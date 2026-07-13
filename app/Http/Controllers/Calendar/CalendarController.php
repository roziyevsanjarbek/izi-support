<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use App\Models\Calendar\CalendarEvent;
use App\Services\Calendar\CalendarService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function __construct(private CalendarService $calendarService){

    }
    
    public function events(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date'],
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
            'filter' => ['nullable', 'in:all,completed,not_completed,pending'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $selectedUserId = $this->calendarService->resolveSelectedUserId($request);

        $events = $this->calendarService->loadEventsForRange(
            $data['start'],
            $data['end'],
            userId: $selectedUserId,
            q: $data['q'] ?? null,
            status: $data['status'] ?? null,
            filter: $data['filter'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $events,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->calendarService->validateEvent($request);
        $userId = $this->calendarService->resolveSelectedUserId($request);

        $startAt = Carbon::parse($validated['start_at']);
        $reminderAt = $this->calendarService->buildReminderAt($validated);

        $event = CalendarEvent::create([
            'user_id' => $userId,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'start_at' => $startAt,
            'end_at' => !empty($validated['end_at']) ? Carbon::parse($validated['end_at']) : null,
            'all_day' => (bool) ($validated['all_day'] ?? false),
            'timezone' => config('app.timezone'),
            'color' => $validated['color'] ?? '#0051ff',
            'status' => $validated['status'] ?? 'active',
            'reminder_at' => $reminderAt,
            'next_reminder_at' => $reminderAt,
            'reminder_sent' => false,
            'reminder_sent_at' => null,
            'reminder_call_enabled' => (bool) ($validated['reminder_call_enabled'] ?? true),
            'reminder_attempts' => 0,
            'reminder_last_attempt_at' => null,
            'reminder_last_error' => null,
            'repeat' => (bool) ($validated['repeat'] ?? false),
            'repeat_type' => $this->calendarService->normalizeRepeatType($validated['repeat_type'] ?? null),
            'repeat_interval_minutes' => $validated['repeat_interval_minutes'] ?? null,
            'repeat_until' => !empty($validated['repeat_until']) ? Carbon::parse($validated['repeat_until']) : null,
            'meta' => $validated['meta'] ?? [],
            'reminder_channels' => Auth::user()?->telegram_id ? ['telegram'] : [],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Event created successfully.',
            'data' => $this->calendarService->toPayload($event->fresh()->load('reminders')),
        ], 201);
    }

    public function update(Request $request, CalendarEvent $event): JsonResponse
    {
        abort_unless($this->calendarService->canAccessEvent($event), 403);

        $validated = $this->calendarService->validateEvent($request);

        $startAt = Carbon::parse($validated['start_at']);
        $reminderAt = $this->calendarService->buildReminderAt($validated);

        $event->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'start_at' => $startAt,
            'end_at' => !empty($validated['end_at']) ? Carbon::parse($validated['end_at']) : null,
            'all_day' => (bool) ($validated['all_day'] ?? false),
            'timezone' => config('app.timezone'),
            'color' => $validated['color'] ?? '#0051ff',
            'status' => $validated['status'] ?? 'active',
            'reminder_at' => $reminderAt,
            'next_reminder_at' => $reminderAt,
            'reminder_sent' => false,
            'reminder_sent_at' => null,
            'reminder_call_enabled' => (bool) ($validated['reminder_call_enabled'] ?? true),
            'repeat' => (bool) ($validated['repeat'] ?? false),
            'repeat_type' => $this->calendarService->normalizeRepeatType($validated['repeat_type'] ?? null),
            'repeat_interval_minutes' => $validated['repeat_interval_minutes'] ?? null,
            'repeat_until' => !empty($validated['repeat_until']) ? Carbon::parse($validated['repeat_until']) : null,
            'meta' => $validated['meta'] ?? [],
            'reminder_channels' => Auth::user()?->telegram_id ? ['telegram'] : [],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Event updated successfully.',
            'data' => $this->calendarService->toPayload($event->fresh()->load('reminders')),
        ]);
    }

    public function destroy(CalendarEvent $event): JsonResponse
    {
        abort_unless($this->calendarService->canAccessEvent($event), 403);

        $event->delete();

        return response()->json([
            'success' => true,
            'message' => 'Event deleted successfully.',
        ]);
    }

    public function done(Request $request, CalendarEvent $event): JsonResponse
    {
        abort_unless($this->calendarService->canAccessEvent($event), 403);
        abort_unless($this->calendarService->canMarkDone($event), 422, 'Event cannot be marked done yet.');

        if ($event->repeat) {
            $nextOccurrenceAt = $event->nextRepeatDate();

            if (
                $event->repeat_until instanceof CarbonInterface
                && $nextOccurrenceAt instanceof CarbonInterface
                && $nextOccurrenceAt->gt($event->repeat_until)
            ) {
                $event->update([
                    'status' => 'done',
                    'reminder_sent' => true,
                    'reminder_sent_at' => now(),
                    'next_reminder_at' => null,
                    'reminder_attempts' => 0,
                    'reminder_last_attempt_at' => now(),
                    'reminder_last_error' => null,
                ]);

                $event->reminders()
                    ->whereIn('status', ['pending', 'retrying'])
                    ->update([
                        'status' => 'done',
                        'done_at' => now(),
                    ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Event marked as done.',
                    'data' => $this->calendarService->toPayload($event->fresh()->load('reminders')),
                ]);
            }

            $event->update([
                'status' => 'active',
                'reminder_sent' => false,
                'reminder_sent_at' => null,
                'next_reminder_at' => $nextOccurrenceAt,
                'reminder_attempts' => 0,
                'reminder_last_attempt_at' => null,
                'reminder_last_error' => null,
            ]);
        } else {
            $event->update([
                'status' => 'done',
                'reminder_sent' => true,
                'color' => '#22c55e',
                'reminder_sent_at' => now(),
                'next_reminder_at' => null,
                'reminder_last_error' => null,
                'reminder_last_attempt_at' => now(),
                'reminder_attempts' => 0,
            ]);

            $event->reminders()
                ->whereIn('status', ['pending', 'retrying'])
                ->update([
                    'status' => 'cancelled',
                    'done_at' => now(),
                    'last_error' => 'manually_done',
                ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Event marked as done.',
            'data' => $this->calendarService->toPayload($event->fresh()->load('reminders')),
        ]);
    }
    public function complete(Request $request, CalendarEvent $event): JsonResponse
    {
        abort_unless($this->calendarService->canAccessEvent($event), 403);
        $event->update([
            'status'=>'completed',
            'color'=>"#00fc5c"
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Event marked as completed.',
            'data' => $this->calendarService->toPayload($event->fresh()->load('reminders')),
        ]);
    }
    public function not_complete(Request $request, CalendarEvent $event): JsonResponse
    {
        abort_unless($this->calendarService->canAccessEvent($event), 403);
        $event->update([
            'status'=>'not_completed',
            'color'=>"#fc0000"
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Event marked as not completed.',
            'data' => $this->calendarService->toPayload($event->fresh()->load('reminders')),
        ]);
    }

    

    

    
}