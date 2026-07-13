<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Calendar\CalendarService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CalendarIndexController extends Controller
{
    public function __construct(private CalendarService $calendarService)
    {
    }

    public function index(Request $request): View
    {
        $view = in_array($request->string('view')->toString(), ['day', 'week', 'month'], true)
            ? $request->string('view')->toString()
            : 'month';

        $selectedDate = Carbon::parse($request->get('date', now()->toDateString()))->startOfDay();
        $authUser = Auth::user();

        $selectedUserId = $this->calendarService->resolveSelectedUserId($request);
        $canManage = $this->calendarService->canManageCalendar($selectedUserId);

        $range = $this->calendarService->getRangeForView($selectedDate, $view);

        $events = $this->calendarService->loadEventsForRange(
            $range['start'],
            $range['end'],
            userId: $selectedUserId,
            q: $request->string('q')->toString() ?: null,
            status: $request->string('status')->toString() ?: null,
            filter: $request->string('filter')->toString() ?: null
        );

        $users = collect();

        if ($authUser?->isSuperAdmin()) {
            $users = User::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get();
        }

        return view('pages.calendar.index', [
            'view' => $view,
            'selectedDate' => $selectedDate,
            'days' => $this->calendarService->buildVisibleDays($selectedDate, $view),
            'hours' => range(0, 23),
            'events' => $events,
            'users' => $users,
            'selectedUserId' => $selectedUserId,
            'canManage' => $canManage,
            'monthGrid' => $this->calendarService->buildMonthGrid($selectedDate),
            'sidebarMonthGrid' => $this->calendarService->buildMonthGrid($selectedDate),
            'monthLabel' => $selectedDate->translatedFormat('F Y'),
            'rangeLabel' => $this->calendarService->buildRangeLabel($selectedDate, $view),
            'navPrevUrl' => route('calendar.index', [
                'view' => $view,
                'date' => $this->calendarService->navDate($selectedDate, $view, -1)->toDateString(),
                'user_id' => $selectedUserId,
            ]),
            'navNextUrl' => route('calendar.index', [
                'view' => $view,
                'date' => $this->calendarService->navDate($selectedDate, $view, 1)->toDateString(),
                'user_id' => $selectedUserId,
            ]),
        ]);
    }
}
