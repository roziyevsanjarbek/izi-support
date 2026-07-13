<?php

namespace App\Models\Messages;

use App\Models\Calendar\CalendarEvent;
use App\Models\Calendar\CalendarEventReminder;
use App\Models\Telegram\Madeline\TelegramAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramScheduledMessage extends Model
{
    protected $fillable = [
        'telegram_account_id',
        'message_id',
        'recipient_user_id',
        'calendar_event_id',
        'calendar_event_reminder_id',
        'peer',
        'message',
        'need_call',
        'call_status',
        'send_at',
        'send_before_at',
        'sent_at',
        'status',
        'attempts',
        'last_error',
        'telegram_message_id',
        'telegram_chat_id',
        'telegram_response',
    ];

    protected $casts = [
        'calendar_event_id' => 'integer',
        'calendar_event_reminder_id' => 'integer',
        'send_at' => 'datetime',
        'send_before_at' => 'datetime',
        'sent_at' => 'datetime',
        'need_call' => 'boolean',
        'telegram_response' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(TelegramAccount::class, 'telegram_account_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class, 'calendar_event_id');
    }

    public function reminder(): BelongsTo
    {
        return $this->belongsTo(CalendarEventReminder::class, 'calendar_event_reminder_id');
    }

    public function isEventLinked(): bool
    {
        return (bool) $this->calendar_event_id || (bool) $this->calendar_event_reminder_id;
    }

    public function isCallRequired(): bool
    {
        return (bool) $this->need_call;
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}