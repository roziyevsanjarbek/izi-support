<?php

namespace App\Models\Messages;

use App\Models\Telegram\TelegramMessageDelivery;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'user_id',
        'message',
        'type',
        'reply_to_id',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'latitude',
        'longitude',
        'location_name',
        'is_edited',
        'is_deleted',
        'is_read',
        'source',
        'telegram_account_id',
        'telegram_chat_id',
        'telegram_message_id',
        'telegram_reply_to_message_id',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
        'is_edited' => 'boolean',
        'is_deleted' => 'boolean',
        'is_read' => 'boolean',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'reply_to_id');
    }

    public function telegramDeliveries(): HasMany
    {
        return $this->hasMany(TelegramMessageDelivery::class, 'message_id');
    }

    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? Storage::disk('public')->url($this->file_path) : null;
    }

    public function getIsMineAttribute(): bool
    {
        return (int) $this->user_id === (int) auth()->id();
    }

    public function getPreviewAttribute(): string
    {
        if ($this->is_deleted) {
            return 'Message deleted';
        }

        if (!empty($this->message)) {
            return (string) $this->message;
        }

        if (!empty($this->file_name)) {
            return (string) $this->file_name;
        }

        if ($this->latitude !== null && $this->longitude !== null) {
            return 'Location';
        }

        return 'Message';
    }
}
