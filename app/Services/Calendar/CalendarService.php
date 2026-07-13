<?php

namespace App\Services\Calendar;

use App\Models\Calendar\CalendarEvent;
use App\Models\Calendar\CalendarEventReminder;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class CalendarService
{
    public function resolveSelectedUserId(Request $request): int
    {
        $authUser = Auth::user();

        if ($authUser?->isSuperAdmin()) {
            return (int) $request->integer('user_id', $authUser->id);
        }

        return (int) ($authUser?->id ?? 0);
    }

    public function canManageCalendar(int $selectedUserId): bool
    {
        $authUser = Auth::user();

        if (! $authUser?->isSuperAdmin()) {
            return true;
        }

        return (int) $authUser->id === (int) $selectedUserId;
    }

    public function canAccessEvent(CalendarEvent $event): bool
    {
        $authUser = Auth::user();

        if ($authUser?->isSuperAdmin()) {
            $selectedUserId = (int) request()->integer('user_id', $event->user_id);
            return (int) $event->user_id === $selectedUserId;
        }

        return (int) $event->user_id === (int) ($authUser?->id ?? 0);
    }

    public function validateEvent(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'all_day' => ['nullable', 'boolean'],
            'color' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'in:planned,draft,new,active,paused,done,cancelled'],
            'reminder_at' => ['nullable', 'date'],
            'reminder_call_enabled' => ['nullable', 'boolean'],
            'repeat' => ['nullable', 'boolean'],
            'repeat_type' => ['nullable', 'string', 'max:50'],
            'repeat_interval_minutes' => ['nullable', 'integer', 'min:1'],
            'repeat_until' => ['nullable', 'date'],
            'meta' => ['nullable', 'array'],
        ]);
    }

    public function buildReminderAt(array $validated): Carbon
    {
        $startAt = Carbon::parse($validated['start_at']);

        return !empty($validated['all_day'])
            ? $startAt->copy()->setTime(9, 0)
            : $startAt->copy();
    }

    public function baseQuery(int $userId, ?string $q = null, ?string $status = null, ?string $filter = null): Builder
    {
        $query = CalendarEvent::query()
            ->with(['reminders' => function ($q) {
                $q->orderByDesc('occurrence_at')->orderByDesc('id');
            }])
            ->where('user_id', $userId);

        if ($q !== null && $q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        if ($status !== null && $status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }

        switch ($filter) {

    case 'completed':
        $query->where('status', 'completed');
        break;

    case 'not_completed':
        $query->where('status', 'not_completed');
        break;

    case 'pending':
        $query->whereNotIn('status', [
            'done',
            'not_completed',
        ]);
        break;

    case 'all':
    default:
        break;
}

        return $query;
    }

    public function loadEventsForRange($start, $end, int $userId, ?string $q = null, ?string $status = null, ?string $filter = null): Collection
    {
        $rangeStart = Carbon::parse($start)->startOfDay();
        $rangeEnd = Carbon::parse($end)->endOfDay();

        return (clone $this->baseQuery($userId, $q, $status, $filter))
            ->get()
            ->filter(fn (CalendarEvent $event) => $this->eventIntersectsRange($event, $rangeStart, $rangeEnd))
            ->map(fn (CalendarEvent $event) => $this->toPayload($event))
            ->sortBy('start_at')
            ->values();
    }

    public function eventIntersectsRange(CalendarEvent $event, Carbon $rangeStart, Carbon $rangeEnd): bool
    {
        $start = Carbon::parse($event->start_at);
        $end = $event->end_at ? Carbon::parse($event->end_at) : $start->copy();

        return $start->lessThanOrEqualTo($rangeEnd) && $end->greaterThanOrEqualTo($rangeStart);
    }

    public function pickReminder(CalendarEvent $event): ?CalendarEventReminder
    {
        if (! $event->relationLoaded('reminders') || $event->reminders->isEmpty()) {
            return null;
        }

        return $event->reminders
            ->sortByDesc(fn (CalendarEventReminder $r) => [
                optional($r->occurrence_at)?->timestamp ?? 0,
                $r->id,
            ])
            ->first();
    }

    public function toPayload(CalendarEvent $event): array
    {
        $reminder = $this->pickReminder($event);

        return [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'start_at' => $event->start_at?->toIso8601String(),
            'end_at' => $event->end_at?->toIso8601String(),
            'all_day' => (bool) $event->all_day,
            'timezone' => $event->timezone ?: config('app.timezone'),
            'color' => $event->color ?? '#0f172a',
            'status' => $event->status ?? 'draft',
            'repeat' => (bool) $event->repeat,
            'repeat_type' => $event->repeat_type,
            'reminder_id' => $reminder?->id,
            'reminder_at' => $reminder?->occurrence_at?->toIso8601String() ?? $event->reminder_at?->toIso8601String(),
            'reminder_status' => $reminder?->status,
            'reminder_call_status' => $reminder?->call_status,
            'reminder_color' => $event->color ?? '#0f172a',
            'reminders' => $event->reminders?->map(fn (CalendarEventReminder $r) => [
                'id' => $r->id,
                'status' => $r->status,
                'call_status' => $r->call_status,
                'attempts' => (int) $r->attempts,
                'next_send_at' => optional($r->next_send_at)?->toIso8601String(),
                'occurrence_at' => optional($r->occurrence_at)?->toIso8601String(),
                'need_call' => (bool) $r->need_call,
                'color' => $r->color,
            ])->values()->all() ?? [],
        ];
    }

    public function buildVisibleDays(Carbon $selectedDate, string $view): array
    {
        if ($view === 'day') {
            return [[
                'date' => $selectedDate->toDateString(),
                'short' => $selectedDate->format('D'),
                'num' => $selectedDate->format('j'),
                'is_today' => $selectedDate->isToday(),
            ]];
        }

        $weekStart = $selectedDate->copy()->startOfWeek(Carbon::MONDAY);
        $days = [];

        for ($i = 0; $i < 7; $i++) {
            $date = $weekStart->copy()->addDays($i);

            $days[] = [
                'date' => $date->toDateString(),
                'short' => $date->format('D'),
                'num' => $date->format('j'),
                'is_today' => $date->isToday(),
            ];
        }

        return $days;
    }

    public function buildMonthGrid(Carbon $date): array
    {
        $firstDay = $date->copy()->startOfMonth();
        $start = $firstDay->copy()->startOfWeek(Carbon::MONDAY);
        $grid = [];

        for ($week = 0; $week < 6; $week++) {
            $row = [];

            for ($day = 0; $day < 7; $day++) {
                $cell = $start->copy()->addDays($week * 7 + $day);

                $row[] = [
                    'date' => $cell->toDateString(),
                    'day' => $cell->day,
                    'in_month' => $cell->month === $date->month,
                    'is_today' => $cell->isToday(),
                ];
            }

            $grid[] = $row;
        }

        return $grid;
    }

    public function getRangeForView(Carbon $selectedDate, string $view): array
    {
        return match ($view) {
            'day' => [
                'start' => $selectedDate->copy()->startOfDay()->toDateTimeString(),
                'end' => $selectedDate->copy()->endOfDay()->toDateTimeString(),
            ],
            'month' => [
                'start' => $selectedDate->copy()->startOfMonth()->startOfDay()->toDateTimeString(),
                'end' => $selectedDate->copy()->endOfMonth()->endOfDay()->toDateTimeString(),
            ],
            default => [
                'start' => $selectedDate->copy()->startOfWeek(Carbon::MONDAY)->startOfDay()->toDateTimeString(),
                'end' => $selectedDate->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay()->toDateTimeString(),
            ],
        };
    }

    public function navDate(Carbon $selectedDate, string $view, int $step): Carbon
    {
        $date = $selectedDate->copy();

        return match ($view) {
            'day' => $date->addDays($step),
            'month' => $date->addMonthsNoOverflow($step),
            default => $date->addWeeks($step),
        };
    }

    public function buildRangeLabel(Carbon $selectedDate, string $view): string
    {
        return match ($view) {
            'day' => $selectedDate->translatedFormat('F j, Y'),
            'month' => $selectedDate->translatedFormat('F Y'),
            default => $selectedDate->copy()->startOfWeek(Carbon::MONDAY)->translatedFormat('F j')
                . ' – '
                . $selectedDate->copy()->endOfWeek(Carbon::SUNDAY)->translatedFormat('F j, Y'),
        };
    }
    public function canMarkDone(CalendarEvent $event): bool
    {
        $now = now();

        $notFuture = (
            $event->start_at instanceof CarbonInterface
            && $event->start_at->lessThanOrEqualTo($now)
        ) || $event->start_at === null;

        $reminderOk = (
            $event->next_reminder_at instanceof CarbonInterface
            && $event->next_reminder_at->lessThanOrEqualTo($now)
        ) || $event->next_reminder_at === null;

        return $notFuture && $reminderOk;
    }
    public function normalizeRepeatType(?string $repeatType): ?string
    {
        if ($repeatType === null || $repeatType === '') {
            return null;
        }

        $type = mb_strtolower(trim($repeatType));

        return match ($type) {
            'every minute', 'minute', 'minutes' => 'minute',
            'every hour', 'hour', 'hours' => 'hour',
            'every day', 'day', 'daily', 'everyday' => 'day',
            'every week', 'week', 'weekly' => 'week',
            'every month', 'month', 'monthly' => 'month',
            'every year', 'year', 'yearly' => 'year',
            default => $type,
        };
    }
}
