<?php declare(strict_types=1);

namespace App\Services;

use App\Models\Messages\Conversation;
use App\Models\Messages\ConversationPermission;
use App\Models\Messages\Message as ChatMessage;
use App\Models\Messages\TelegramScheduledMessage;
use App\Models\Telegram\Madeline\TelegramAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ConversationMessageNotificationService
{
    public function schedule(ChatMessage $message): void
    {
        try {
            $message->loadMissing([
                'conversation.permissions.user',
                'conversation.task',
                'sender',
                'replyTo.sender',
                'replyTo',
            ]);

            $conversation = $message->conversation;

            if (! $conversation instanceof Conversation) {
                return;
            }

            $senderId = (int) $message->user_id;
            $replySenderId = (int) ($message->replyTo?->user_id ?? 0);

            $now = now();

            /**
             * 1) unread_count har doim oshadi
             *    - notification=true bo'lsa Telegram schedule ham yoziladi
             *    - notification=false bo'lsa faqat quiet unread qoladi
             */
            $recipientPermissions = $conversation->permissions
                ->filter(function ($permission) use ($senderId) {
                    $permissionUser = $permission->user;

                    if (! $permissionUser instanceof User) {
                        return false;
                    }

                    return (int) $permissionUser->id !== $senderId;
                })
                ->values();

            $recipientIds = $recipientPermissions
                ->pluck('user_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->values();

            if ($recipientIds->isNotEmpty()) {
                DB::transaction(function () use ($conversation, $recipientIds) {
                    ConversationPermission::query()
                        ->where('conversation_id', $conversation->id)
                        ->whereIn('user_id', $recipientIds->all())
                        ->increment('unread_count', 1);
                });

                Log::info('Conversation unread_count incremented', [
                    'conversation_id' => $conversation->id,
                    'message_id' => $message->id,
                    'sender_id' => $senderId,
                    'recipient_ids' => $recipientIds->all(),
                    'count' => $recipientIds->count(),
                ]);
            }

            /**
             * 2) Telegram schedule faqat notifications=true bo'lsa
             */
            $account = $this->runningAccount();
            $text = $this->buildOutboundText($message);

            $rows = [];
            $scheduledRecipientIds = [];

            foreach ($recipientPermissions as $permission) {
                $permissionUser = $permission->user;

                if (! $permissionUser instanceof User) {
                    continue;
                }

                if (
                    empty($permission->notifications) ||
                    empty($permissionUser->telegram_id)
                ) {
                    continue;
                }

                $scheduledRecipientIds[] = (int) $permissionUser->id;

                $rows[] = [
                    'telegram_account_id' => $account?->id,
                    'message_id' => $message->id,
                    'recipient_user_id' => $permissionUser->id,
                    'peer' => (string) $permissionUser->telegram_id,
                    'message' => $text,
                    'need_call' => false,
                    'send_at' => $now,
                    'sent_at' => null,
                    'status' => 'pending',
                    'attempts' => 0,
                    'last_error' => null,
                    'telegram_message_id' => null,
                    'telegram_chat_id' => (string) $permissionUser->telegram_id,
                    'telegram_response' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            /**
             * Reply qilgan userga ham faqat notifications=true bo'lsa schedule qilinadi
             */
            if ($replySenderId && $replySenderId !== $senderId) {
                $replyUser = User::query()->find($replySenderId);

                if ($replyUser?->telegram_id) {
                    $replyPermission = $conversation->permissions->firstWhere('user_id', $replySenderId);

                    if (
                        $replyPermission?->notifications &&
                        ! collect($scheduledRecipientIds)->contains($replyUser->id)
                    ) {
                        $scheduledRecipientIds[] = (int) $replyUser->id;

                        $rows[] = [
                            'telegram_account_id' => $account?->id,
                            'message_id' => $message->id,
                            'recipient_user_id' => $replyUser->id,
                            'peer' => (string) $replyUser->telegram_id,
                            'message' => $text,
                            'need_call' => false,
                            'send_at' => $now,
                            'sent_at' => null,
                            'status' => 'pending',
                            'attempts' => 0,
                            'last_error' => null,
                            'telegram_message_id' => null,
                            'telegram_chat_id' => (string) $replyUser->telegram_id,
                            'telegram_response' => null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
            }

            if (empty($rows)) {
                Log::info('Conversation notification skipped for Telegram schedule', [
                    'conversation_id' => $conversation->id,
                    'message_id' => $message->id,
                    'sender_id' => $senderId,
                    'reason' => 'No recipients with notifications enabled',
                ]);

                return;
            }

            Log::info('Conversation Telegram notifications prepared', [
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
                'sender_id' => $senderId,
                'account_id' => $account?->id,
                'scheduled_count' => count($rows),
                'scheduled_recipient_ids' => $scheduledRecipientIds,
            ]);

            foreach (array_chunk($rows, 500) as $index => $chunk) {
                TelegramScheduledMessage::query()->insert($chunk);

                Log::info('Conversation Telegram notification batch inserted', [
                    'conversation_id' => $conversation->id,
                    'message_id' => $message->id,
                    'batch' => $index + 1,
                    'rows' => count($chunk),
                ]);
            }
        } catch (Throwable $e) {
            Log::error('Failed to schedule conversation telegram notifications', [
                'conversation_id' => $message->conversation_id ?? null,
                'message_id' => $message->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function buildOutboundText(ChatMessage $message): string
{
    $conversation = $message->conversation;
    $task = $conversation?->task;

    $senderName = htmlspecialchars(
        $message->sender?->name ?? 'Unknown',
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );

        $messageText = (string) ($message->message ?: '[file]');

        if (mb_strlen($messageText, 'UTF-8') > 1000) {
            $messageText = mb_substr($messageText, 0, 1000, 'UTF-8') . '...';
        }

        $messageText = htmlspecialchars(
            $messageText,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        $lines = [];
    $lines[] = '💬 <b>New message received</b>';
    $lines[] = '';

    if ($task !== null && ! empty($task->id)) {
        $taskName = htmlspecialchars(
            $task->name ?? 'Task',
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        try {
            $taskUrl = route('tasks.show', $task->id);

            $lines[] = '📌 Task: <a href="' .
                htmlspecialchars($taskUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
                '">' . $taskName . '</a>';
        } catch (Throwable) {
            $lines[] = '📌 Task: ' . $taskName;
        }

        $lines[] = '👤 From: ' . $senderName;
    } else {
        try {
            $conversationUrl = route('messages.conversations.index', [
                'conversation' => $conversation?->id,
            ]);

            $lines[] = '👤 From: <a href="' .
                htmlspecialchars($conversationUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
                '">' .
                $senderName .
                '</a>';
        } catch (Throwable) {
            $lines[] = '👤 From: ' . $senderName;
        }
    }

    // $lines[] = '📝 Message: ' . $messageText;
    $lines[] = '📝 Message:' . PHP_EOL . "<blockquote>{$messageText}</blockquote>";

    if ($message->reply_to_id) {
        $replySourceText = htmlspecialchars(
            (string) (
                $message->replyTo?->message
                ?: $message->replyTo?->file_name
                ?: 'file'
            ),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        $lines[] = '↩️ Reply to: ' . $replySourceText;
    }

    $lines[] = '🕒 Time: ' . ($message->created_at?->format('d.m.Y H:i') ?? now()->format('d.m.Y H:i'));

    return implode("\n", $lines);
}

    private function runningAccount(): ?TelegramAccount
    {
        try {
            return TelegramAccount::query()
                ->where('is_authorized', true)
                ->where('status', 'running')
                ->first();
        } catch (Throwable $e) {
            Log::warning('Failed to resolve running telegram account', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
