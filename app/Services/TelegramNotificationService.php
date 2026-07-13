<?php

namespace App\Services;

use App\Models\Messages\TelegramScheduledMessage;
use App\Models\Tasks\Task;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class TelegramNotificationService
{
    public function scheduleTaskCreatedNotification(User $user, Task $task): void
    {
        if (! $user?->telegram_id) {
            Log::warning('Telegram notification skipped: user has no telegram_id', [
                'user_id' => $user?->id,
                'task_id' => $task?->id,
            ]);

            return;
        }

        $text = "🆕 New task created!\n\n"
            . "📌 Title: {$task->name}\n"
            . "📝 Description: {$task->description}\n";

        $this->storeScheduledMessage(
            telegramId: $user->telegram_id,
            text: $text,
            context: [
                'event' => 'task_created',
                'user_id' => $user->id,
                'task_id' => $task->id,
            ]
        );
    }

    public function scheduleTaskCompletedNotification(Task $task): void
    {
        $creator = $task->creator;

        if (! $creator?->telegram_id) {
            return;
        }

        $completedBy = $task->completedBy?->name ?? 'Unknown User';

        $text = "✅ Task completed!\n\n"
            . "📌 Title: {$task->name}\n"
            . "👤 Completed by: {$completedBy}\n"
            . "🕒 Time: " . now()->format('d.m.Y H:i');

        $this->storeScheduledMessage(
            telegramId: $creator->telegram_id,
            text: $text,
            context: [
                'event' => 'task_completed',
                'task_id' => $task->id,
                'creator_id' => $creator->id,
            ]
        );
    }

    public function scheduleTaskStartedNotification(Task $task, User $user): void
    {
        $creator = $task->creator;

        if (! $creator?->telegram_id) {
            return;
        }

        $text = "🚀 Task started!\n\n"
            . "📌 Title: {$task->name}\n"
            . "👤 Started by: {$user->name}\n"
            . "🕒 Time: " . now()->format('d.m.Y H:i');

        $this->storeScheduledMessage(
            telegramId: $creator->telegram_id,
            text: $text,
            context: [
                'event' => 'task_started',
                'task_id' => $task->id,
                'user_id' => $user->id,
            ]
        );
    }

    private function storeScheduledMessage(int|string $telegramId, string $text, array $context = []): void
    {
        try {
            TelegramScheduledMessage::create([
                'telegram_account_id' => null, // keyin sender resolve qilsa bo‘ladi
                'peer' => (string) $telegramId,
                'message' => $text,
                'need_call' => false,
                'send_at' => now(),
                'sent_at' => null,
                'status' => 'pending',
                'attempts' => 0,
                'last_error' => null,
                'telegram_message_id' => null,
                'telegram_chat_id' => (string) $telegramId,
                'telegram_response' => null,
            ]);
        } catch (\Throwable $e) {
            Log::critical('Failed to create scheduled Telegram message', $context + [
                'telegram_id' => $telegramId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}