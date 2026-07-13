<?php

namespace App\Services\Telegram;

use App\Models\Messages\TelegramScheduledMessage;
use App\Models\Requests\Request;
use App\Models\Telegram\Madeline\TelegramAccount;
use App\Models\TenderRequest;
use App\Models\User;
use Illuminate\Support\Carbon;

class TenderRequestNotificationService
{
    public function scheduleForTenderRequest(Request $request, int $excludeUserId): void
    {
        $request->loadMissing('creator:id,name');

        $recipientTelegramIds = User::query()
            ->whereNotNull('telegram_id')
            ->where('id', '!=', $excludeUserId)
            ->whereHas('role', function ($q) {
                $q->where('name', 'operation');
            })
            ->pluck('telegram_id')
            ->filter()
            ->unique()
            ->values();

        if ($recipientTelegramIds->isEmpty()) {
            return;
        }


        $message = $this->buildTenderRequestMessage($request);
        $now = now();

        $rows = [];
  
        foreach ($recipientTelegramIds as $telegramId) {
            $rows[] = [
                'telegram_account_id' => null,
                'peer' => (string) $telegramId,
                'message' => $message,
                'need_call' => false,
                'send_at' => $now,
                'sent_at' => null,
                'status' => 'pending',
                'attempts' => 0,
                'last_error' => null,

                'telegram_message_id' => null,
                'telegram_chat_id' => (string) $telegramId,
                'telegram_response' => null,

                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            TelegramScheduledMessage::query()->insert($chunk);
        }
    }

    public function buildTenderRequestMessage(Request $request): string
    {
        $creatorName = htmlspecialchars(
            $request->creator?->name ?? 'Unknown',
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        $requestName = htmlspecialchars(
            (string) ($request->name ?? 'Tender Request'),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        $closedAt = optional($request->closed_at)->format('d.m.Y H:i') ?? '—';

        return "🆕 <b>New tender request</b>\n\n"
            . "📌 Name: {$requestName}\n"
            . "👤 Creator: {$creatorName}\n"
            . "⏳ Closes at: {$closedAt}\n"
            . "🕒 Time: " . now()->format('d.m.Y H:i');
    }


}