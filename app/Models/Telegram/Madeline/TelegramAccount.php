<?php

namespace App\Models\Telegram\Madeline;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramAccount extends Model
{
    protected $fillable = [
        'phone',
        'session_path',
        'is_authorized',
        'authorized_at',
        'status',
        'message',
        'message_key',
        'last_ping',
        'last_activity_at',
        'error_count',
        'last_error',
        'last_error_at',
        'meta',
    ];

    protected $casts = [
        'is_authorized' => 'boolean',
        'authorized_at' => 'datetime',
        'last_ping' => 'datetime',
        'last_activity_at' => 'datetime',
        'last_error_at' => 'datetime',
        'meta' => 'array',
    ];

    public function scheduledMessages(): HasMany
    {
        return $this->hasMany(TelegramScheduledMessage::class, 'telegram_account_id');
    }
}