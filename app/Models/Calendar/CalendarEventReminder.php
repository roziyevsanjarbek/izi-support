<?php

namespace App\Models\Calendar;

use App\Models\Messages\TelegramScheduledMessage;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CalendarEventReminder extends Model
{
    protected $fillable = [
        'calendar_event_id',
        'occurrence_at',
        'color',
        'status',
        'attempts',
        'max_attempts',
        'interval_minutes',
        'next_send_at',
        'last_sent_at',
        'done_at',
        'last_error',
        'need_call',
        'call_status',
        'channels',
        'meta',
    ];

    protected $casts = [
    'attempts' => 'integer',
    'max_attempts' => 'integer',
    'interval_minutes' => 'integer',
    'next_send_at' => 'datetime',
    'last_sent_at' => 'datetime',
    'done_at' => 'datetime',
    'need_call' => 'boolean',
    'channels' => 'array',
    'meta' => 'array',
];

    public function event(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class, 'calendar_event_id');
    }

    public function isDue(): bool
    {
        return in_array($this->status, ['pending', 'retrying'], true)
            && $this->next_send_at instanceof CarbonInterface
            && $this->next_send_at->lte(now())
            && $this->attempts < $this->max_attempts;
    }
    public function scheduledMessages(): HasMany
    {
        return $this->hasMany(TelegramScheduledMessage::class, 'calendar_event_reminder_id');
    }
}