<?php declare(strict_types=1);

namespace App\Telegram;

use App\Models\Messages\Conversation;
use App\Models\Messages\ConversationPermission;
use App\Models\Messages\Message as ChatMessage;
use App\Models\Messages\TelegramScheduledMessage;
use App\Models\Telegram\Madeline\TelegramAccount;
use App\Models\Telegram\TelegramMessageDelivery;
use App\Models\User;
use App\Services\ConversationMessageNotificationService;
use Amp\CancelledException;
use Amp\TimeoutException;
use Carbon\CarbonInterface;
use danog\MadelineProto\EventHandler\Attributes\Cron;
use danog\MadelineProto\EventHandler\Attributes\Handler;
use danog\MadelineProto\EventHandler\Message as TelegramIncomingMessage;
use danog\MadelineProto\EventHandler\Message\Service\DialogPhoneCall;
use danog\MadelineProto\EventHandler\SimpleFilter\Incoming;
use danog\MadelineProto\ParseMode;
use danog\MadelineProto\SimpleEventHandler;
use danog\MadelineProto\VoIP;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Throwable;

// ❌ OLIB TASHLANDI: use danog\MadelineProto\API; — createApi() o'chirildi

final class TelegramAccountHandler extends SimpleEventHandler
{
    private const CALL_STATUS_PENDING = 'pending';
    private const CALL_STATUS_CALLED  = 'called';
    private const CALL_STATUS_FAILED  = 'failed';

    private int $sentCount    = 0;
    private int $failedCount  = 0;
    private int $handledCount = 0;
    private int $lastReportAt = 0;

    public function getReportPeers(): int|string|array
    {
        return ['@me'];
    }

    public function onStart(): void
    {
        try {
            $account = $this->authorizedAccount();

            if (!$account) {
                $this->logger('TelegramAccountHandler: authorized account not found.');
                return;
            }

            $account->update([
                'status'           => 'running',
                'last_ping'        => now(),
                'last_activity_at' => now(),
                'last_error'       => null,
            ]);

            $this->lastReportAt = time();

            $this->logger("Telegram loop started for {$account->phone}");

            $this->sendAdminReport(
                "🟢 Telegram loop started\n" .
                "Phone: {$account->phone}\n" .
                "Session: {$account->session_path}"
            );
        } catch (Throwable $e) {
            Log::error('TelegramAccountHandler onStart failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    #[Handler]
    public function logIncomingTelegramMessage(Incoming&TelegramIncomingMessage $message): void
    {
        try {
            $this->handledCount++;

            $senderTelegramId = $message->senderId ?? null;
            $chatId           = $message->chatId ?? null;

            if (!$senderTelegramId || !$chatId) {
                return;
            }

            if ($this->isGroupChat($chatId)) {
                return;
            }

            $user = User::query()
                ->where('telegram_id', (string) $senderTelegramId)
                ->first();

            if (!$user) {
                Log::info('Telegram incoming message ignored: linked user not found', [
                    'sender_id'  => $senderTelegramId,
                    'chat_id'    => $chatId,
                    'message_id' => $message->id ?? null,
                ]);
                return;
            }

            $text = trim((string) ($message->message ?? ''));

            if ($text === '') {
                Log::info('Telegram incoming message ignored: empty text', [
                    'sender_id'  => $senderTelegramId,
                    'chat_id'    => $chatId,
                    'message_id' => $message->id ?? null,
                ]);
                return;
            }

            [$conversation, $replyToId, $replyToTelegramMessageId] =
                $this->resolveConversationForIncomingMessage($user, $message);

            if (!$conversation) {
                Log::info('Telegram incoming message ignored: conversation not resolved', [
                    'sender_id'          => $senderTelegramId,
                    'chat_id'            => $chatId,
                    'message_id'         => $message->id ?? null,
                    'reply_to_msg_id'    => $replyToTelegramMessageId,
                ]);
                return;
            }

            $storedMessage = DB::transaction(function () use (
                $conversation, $user, $text, $message, $replyToId, $replyToTelegramMessageId
            ) {
                $this->ensureConversationMember($conversation, $user);

                return ChatMessage::create([
                    'conversation_id'             => $conversation->id,
                    'user_id'                     => $user->id,
                    'message'                     => $text,
                    'type'                        => 'text',
                    'reply_to_id'                 => $replyToId,
                    'source'                      => 'telegram',
                    'telegram_account_id'         => $this->currentAccount()?->id,
                    'telegram_chat_id'            => (string) ($message->chatId ?? ''),
                    'telegram_message_id'         => (int) ($message->id ?? 0),
                    'telegram_reply_to_message_id' => $replyToTelegramMessageId,
                ]);
            });

            $storedMessage->loadMissing(['sender', 'replyTo.sender', 'conversation.task']);

            app(ConversationMessageNotificationService::class)->schedule($storedMessage);

            $this->touchCurrentAccount();
        } catch (Throwable $e) {
            Log::error('Telegram incoming message sync failed', [
                'error'      => $e->getMessage(),
                'message_id' => $message->id ?? null,
            ]);
        }
    }

    private function isGroupChat(int|string|null $chatId): bool
    {
        if ($chatId === null || $chatId === '') {
            return false;
        }

        return (int) $chatId < 0;
    }

    #[Handler]
    public function logIncomingCall(VoIP&Incoming $call): void
    {
        try {
            Log::info('Incoming Telegram call event received', [
                'account' => $this->currentAccount()?->phone,
                'event'   => 'incoming_call',
                'class'   => $call::class,
            ]);
        } catch (Throwable $e) {
            Log::error('Incoming call logging failed', ['error' => $e->getMessage()]);
        }
    }

    #[Handler]
    public function logPhoneCallServiceMessage(DialogPhoneCall $call): void
    {
        try {
            Log::info('Telegram phone call service update', [
                'account' => $this->currentAccount()?->phone,
                'event'   => 'phone_call_service',
                'class'   => $call::class,
            ]);
        } catch (Throwable $e) {
            Log::error('Phone call service logging failed', ['error' => $e->getMessage()]);
        }
    }

    #[Cron(period: 5.0)]
    public function dispatchDueMessages(): void
    {
        try {
            $account = $this->currentAccount();

            if (!$account || !$account->is_authorized) {
                return;
            }

            $jobs = TelegramScheduledMessage::query()
                ->where('status', 'pending')
                ->where('send_at', '<=', now())
                ->where(function ($q) {
                    $q->whereNull('send_before_at')
                        ->orWhere('send_before_at', '>=', now());
                })
                ->orderBy('send_at')
                ->limit(20)
                ->get();

            if ($jobs->isEmpty()) {
                $this->touchCurrentAccount();
                return;
            }

            // ✅ TUZATILDI: createApi() o'chirildi.
            // $this o'zi SimpleEventHandler (== AbstractAPI), IPC instansiyasi kerak emas.

            foreach ($jobs as $job) {
                $account = $this->currentAccount() ?? $account;

                if (!$account || !$account->is_authorized) {
                    break;
                }

                // ✅ $api parametri o'chirildi — $this ichida ishlatiladi
                $continue = $this->processScheduledJob($account, $job);

                if (!$continue) {
                    break;
                }
            }
        } catch (TimeoutException | CancelledException $e) {
            $account = $this->currentAccount() ?? $this->authorizedAccount();

            $this->markLoopInterrupted(
                account: $account,
                e: $e,
                status: 'stopped'
            );

            // ✅ YANGI: watchdog qayta ishga tushurishiga exit bilan signal beramiz.
            // Bu olmasa loop "zombie" holatda qoladi — barcha cronlar ishlaydi,
            // lekin Telegram API chaqiruvlari bajarilmaydi.
            Log::critical('CancelledException caught in dispatchDueMessages — exiting for watchdog restart', [
                'error'     => $e->getMessage(),
                'exception' => $e::class,
            ]);

            exit(1);
        } catch (Throwable $e) {
            $account = $this->currentAccount() ?? $this->authorizedAccount();

            if ($account && $this->isAuthLostException($e)) {
                $this->handleAuthLoss($account, $e);
                return;
            }

            Log::critical('dispatchDueMessages crashed', [
                'error'     => $e->getMessage(),
                'exception' => $e::class,
            ]);
        }
    }

    #[Cron(period: 3600.0)]
    public function sendStatusReport(): void
    {
        try {
            $account = $this->currentAccount();

            if (!$account || !$account->is_authorized) {
                return;
            }

            $pending = TelegramScheduledMessage::query()->where('status', 'pending')->count();
            $sending = TelegramScheduledMessage::query()->where('status', 'sending')->count();
            $sent    = TelegramScheduledMessage::query()->where('status', 'sent')->count();
            $failed  = TelegramScheduledMessage::query()->where('status', 'failed')->count();

            $callPending = TelegramScheduledMessage::query()->where('call_status', self::CALL_STATUS_PENDING)->count();
            $callCalled  = TelegramScheduledMessage::query()->where('call_status', self::CALL_STATUS_CALLED)->count();
            $callFailed  = TelegramScheduledMessage::query()->where('call_status', self::CALL_STATUS_FAILED)->count();

            $text =
                "📊 Telegram status report\n" .
                "Phone: {$account->phone}\n" .
                "Status: {$account->status}\n" .
                "Pending: {$pending}\n" .
                "Sending: {$sending}\n" .
                "Sent: {$sent}\n" .
                "Failed: {$failed}\n" .
                "Call pending: {$callPending}\n" .
                "Call called: {$callCalled}\n" .
                "Call failed: {$callFailed}\n" .
                "Handled updates: {$this->handledCount}\n" .
                "Sent now: {$this->sentCount}\n" .
                "Failed now: {$this->failedCount}\n" .
                "Last ping: " . optional($account->last_ping)->toDateTimeString();

            Log::info('Telegram status report', [
                'account_phone' => $account->phone,
                'pending'       => $pending,
                'sending'       => $sending,
                'sent'          => $sent,
                'failed'        => $failed,
                'call_pending'  => $callPending,
                'call_called'   => $callCalled,
                'call_failed'   => $callFailed,
                'handled_now'   => $this->handledCount,
                'sent_now'      => $this->sentCount,
                'failed_now'    => $this->failedCount,
            ]);

            $this->sendAdminReport($text);

            $this->lastReportAt = time();
            $this->sentCount    = 0;
            $this->failedCount  = 0;
            $this->handledCount = 0;
        } catch (Throwable $e) {
            Log::error('sendStatusReport failed', ['error' => $e->getMessage()]);
        }
    }

    // ✅ TUZATILDI: API $api parametri olib tashlandi — $this ishlatiladi
    private function processScheduledJob(
        TelegramAccount $account,
        TelegramScheduledMessage $job
    ): bool {
        try {
            $deadline = $job->send_before_at instanceof CarbonInterface
    ? $job->send_before_at->copy()->addMinutes(20)
    : null;

            if ($deadline && now()->gt($deadline)) {
                $this->syncJobAndReminder($job, [
                    'status'     => 'failed',
                    'last_error' => 'expired_before_send_window',
                    'sent_at'    => now(),
                ], [
                    'status'      => 'failed',
                    'last_sent_at' => now(),
                    'done_at'     => now(),
                    'last_error'  => 'expired_before_send_window',
                ]);

                if ($job->need_call) {
                    $this->syncJobAndReminder($job, [
                        'call_status' => self::CALL_STATUS_FAILED,
                    ], [
                        'call_status' => self::CALL_STATUS_FAILED,
                    ]);
                }

                Log::info('Telegram scheduled message skipped: expired window', [
                    'job_id'         => $job->id,
                    'peer'           => $job->peer,
                    'send_at'        => $job->send_at?->toDateTimeString(),
                    'send_before_at' => $deadline->toDateTimeString(),
                    'now'            => now()->toDateTimeString(),
                ]);

                return true;
            }

            $this->syncJobAndReminder($job, [
                'status'     => 'sending',
                'attempts'   => $job->attempts + 1,
                'last_error' => null,
            ], [
                'status'     => 'retrying',
                'attempts'   => $job->attempts + 1,
                'last_error' => null,
            ]);

            // ✅ TUZATILDI: $api->messages->... → $this->messages->...
            $sent = $this->messages->sendMessage([
                'peer'        => $job->peer,
                'message'     => $job->message,
                'parse_mode'  => ParseMode::HTML,
                'no_webpage'  => true,
            ]);

            $telegramMessageId = $this->resolveSentTelegramMessageId($sent);

            $sendUpdate = [
                'telegram_message_id'  => $telegramMessageId ? (int) $telegramMessageId : null,
                'telegram_chat_id'     => (string) $job->peer,
                'telegram_account_id'  => $account->id,
                'sent_at'              => now(),
                'status'               => 'sent',
                'last_error'           => $telegramMessageId ? null : 'telegram_message_id_not_resolved',
            ];

            $reminderUpdate = [
                'status'      => 'sent',
                'last_sent_at' => now(),
                'done_at'     => now(),
                'last_error'  => $telegramMessageId ? null : 'telegram_message_id_not_resolved',
            ];

            $this->syncJobAndReminder($job, $sendUpdate, $reminderUpdate);

            $recipientUserId = $job->recipient_user_id
                ?: User::query()
                    ->where('telegram_id', (string) $job->peer)
                    ->value('id');

            if ($recipientUserId && $telegramMessageId) {
                TelegramMessageDelivery::create([
                    'message_id'          => $job->message_id,
                    'user_id'             => (int) $recipientUserId,
                    'telegram_chat_id'    => (int) $job->peer,
                    'telegram_message_id' => (int) $telegramMessageId,
                ]);
            }

            if ($job->need_call) {
                // ✅ TUZATILDI: $api parametri olib tashlandi
                $callSucceeded = $this->attemptCall($account, $job);

                if ($callSucceeded) {
                    $this->syncJobAndReminder($job, [
                        'call_status' => self::CALL_STATUS_CALLED,
                    ], [
                        'call_status' => self::CALL_STATUS_CALLED,
                    ]);
                }
            }

            $this->sentCount++;

            Log::info('Telegram scheduled message sent', [
                'account_phone' => $account->phone,
                'job_id'        => $job->id,
                'peer'          => $job->peer,
                'need_call'     => $job->need_call,
                'call_status'   => $job->call_status,
            ]);

            $this->touchAccount($account);

            return true;
        } catch (TimeoutException | CancelledException $e) {
            $error = mb_substr($e->getMessage(), 0, 1000);

            $this->syncJobAndReminder($job, [
                'status'     => 'failed',
                'last_error' => 'timeout_or_cancelled: ' . $error,
            ], [
                'status'     => 'failed',
                'last_error' => 'timeout_or_cancelled: ' . $error,
            ]);

            if ($job->need_call) {
                $this->syncJobAndReminder($job, [
                    'call_status' => self::CALL_STATUS_FAILED,
                ], [
                    'call_status' => self::CALL_STATUS_FAILED,
                ]);
            }

            $account->increment('error_count');
            $account->update([
                'last_error'    => $error,
                'last_error_at' => now(),
                'last_ping'     => now(),
            ]);

            $this->failedCount++;

            Log::warning('Telegram scheduled message interrupted', [
                'account_phone' => $account->phone,
                'job_id'        => $job->id,
                'peer'          => $job->peer,
                'error'         => $error,
                'exception'     => $e::class,
            ]);

            return false;
        } catch (Throwable $e) {
            if ($this->isAuthLostException($e)) {
                $this->handleAuthLoss($account, $e);
                return false;
            }

            $error = mb_substr($e->getMessage(), 0, 1000);

            $this->syncJobAndReminder($job, [
                'status'     => 'failed',
                'last_error' => $error,
            ], [
                'status'     => 'failed',
                'last_error' => $error,
            ]);

            if ($job->need_call) {
                $this->syncJobAndReminder($job, [
                    'call_status' => self::CALL_STATUS_FAILED,
                ], [
                    'call_status' => self::CALL_STATUS_FAILED,
                ]);
            }

            $account->increment('error_count');
            $account->update([
                'last_error'    => $error,
                'last_error_at' => now(),
                'last_ping'     => now(),
            ]);

            $this->failedCount++;

            Log::error('Telegram scheduled message failed', [
                'account_phone' => $account->phone,
                'job_id'        => $job->id,
                'peer'          => $job->peer,
                'error'         => $error,
                'exception'     => $e::class,
            ]);

            $this->sendAdminReport(
                "⚠️ Scheduled message failed\n" .
                "Phone: {$account->phone}\n" .
                "Job ID: {$job->id}\n" .
                "Error: {$error}"
            );

            return true;
        }
    }

    private function syncJobAndReminder(
        TelegramScheduledMessage $job,
        array $jobData,
        ?array $reminderData = null
    ): void {
        $job->update($jobData);
        $job->refresh();

        $reminder = $job->reminder;

        if ($reminder && $reminderData !== null) {
            $reminder->update($reminderData);
        }
    }

    // ✅ TUZATILDI: API $api parametri olib tashlandi — $this->requestCall() ishlatiladi
    private function attemptCall(TelegramAccount $account, TelegramScheduledMessage $job): bool
    {
        try {
            // ✅ $api->requestCall → $this->requestCall
            $this->requestCall($job->peer);

            Log::info('Telegram call requested', [
                'account_phone' => $account->phone,
                'job_id'        => $job->id,
                'peer'          => $job->peer,
            ]);

            return true;
        } catch (TimeoutException | CancelledException $e) {
            $error = mb_substr($e->getMessage(), 0, 900);

            $this->syncJobAndReminder($job, [
                'call_status' => self::CALL_STATUS_FAILED,
                'last_error'  => trim((string) ($job->last_error ? $job->last_error . ' | ' : '') . 'call_interrupted: ' . $error),
            ], [
                'call_status' => self::CALL_STATUS_FAILED,
                'last_error'  => trim((string) ($job->last_error ? $job->last_error . ' | ' : '') . 'call_interrupted: ' . $error),
            ]);

            Log::warning('Telegram call interrupted', [
                'account_phone' => $account->phone,
                'job_id'        => $job->id,
                'peer'          => $job->peer,
                'error'         => $error,
                'exception'     => $e::class,
            ]);

            return false;
        } catch (Throwable $e) {
            if ($this->isAuthLostException($e)) {
                $this->handleAuthLoss($account, $e);
                return false;
            }

            $error = mb_substr($e->getMessage(), 0, 900);

            $this->syncJobAndReminder($job, [
                'call_status' => self::CALL_STATUS_FAILED,
                'last_error'  => trim((string) ($job->last_error ? $job->last_error . ' | ' : '') . 'call_failed: ' . $error),
            ], [
                'call_status' => self::CALL_STATUS_FAILED,
                'last_error'  => trim((string) ($job->last_error ? $job->last_error . ' | ' : '') . 'call_failed: ' . $error),
            ]);

            Log::warning('Telegram call failed', [
                'account_phone' => $account->phone,
                'job_id'        => $job->id,
                'peer'          => $job->peer,
                'error'         => $error,
            ]);

            // ✅ TUZATILDI: $api parametri olib tashlandi
            $this->sendSimpleMessageSafely(
                $job->peer,
                'We were unable to place an automated Telegram call to your account. ' .
                'This can happen if Telegram calling is unavailable, restricted by your privacy settings, ' .
                'or temporarily unsupported for your account. ' .
                'Please review the message above and contact support if you need assistance.'
            );

            return false;
        }
    }

    // ✅ TUZATILDI: API $api parametri olib tashlandi — $this->messages->... ishlatiladi
    private function sendSimpleMessageSafely(int|string $peer, string $text): void
    {
        try {
            // ✅ $api->messages->sendMessage → $this->messages->sendMessage
            $this->messages->sendMessage([
                'peer'       => $peer,
                'message'    => $text,
                'parse_mode' => ParseMode::HTML,
                'no_webpage' => true,
            ]);
        } catch (Throwable $e) {
            Log::error('Fallback Telegram message failed', [
                'peer'  => $peer,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function resolveConversationForIncomingMessage(User $user, TelegramIncomingMessage $message): array
{
    $chatId = (string) ($message->chatId ?? '');
    $replyToTelegramMessageId = $this->extractReplyToTelegramMessageId($message);

    // Reply bo'lmasa umuman ignore
    if (!$replyToTelegramMessageId) {
        return [null, null, null];
    }

    $delivery = TelegramMessageDelivery::with('message.conversation')
        ->where('telegram_chat_id', $chatId)
        ->where('telegram_message_id', (int) $replyToTelegramMessageId)
        ->first();

    if (!$delivery?->message?->conversation) {
        return [null, null, $replyToTelegramMessageId];
    }

    return [
        $delivery->message->conversation,
        $delivery->message->id,
        $replyToTelegramMessageId,
    ];
}

    private function extractReplyToTelegramMessageId(TelegramIncomingMessage $message): ?int
    {
        $replyTo = $message->replyToMsgId ?? null;

        if ($replyTo === null || $replyTo === '') {
            return null;
        }

        return (int) $replyTo;
    }

    private function ensureConversationMember(Conversation $conversation, User $user): void
    {
        ConversationPermission::firstOrCreate(
            [
                'conversation_id' => $conversation->id,
                'user_id'         => $user->id,
            ],
            [
                'role'               => 'member',
                'notifications'      => true,
                'can_add_user'       => false,
                'can_remove_user'    => false,
                'can_delete_message' => false,
                'can_change_name'    => false,
                'can_pin_message'    => false,
                'can_send_messages'  => true,
            ]
        );
    }

    private function authorizedAccount(): ?TelegramAccount
    {
        return TelegramAccount::query()
            ->where('is_authorized', true)
            ->first();
    }

    private function currentAccount(): ?TelegramAccount
    {
        return TelegramAccount::query()
            ->where('is_authorized', true)
            ->where('status', 'running')
            ->first();
    }

    // ✅ createApi() BUTUNLAY O'CHIRILDI — bu muammoning asosiy sababi edi.
    // Har 5 soniyada yangi IPC API instansiyasi yaratilardi → connection conflicts → CancelledException.
    // Endi $this->messages->sendMessage() va $this->requestCall() to'g'ridan-to'g'ri ishlatiladi.

    private function resolveSentTelegramMessageId(mixed $sent): ?int
    {
        $telegramMessageId = data_get($sent, 'id')
            ?? data_get($sent, 'message_id')
            ?? data_get($sent, 'updates.0.message.id')
            ?? data_get($sent, 'updates.0.id');

        if ($telegramMessageId === null || $telegramMessageId === '') {
            return null;
        }

        return (int) $telegramMessageId;
    }

    private function sendAdminReport(string $text): void
    {
        try {
            $this->sendMessageToAdmins($text);
        } catch (Throwable $e) {
            Log::error('Telegram report failed', ['error' => $e->getMessage()]);
        }
    }

    private function touchCurrentAccount(): void
    {
        $account = $this->currentAccount();

        if (!$account) {
            return;
        }

        $this->touchAccount($account);
    }

    private function touchAccount(TelegramAccount $account): void
    {
        try {
            $account->update([
                'last_ping'        => now(),
                'last_activity_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('Failed to update account heartbeat', [
                'account_phone' => $account->phone ?? null,
                'error'         => $e->getMessage(),
            ]);
        }
    }

    private function markLoopInterrupted(?TelegramAccount $account, Throwable $e, string $status = 'stopped'): void
    {
        if (!$account) {
            Log::warning('Telegram loop interrupted without active account', [
                'status'    => $status,
                'error'     => $e->getMessage(),
                'exception' => $e::class,
            ]);
            return;
        }

        $error = mb_substr($e->getMessage(), 0, 1000);

        Log::warning('Telegram loop interrupted', [
            'account_id' => $account->id,
            'phone'      => $account->phone,
            'status'     => $status,
            'error'      => $error,
            'exception'  => $e::class,
        ]);

        $account->update([
            'status'        => $status,
            'last_error'    => $error,
            'last_error_at' => now(),
            'last_ping'     => now(),
        ]);
    }

    private function isAuthLostException(Throwable $e): bool
    {
        $message = mb_strtolower($e->getMessage());

        return str_contains($message, 'auth_key_unregistered')
            || str_contains($message, 'session revoked')
            || str_contains($message, 'logged out')
            || str_contains($message, 'unauthorized')
            || str_contains($message, 'flood prevention system suspended')
            || str_contains($message, 'permanent auth key was main authorized key, logging out')
            || str_contains($message, 'unknown auth_key id');
    }

    private function handleAuthLoss(TelegramAccount $account, Throwable $e): void
    {
        $sessionPath = $account->session_path;
        $error       = mb_substr($e->getMessage(), 0, 1000);

        Log::warning('Telegram account session lost, forcing logout cleanup', [
            'account_id' => $account->id,
            'phone'      => $account->phone,
            'error'      => $error,
            'exception'  => $e::class,
        ]);

        $this->failPendingMessages($account);
        $this->cleanupSession($sessionPath, $account);

        $account->update([
            'status'           => 'logged_out',
            'is_authorized'    => false,
            'session_path'     => null,
            'last_error'       => null,
            'last_error_at'    => now(),
            'last_ping'        => now(),
            'last_activity_at' => null,
        ]);
    }

    private function cleanupSession(?string $sessionPath, TelegramAccount $account): void
    {
        if (!$sessionPath) {
            return;
        }

        try {
            if (file_exists($sessionPath)) {
                if (is_dir($sessionPath)) {
                    File::deleteDirectory($sessionPath);
                } else {
                    File::delete($sessionPath);
                }
            }
        } catch (Throwable $e) {
            Log::warning('Telegram session cleanup failed', [
                'account_id' => $account->id,
                'phone'      => $account->phone,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    private function failPendingMessages(TelegramAccount $account): void
    {
        $jobs = TelegramScheduledMessage::query()
            ->with('reminder')
            ->where('telegram_account_id', $account->id)
            ->whereIn('status', ['pending', 'sending'])
            ->get();

        foreach ($jobs as $job) {
            $this->syncJobAndReminder($job, [
                'status'     => 'failed',
                'last_error' => 'Account logged out',
            ], [
                'status'     => 'failed',
                'last_error' => 'Account logged out',
            ]);

            if ($job->need_call) {
                $this->syncJobAndReminder($job, [
                    'call_status' => self::CALL_STATUS_FAILED,
                ], [
                    'call_status' => self::CALL_STATUS_FAILED,
                ]);
            }
        }
    }
}