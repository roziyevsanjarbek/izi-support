@extends('layouts.app')

@section('content_wrapper_class', 'p-0 mx-0 max-w-none')
{{-- @section('content_wrapper_class', 'p-0 mx-0 max-w-none h-full min-h-0') --}}
@section('sidebar_default', 'collapsed')
@section('sidebar_hover_enabled', 'false')
@section('title', 'Calendar')

@php
$calendarView = request('view', 'month');
$selectedFilter = strtolower(str_replace(' ', '_', request('status', request('filter', 'pending'))));
$authUser = auth()->user();
$authUserId = $authUser?->id;
$authUserName = $authUser?->name ?? 'My calendar';

$selectedUserName = $authUserName;
if ($selectedUserId) {
    $selectedUserName = optional($users->firstWhere('id', $selectedUserId))->name ?? $selectedUserName;
}

$showBackToSelf = $authUserId && (int) $selectedUserId !== (int) $authUserId;
@endphp

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@section('content')
    <div class="calendar-app flex h-dvh flex-col overflow-hidden bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-50"
         data-view="{{ $calendarView }}"
         data-selected-date="{{ $selectedDate->toDateString() }}"
         data-selected-filter="{{ $selectedFilter }}">
        <script>
            window.__calendarInitialEvents = @json($events);
            window.__calendarInitialView = @json($calendarView);
            window.__calendarInitialDate = @json($selectedDate->toDateString());
            window.__calendarInitialFilter = @json($selectedFilter);
            window.__calendarSelectedUserId = @json($selectedUserId);
            window.__calendarSelectedUserName = @json($selectedUserName);
            window.__calendarAuthUserId = @json($authUserId);
            window.__calendarAuthUserName = @json($authUserName);
            window.__calendarCanManage = @json($canManage);
            window.__calendarUsers = @json($users);
            window.__calendarConfig = {
                routeIndex: @json(route('calendar.index')),
                routeEvents: @json(route('calendar.events.index')),
                timezone: @json(config('app.timezone')),
            };
        </script>

        @vite('resources/js/calendar/calendar-core.js')

        <div id="calendarToolbar" class="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
            <div class="flex h-14 items-center justify-between gap-3 px-4 md:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <button id="openSidebarButton" type="button" class="grid h-10 w-10 place-items-center rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 md:hidden">☰</button>
                    <div class="min-w-0">
                        <div id="rangeLabel" class="truncate text-[18px] font-semibold tracking-tight text-slate-900 dark:text-slate-50 md:text-[22px]">{{ $rangeLabel }}</div>
                        <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400 md:text-sm">{{ ucfirst($calendarView) }} view</div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    @if(auth()->user()?->isSuperAdmin())
                        <div class="flex items-center gap-2">
                            <div id="usersSelectWrap" class="relative">
                                <button id="usersSelectButton" type="button" class="inline-flex min-w-[220px] items-center justify-between gap-3 rounded-full border border-slate-200 bg-white px-4 py-2 text-left text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200 dark:hover:bg-slate-900">
                                    <span class="truncate" id="usersSelectValue">{{ $selectedUserName }}</span>
                                    <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4 shrink-0">
                                        <path d="M5 7l5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </button>

                                <div id="usersSelectMenu" class="hidden absolute right-0 top-full z-50 mt-2 w-[360px] rounded-2xl border border-slate-200 bg-white p-3 shadow-xl dark:border-slate-800 dark:bg-slate-950">
                                    <input id="usersSelectSearch" type="text" placeholder="Search user..." class="mb-3 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-slate-400 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100">
                                    <div class="max-h-[400px] space-y-1 overflow-y-auto pr-1">
                                        @foreach($users as $user)
                                            <button type="button"
                                                data-user-option
                                                data-user-name="{{ $user->name }}"
                                                data-user-url="{{ route('calendar.index', ['view' => $calendarView, 'date' => $selectedDate->toDateString(), 'user_id' => $user->id, 'filter' => $selectedFilter]) }}"
                                                class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-left text-sm transition hover:bg-slate-100 dark:hover:bg-slate-800 {{ (int) $selectedUserId === (int) $user->id ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900' : 'text-slate-700 dark:text-slate-200' }}">
                                                <span class="truncate">{{ $user->name }}</span>
                                                <span class="text-[11px] opacity-70">#{{ $user->id }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            @if($showBackToSelf)
                                <button type="button" data-go-self class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-2 text-sm font-medium text-sky-700 hover:bg-sky-100 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-200 dark:hover:bg-sky-900/60">
                                    <span class="h-2 w-2 rounded-full bg-sky-500"></span>
                                    {{ $authUserName }}
                                </button>
                            @endif
                        </div>
                    @endif

                    @if(!$selectedDate->isSameDay(now()))
                        <button type="button" data-jump-today class="rounded-full bg-sky-500 px-4 py-2 text-sm font-medium text-white hover:bg-sky-600">Today</button>
                    @endif
                    <button type="button" data-nav="prev" class="grid h-10 w-10 place-items-center rounded-full bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">←</button>
                    <button type="button" data-nav="next" class="grid h-10 w-10 place-items-center rounded-full bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">→</button>

                    <details class="relative">
                        <summary class="inline-flex list-none cursor-pointer items-center gap-2 rounded-full bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                            <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4"><path d="M5 7l5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
                            <span>{{ ucfirst($calendarView) }}</span>
                        </summary>
                        <div class="absolute right-0 z-30 mt-2 w-44 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl dark:border-slate-800 dark:bg-slate-950">
                            <button type="button" data-view-switch="day" class="block w-full rounded-xl px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800">Day</button>
                            <button type="button" data-view-switch="week" class="block w-full rounded-xl px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800">Week</button>
                            <button type="button" data-view-switch="month" class="block w-full rounded-xl px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800">Month</button>
                        </div>
                    </details>

                    <button id="newEventButton" type="button" class="inline-flex rounded-full bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">Create</button>
                </div>
            </div>

            <div class="border-t border-slate-200 px-4 py-3 dark:border-slate-800 md:hidden">
                <div class="flex items-center gap-2">
                    <select id="statusFilterMobile" class="rounded-full border border-slate-200 bg-white px-3 py-2 text-sm outline-none dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100">
                        <option value="completed">Completed</option>
                        <option value="not_completed">Not Completed</option>
                        <option value="pending">Pending</option>
                    </select>
                    <button id="newEventButtonMobile" type="button" class="grid h-10 w-10 place-items-center rounded-full bg-slate-900 text-white shadow-sm dark:bg-slate-100 dark:text-slate-900">+</button>
                </div>
            </div>
        </div>

        <div class="flex min-h-0 flex-1 overflow-hidden">
            <aside id="calendarSidebar" class="fixed inset-y-0 left-0 z-[80] w-80 -translate-x-full overflow-hidden border-r border-slate-200 bg-white py-4 transition-transform duration-200 ease-out dark:border-slate-800 dark:bg-slate-950 md:static md:z-auto md:w-80 md:translate-x-0 md:overflow-hidden">
    <div class="px-4 pt-2">
        <div class="flex items-center justify-between gap-2">
            <div class="text-sm font-semibold text-slate-900 dark:text-slate-50">Calendar</div>
            @if($canManage)
                <button id="newEventButtonSidebar" type="button" class="grid h-10 w-10 place-items-center rounded-full bg-slate-900 text-white shadow-sm transition hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                    <span class="text-lg leading-none">+</span>
                </button>
            @endif
        </div>
    </div>

    <div class="px-4 pt-4">
        <div class="flex items-center gap-1.5">
            <button type="button" data-filter="pending" class="rounded-full border px-3 py-1.5 text-xs font-medium transition border-sky-200 bg-sky-50 text-sky-700">Pending</button>
            <button type="button" data-filter="completed" class="rounded-full border px-3 py-1.5 text-xs font-medium transition border-emerald-200 bg-emerald-50 text-emerald-700">Completed</button>
            <button type="button" data-filter="not_completed" class="rounded-full border px-3 py-1.5 text-xs font-medium transition border-rose-200 bg-rose-50 text-rose-700">Not Completed</button>
        </div>
    </div>

    <div class="px-4 pt-4">
        <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Event list</div>
        <div id="sidebarEventList" class="mt-2 max-h-[calc(100dvh-210px)] overflow-y-auto pr-1 space-y-2"></div>
    </div>
</aside>

            <div id="sidebarBackdrop" class="fixed inset-0 z-[70] hidden bg-slate-950/50 backdrop-blur-sm md:hidden"></div>

            <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                <div id="calendarWeekHeader" class="{{ $calendarView === 'month' ? 'hidden' : '' }} border-b border-slate-200 bg-white px-4 py-3 dark:border-slate-800 dark:bg-slate-950 md:px-6">
                    <div class="calendar-grid text-sm">
                        <div></div>
                        @foreach ($days as $day)
                            <div class="flex flex-col items-center justify-center gap-1">
                                <div class="text-[12px] font-semibold uppercase text-slate-700 dark:text-slate-200 md:text-[13px]">
                                    {{ $day['short'] }}
                                    <span class="ml-1 inline-flex h-6 w-6 items-center justify-center rounded-full {{ $day['is_today'] ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900' : 'text-slate-800 dark:text-slate-100' }} text-[12px] font-semibold">
                                        {{ $day['num'] }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div id="calendarWeekBody" class="{{ $calendarView === 'month' ? 'hidden' : '' }} relative min-h-0 flex-1 overflow-hidden">
                    <div id="calendarScroll" class="calendar-scroll h-full">
                        <div id="calendarGrid" class="calendar-grid relative min-h-[980px] min-w-[1100px]">
                            <div class="border-r border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
                                <div class="h-[32px] border-b border-slate-200 dark:border-slate-800"></div>
                                @foreach ($hours as $hour)
                                    <div class="calendar-row relative border-b border-slate-100 px-2 text-[12px] text-slate-500 dark:border-slate-800/60 dark:text-slate-400">
                                        <div class="absolute -top-2 left-2">{{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}:00</div>
                                    </div>
                                @endforeach
                            </div>

                            @foreach ($days as $day)
                                <div class="relative border-r border-slate-100 dark:border-slate-800" data-day-column data-date="{{ $day['date'] }}">
                                    <div class="h-[32px] border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950"></div>
                                    <div class="calendar-day-body">
                                        @foreach ($hours as $hour)
                                            <div class="calendar-row cursor-pointer border-b border-slate-100 hover:bg-slate-50 dark:border-slate-800/60 dark:hover:bg-slate-900/40" data-slot data-date="{{ $day['date'] }}" data-hour="{{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}"></div>
                                        @endforeach
                                        <div class="absolute inset-0 pointer-events-none js-event-layer"></div>
                                    </div>
                                </div>
                            @endforeach

                            <div id="timeLine" class="absolute left-[72px] right-0 hidden h-px bg-sky-400/80 z-20"></div>
                            <div id="timeDot" class="absolute hidden h-2.5 w-2.5 -translate-x-1/2 -translate-y-1/2 rounded-full bg-sky-500 z-20"></div>
                        </div>
                    </div>
                </div>

                <div id="calendarMonthBody" class="{{ $calendarView === 'month' ? '' : 'hidden' }} flex-1 min-h-0 overflow-hidden px-4 py-4 md:px-6">
                    <div class="h-full overflow-hidden rounded-3xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
                        <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50 text-center text-xs font-semibold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-900/40 dark:text-slate-400">
                            <div class="py-3">Mo</div><div class="py-3">Tu</div><div class="py-3">We</div><div class="py-3">Th</div><div class="py-3">Fr</div><div class="py-3">Sa</div><div class="py-3">Su</div>
                        </div>

                        <div class="grid h-[calc(100%-41px)] grid-rows-6">
                            @foreach ($monthGrid as $week)
                                <div class="grid grid-cols-7 border-b border-slate-100 last:border-b-0 dark:border-slate-800/60">
                                    @foreach ($week as $cell)
                                        <button type="button" data-month-cell data-date="{{ $cell['date'] }}" class="month-cell border-r border-slate-100 p-2 text-left transition last:border-r-0 hover:bg-slate-50 dark:border-slate-800/60 dark:hover:bg-slate-900/40 {{ $cell['in_month'] ? 'text-slate-900 dark:text-slate-100' : 'text-slate-300 dark:text-slate-700' }} {{ $cell['is_today'] ? 'bg-sky-50/60 dark:bg-sky-500/10' : '' }}">
                                            <div class="flex items-center justify-between">
                                                <div class="inline-flex h-7 w-7 items-center justify-center rounded-full {{ $cell['is_today'] ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900' : '' }} text-sm font-semibold">{{ $cell['day'] }}</div>
                                            </div>
                                            <div class="mt-2 space-y-1 overflow-y-auto text-[11px]" style="max-height:80px" data-month-events="{{ $cell['date'] }}"></div>
                                        </button>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include('pages.calendar.partials.event-modal')
    </div>

    @include('pages.calendar.partials.style')

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    @endpush
@endsection
