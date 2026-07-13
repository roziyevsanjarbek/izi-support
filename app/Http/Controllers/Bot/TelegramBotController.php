<?php

namespace App\Http\Controllers\Bot;

use App\Http\Controllers\Controller;
use App\Models\Messages\Conversation;
use App\Models\Messages\ConversationPermission;
use App\Models\Messages\Message;
use App\Models\Telegram\TelegramLinkToken;
use App\Models\Telegram\TelegramMessageDelivery;
use App\Models\User;
use App\Services\ConversationMessageNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Telegram\Bot\Api;

class TelegramBotController extends Controller
{
    protected Api $telegram;

    public function __construct()
    {
        $this->telegram = app('telegram')->bot('notification_bot');
    }

    public function webhook(): JsonResponse
    {
        $update = $this->telegram->getWebhookUpdate();
        $payload = method_exists($update, 'toArray') ? $update->toArray() : (array) $update;

        $messagePayload = data_get($payload, 'message');

        if (! $messagePayload) {
            return response()->json(['ok' => true]);
        }

        $text = trim((string) data_get($messagePayload, 'text', ''));
        $chatId = (int) data_get($messagePayload, 'chat.id');
        $telegramId = (string) data_get($messagePayload, 'from.id');
        $firstName = (string) data_get($messagePayload, 'from.first_name', 'there');

        // /start bind flow
        if (str_starts_with($text, '/start')) {
            return $this->handleStart($chatId, $telegramId, $firstName, $text);
        }

        // faqat text reply ishlaymiz
        if ($text === '') {
            return response()->json(['ok' => true]);
        }

        $user = User::where('telegram_id', $telegramId)->first();

        if (! $user) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Please link your web account with Telegram before continuing.',
            ]);

            return response()->json(['ok' => true]);
        }

        $replyToTelegramMessageId = data_get($messagePayload, 'reply_to_message.message_id');

        if (! $replyToTelegramMessageId) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Please reply to this message. At the moment, only replies are supported; sending a new standalone message is not available.',
            ]);

            return response()->json(['ok' => true]);
        }

        $delivery = TelegramMessageDelivery::with('message.conversation')
            ->where('telegram_chat_id', $chatId)
            ->where('telegram_message_id', (int) $replyToTelegramMessageId)
            ->first();

        if (! $delivery || ! $delivery->message || ! $delivery->message->conversation) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'The internal message for this reply could not be found.',
            ]);

            return response()->json(['ok' => true]);
        }

        $originMessage = $delivery->message;
        $conversation = $originMessage->conversation;

        $newMessage = DB::transaction(function () use ($conversation, $user, $text, $originMessage) {
            $this->ensureConversationMember($conversation, $user);

            return Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'message' => $text,
                'type' => 'text',
                'reply_to_id' => $originMessage->id,
            ]);
        });

        $newMessage->load('sender', 'conversation.task', 'replyTo');

        app(ConversationMessageNotificationService::class)->notify($newMessage);

        return response()->json(['ok' => true]);
    }

    private function handleStart(int $chatId, string $telegramId, string $firstName, string $text): JsonResponse
    {
        $parts = preg_split('/\s+/', $text, 2);
        $startPayload = $parts[1] ?? '';

        if ($startPayload && str_starts_with($startPayload, 'bind_')) {
            $token = substr($startPayload, 5);

            $linkToken = TelegramLinkToken::where('token', $token)
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->first();

            if (! $linkToken) {
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'This link is invalid or has expired.',
                ]);

                return response()->json(['ok' => true]);
            }

            $user = User::find($linkToken->user_id);

            if (! $user) {
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'User not found.',
                ]);

                return response()->json(['ok' => true]);
            }

            $alreadyLinkedUser = User::where('telegram_id', $telegramId)
                ->where('id', '!=', $user->id)
                ->first();

            if ($alreadyLinkedUser) {
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'This Telegram account is already linked to another profile.',
                ]);

                return response()->json(['ok' => true]);
            }

            $user->update(['telegram_id' => $telegramId]);

            $linkToken->update(['used_at' => now()]);

            $notifyUsername = env('TELEGRAM_NOTIFICATION_USERNAME');
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Hello, {$firstName}! Your Telegram ID has been saved successfully.\n\nTo receive notifications, please start a chat with this user: {$notifyUsername}",
            ]);

            return response()->json(['ok' => true]);
        }

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "Welcome, {$firstName}!",
        ]);

        return response()->json(['ok' => true]);
    }

    private function ensureConversationMember(Conversation $conversation, User $user): void
    {
        ConversationPermission::firstOrCreate(
            [
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
            ],
            [
                'role' => 'member',
                'notifications' => true,
                'can_add_user' => false,
                'can_remove_user' => false,
                'can_delete_message' => false,
                'can_change_name' => false,
                'can_pin_message' => false,
                'can_send_messages' => true,
            ]
        );
    }
}