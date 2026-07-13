<?php

namespace App\Models\Calendar;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class CalendarEvent extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'start_at',
        'end_at',
        'all_day',
        'timezone',
        'color',
        'status',
        'reminder_at',
        'next_reminder_at',
        'reminder_sent',
        'reminder_sent_at',
        'reminder_call_enabled',
        'reminder_attempts',
        'reminder_last_attempt_at',
        'reminder_last_error',
        'repeat',
        'repeat_type',
        'repeat_interval_minutes',
        'repeat_until',
        'recurrence_key',
        'reminder_channels',
        'meta',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'all_day' => 'boolean',
        'reminder_at' => 'datetime',
        'next_reminder_at' => 'datetime',
        'reminder_sent' => 'boolean',
        'reminder_sent_at' => 'datetime',
        'reminder_call_enabled' => 'boolean',
        'reminder_attempts' => 'integer',
        'reminder_last_attempt_at' => 'datetime',
        'repeat' => 'boolean',
        'repeat_until' => 'datetime',
        'repeat_interval_minutes' => 'integer',
        'reminder_channels' => 'array',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(CalendarEventReminder::class, 'calendar_event_id');
    }

    public function isRemindable(): bool
    {
        return !in_array($this->status, ['paused', 'scheduled', 'cancelled'], true);
    }

    public function scopeForRange(Builder $query, $start, $end): Builder
    {
        return $query->where(function (Builder $q) use ($start, $end) {
            $q->whereBetween('start_at', [$start, $end])
                ->orWhereBetween('end_at', [$start, $end])
                ->orWhere(function (Builder $q) use ($start, $end) {
                    $q->where('start_at', '<=', $start)
                        ->where(function (Builder $q) use ($end) {
                            $q->whereNull('end_at')
                                ->orWhere('end_at', '>=', $end);
                        });
                });
        });
    }

    public function scopeDueReminders(Builder $query): Builder
    {
        return $query
            ->where('status', 'active')
            ->where(function (Builder $q) {
                $q->where(function (Builder $q) {
                    $q->whereNotNull('next_reminder_at')
                        ->where('next_reminder_at', '<=', now());
                })->orWhere(function (Builder $q) {
                    $q->whereNull('next_reminder_at')
                        ->where('reminder_at', '<=', now());
                });
            });
    }

    public function reminderPeer(): int|string|null
    {
        return $this->user?->telegram_id;
    }

    public function isReminderDue(): bool
    {
        if ($this->repeat) {
            return $this->next_reminder_at instanceof CarbonInterface
                && $this->next_reminder_at->lte(now());
        }

        return $this->reminder_at instanceof CarbonInterface
            && $this->reminder_at->lte(now())
            && !$this->reminder_sent;
    }

    public function toFullCalendarArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'start' => $this->start_at?->toIso8601String(),
            'end' => $this->end_at?->toIso8601String(),
            'allDay' => $this->all_day,
            'backgroundColor' => $this->color,
            'borderColor' => $this->color,
            'extendedProps' => [
                'description' => $this->description,
                'status' => $this->status,
                'timezone' => $this->timezone,
                'reminder_at' => $this->reminder_at?->toIso8601String(),
                'reminder_sent' => $this->reminder_sent,
                'reminder_call_enabled' => $this->reminder_call_enabled,
                'repeat' => $this->repeat,
                'repeat_type' => $this->repeat_type,
            ],
        ];
    }


    public function nextReminderDate(): ?CarbonInterface
    {
        if (!$this->repeat) {
            return null;
        }

        $base = $this->next_reminder_at ?: $this->reminder_at;

        if (!$base instanceof CarbonInterface) {
            return null;
        }

        $interval = max(1, (int) ($this->repeat_interval_minutes ?: 1));

        return match ($this->repeat_type) {
            'minute', 'minutes' => $base->copy()->addMinutes($interval),
            'hour', 'hours' => $base->copy()->addHours($interval),
            'day', 'daily' => $base->copy()->addDays($interval),
            'week', 'weekly' => $base->copy()->addWeeks($interval),
            'month', 'monthly' => $base->copy()->addMonthsNoOverflow($interval),
            'year', 'yearly' => $base->copy()->addYearsNoOverflow($interval),
            default => $base->copy()->addDays($interval),
        };
    }
    // CalendarEvent.php

    public function reminderAnchorAt(): ?CarbonInterface
    {
        $anchor = data_get($this->meta, 'reminder_anchor_at');

        if ($anchor) {
            return Carbon::parse($anchor);
        }

        if ($this->reminder_at instanceof CarbonInterface) {
            return $this->reminder_at;
        }

        return $this->start_at instanceof CarbonInterface ? $this->start_at : null;
    }

    public function nextRepeatDate(): ?CarbonInterface
    {
        if (!$this->repeat) {
            return null;
        }

        $base = $this->reminderAnchorAt();

        if (!$base instanceof CarbonInterface) {
            return null;
        }

        $interval = max(1, (int) ($this->repeat_interval_minutes ?: 1));

        return match ($this->repeat_type) {
            'minute', 'minutes' => $base->copy()->addMinutes($interval),
            'hour', 'hours' => $base->copy()->addHours($interval),
            'day', 'daily' => $base->copy()->addDays($interval),
            'week', 'weekly' => $base->copy()->addWeeks($interval),
            'month', 'monthly' => $base->copy()->addMonthsNoOverflow($interval),
            'year', 'yearly' => $base->copy()->addYearsNoOverflow($interval),
            default => $base->copy()->addDays($interval),
        };
    }
    public function scheduledMessages(): HasMany
    {
        return $this->hasMany(\App\Models\Messages\TelegramScheduledMessage::class, 'calendar_event_id');
    }
}