<?php

namespace App\Models\Telegram;

use App\Models\Messages\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramMessageDelivery extends Model
{
    protected $fillable = [
        'message_id',
        'user_id',
        'telegram_chat_id',
        'telegram_message_id',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}