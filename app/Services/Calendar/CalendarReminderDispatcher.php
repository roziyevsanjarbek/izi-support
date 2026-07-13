<?php

namespace App\Services\Calendar;

use App\Models\Calendar\CalendarEvent;
use App\Models\Calendar\CalendarEventReminder;
use App\Models\Messages\TelegramScheduledMessage;
use App\Models\Telegram\Madeline\TelegramAccount;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CalendarReminderDispatcher
{
    private const MAX_ATTEMPTS = 1;

    public function dispatchDue(): void
    {
        $account = TelegramAccount::query()
            ->where('is_authorized', true)
            ->first();

        if (! $account) {
            Log::warning('calendar.reminder.dispatcher.no_account', [
                'reason' => 'authorized telegram account not found',
            ]);
            return;
        }

        $events = CalendarEvent::query()
            ->with('user')
            ->dueReminders()
            ->orderByRaw('COALESCE(next_reminder_at, reminder_at) ASC')
            ->limit(100)
            ->get();

        foreach ($events as $event) {
            try {
                $this->processEvent($event, $account);
            } catch (Throwable $e) {
                Log::error('calendar.reminder.dispatcher.event_failed', [
                    'event_id' => $event->id,
                    'user_id' => $event->user_id,
                    'title' => $event->title,
                    'status' => $event->status,
                    'repeat' => (bool) $event->repeat,
                    'reminder_at' => $event->reminder_at?->toDateTimeString(),
                    'next_reminder_at' => $event->next_reminder_at?->toDateTimeString(),
                    'reminder_attempts' => (int) ($event->reminder_attempts ?? 0),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('calendar.reminder.dispatcher.finished', [
            'events_processed' => $events->count(),
        ]);
    }

    private function processEvent(CalendarEvent $event, TelegramAccount $account): void
    {
        Log::info('calendar.reminder.event.inspect', [
            'event_id' => $event->id,
            'user_id' => $event->user_id,
            'title' => $event->title,
            'status' => $event->status,
            'repeat' => (bool) $event->repeat,
            'reminder_at' => $event->reminder_at?->toDateTimeString(),
            'next_reminder_at' => $event->next_reminder_at?->toDateTimeString(),
            'reminder_sent' => (bool) $event->reminder_sent,
            'reminder_attempts' => (int) ($event->reminder_attempts ?? 0),
        ]);

        if (! $event->isRemindable()) {
            Log::info('calendar.reminder.event.skipped', [
                'event_id' => $event->id,
                'reason' => 'event_not_remindable',
                'status' => $event->status,
            ]);
            return;
        }

        $reminder = $this->resolveReminder($event);

        if (! $reminder) {
            Log::info('calendar.reminder.event.skipped', [
                'event_id' => $event->id,
                'reason' => 'reminder_not_resolved',
            ]);
            return;
        }

        $dueAt = $this->resolveDueAt($event, $reminder);

        if (! $dueAt instanceof CarbonInterface) {
            Log::info('calendar.reminder.event.skipped', [
                'event_id' => $event->id,
                'reminder_id' => $reminder->id,
                'reason' => 'due_at_missing',
            ]);
            return;
        }

        if (! $this->isWithinAllowedWindow($event, $dueAt)) {
            $this->markCurrentOccurrenceDone(
                $event,
                $reminder,
                'outside_event_window'
            );
            return;
        }

        $peer = $event->reminderPeer();

        if (! $peer) {
            $this->markCurrentOccurrenceDone(
                $event,
                $reminder,
                'telegram_id_missing'
            );
            return;
        }

        DB::transaction(function () use ($event, $reminder, $account, $peer) {
            $message = $this->buildReminderMessage($event, $reminder);

            TelegramScheduledMessage::create([
                'telegram_account_id' => $account->id,
                'calendar_event_id' => $event->id,
                'calendar_event_reminder_id' => $reminder->id,
                'peer' => $peer,
                'message' => $message,
                'need_call' => true,
                'send_at' => now(),
                'send_before_at' => $event->end_at instanceof CarbonInterface
                    ? $event->end_at->copy()->addMinutes(30)
                    : null,
                'status' => 'pending',
            ]);

            $this->completeCurrentOccurrence($event, $reminder);

            if ($event->repeat) {
                $this->cloneNextRepeatEvent($event);
            }
        });

        Log::info('calendar.reminder.event.queued', [
            'event_id' => $event->id,
            'reminder_id' => $reminder->id,
            'user_id' => $event->user_id,
            'title' => $event->title,
            'peer' => $peer,
            'repeat' => (bool) $event->repeat,
        ]);
    }

    private function resolveReminder(CalendarEvent $event): ?CalendarEventReminder
    {
        return DB::transaction(function () use ($event) {
            $lockedEvent = CalendarEvent::query()
                ->with('user')
                ->lockForUpdate()
                ->find($event->id);

            if (! $lockedEvent) {
                return null;
            }

            $existing = $lockedEvent->reminders()
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($existing) {
                return $existing;
            }

            $scheduledAt = $lockedEvent->next_reminder_at instanceof CarbonInterface
                ? $lockedEvent->next_reminder_at
                : ($lockedEvent->reminder_at instanceof CarbonInterface ? $lockedEvent->reminder_at : null);

            if (! $scheduledAt) {
                return null;
            }

            return $lockedEvent->reminders()->create([
                'status' => 'pending',
                'attempts' => 0,
                'max_attempts' => self::MAX_ATTEMPTS,
                'interval_minutes' => 0,
                'next_send_at' => $scheduledAt,
                'need_call' => true,
                'channels' => $lockedEvent->reminder_channels ?: ['telegram'],
                'meta' => [
                    'source' => $lockedEvent->repeat
                        ? 'calendar_events.next_reminder_at'
                        : 'calendar_events.reminder_at',
                ],
            ]);
        });
    }

    private function resolveDueAt(CalendarEvent $event, CalendarEventReminder $reminder): ?CarbonInterface
    {
        if ($reminder->next_send_at instanceof CarbonInterface) {
            return $reminder->next_send_at;
        }

        if ($event->next_reminder_at instanceof CarbonInterface) {
            return $event->next_reminder_at;
        }

        return $event->reminder_at instanceof CarbonInterface
            ? $event->reminder_at
            : null;
    }

    private function isWithinAllowedWindow(CalendarEvent $event, CarbonInterface $dueAt): bool
    {
        $startAt = $event->start_at instanceof CarbonInterface ? $event->start_at : null;
        $endAt = $event->end_at instanceof CarbonInterface ? $event->end_at : null;

        if ($startAt && $dueAt->lt($startAt)) {
            return false;
        }

        if ($endAt && $dueAt->gt($endAt)) {
            return false;
        }

        return true;
    }

    private function markCurrentOccurrenceDone(
        CalendarEvent $event,
        CalendarEventReminder $reminder,
        string $error
    ): void {
        DB::transaction(function () use ($event, $reminder, $error) {
            $reminder->update([
                'status' => 'done',
                'attempts' => 1,
                'last_sent_at' => now(),
                'next_send_at' => null,
                'done_at' => now(),
                'last_error' => $error,
            ]);

            $event->update([
                'status' => 'sent',
                'reminder_sent' => true,
                'reminder_sent_at' => now(),
                'next_reminder_at' => null,
                'reminder_last_error' => $error,
                'reminder_last_attempt_at' => now(),
                'reminder_attempts' => 1,
            ]);
        });

        Log::warning('calendar.reminder.event.done_with_error', [
            'event_id' => $event->id,
            'reminder_id' => $reminder->id,
            'title' => $event->title,
            'error' => $error,
        ]);
    }

    private function completeCurrentOccurrence(
        CalendarEvent $event,
        CalendarEventReminder $reminder
    ): void {
        $reminder->update([
            'status' => 'done',
            'attempts' => 1,
            'last_sent_at' => now(),
            'next_send_at' => null,
            'done_at' => now(),
            'last_error' => null,
        ]);

        $event->update([
            'status' => 'sent',
            'reminder_sent' => true,
            'reminder_sent_at' => now(),
            'next_reminder_at' => null,
            'reminder_last_attempt_at' => now(),
            'reminder_last_error' => null,
            'reminder_attempts' => 1,
        ]);
    }

    private function cloneNextRepeatEvent(CalendarEvent $event): ?CalendarEvent
    {
        $nextStartAt = $event->nextRepeatDate($event->start_at ?: $event->reminder_at);

        if (! $nextStartAt instanceof CarbonInterface) {
            Log::warning('calendar.reminder.repeat.clone_skipped', [
                'event_id' => $event->id,
                'title' => $event->title,
                'reason' => 'next_start_not_resolved',
                'repeat_type' => $event->repeat_type,
            ]);

            return null;
        }

        if (
            $event->repeat_until instanceof CarbonInterface
            && $nextStartAt->gt($event->repeat_until)
        ) {
            Log::info('calendar.reminder.repeat.completed', [
                'event_id' => $event->id,
                'title' => $event->title,
                'repeat_until' => $event->repeat_until?->toDateTimeString(),
                'next_start_at' => $nextStartAt->toDateTimeString(),
            ]);

            return null;
        }

        $nextEndAt = $event->end_at instanceof CarbonInterface
            ? $event->nextRepeatDate($event->end_at)
            : null;

        $nextReminderAt = $event->reminder_at instanceof CarbonInterface
            ? $event->nextRepeatDate($event->reminder_at)
            : $nextStartAt->copy();

        $meta = $event->meta ?: [];
        $meta['cloned_from_event_id'] = $event->id;
        $meta['cloned_from_start_at'] = $event->start_at?->toDateTimeString();

        $clone = $event->replicate();
        $clone->fill([
            'status' => 'active',
            'start_at' => $nextStartAt,
            'end_at' => $nextEndAt,
            'reminder_at' => $nextReminderAt,
            'next_reminder_at' => $nextReminderAt,
            'reminder_sent' => false,
            'reminder_sent_at' => null,
            'reminder_attempts' => 0,
            'reminder_last_attempt_at' => null,
            'reminder_last_error' => null,
            'meta' => $meta,
        ]);

        $clone->save();

        Log::info('calendar.reminder.repeat.cloned', [
            'source_event_id' => $event->id,
            'new_event_id' => $clone->id,
            'title' => $clone->title,
            'start_at' => $clone->start_at?->toDateTimeString(),
            'end_at' => $clone->end_at?->toDateTimeString(),
            'reminder_at' => $clone->reminder_at?->toDateTimeString(),
            'repeat_type' => $clone->repeat_type,
        ]);

        return $clone;
    }

    private function buildReminderMessage(CalendarEvent $event, CalendarEventReminder $reminder): string
    {
        $start = $event->start_at?->format('Y-m-d H:i');
        $end = $event->end_at?->format('Y-m-d H:i');

        $text = "🔔 Calendar reminder\n";
        $text .= "Title: {$event->title}\n";

        if ($event->description) {
            $text .= "Description: {$event->description}\n";
        }

        if ($start) {
            $text .= "Start: {$start}\n";
        }

        if ($end) {
            $text .= "End: {$end}\n";
        }

        

        if ($event->timezone) {
            $text .= "Timezone: {$event->timezone}\n";
        }

        

        return trim($text);
    }
}