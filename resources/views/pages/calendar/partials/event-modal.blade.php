<div id="eventModal" class="fixed inset-0 z-[99980] hidden items-center justify-center p-3 sm:p-6">
    <div id="eventModalBackdrop" class="absolute inset-0 drawer-backdrop"></div>

    <div class="relative w-full max-w-4xl overflow-hidden rounded-[30px] bg-white shadow-[0_30px_90px_rgba(15,23,42,.22)] ring-1 ring-slate-200/70 dark:bg-slate-950 dark:ring-slate-800 max-h-[92vh] transform-gpu" style="backface-visibility:hidden; will-change:transform;">
        <div class="flex items-start justify-between border-b border-slate-200/80 px-5 py-4 dark:border-slate-800 md:px-6">
            <div class="min-w-0">
                <div id="eventModalTitle" class="text-xl font-semibold tracking-tight text-slate-900 dark:text-slate-50">Create event</div>
                <div id="eventModalSubtitle" class="mt-1 text-sm text-slate-500 dark:text-slate-400">Fill in the details below.</div>
            </div>
            <button id="closeEventModal" class="grid h-11 w-11 shrink-0 place-items-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200" type="button" aria-label="Close">
                <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
        </div>

        <form id="eventForm" class="max-h-[calc(92vh-132px)] overflow-y-auto overflow-x-hidden px-5 py-5 sm:px-6">
            <input type="hidden" id="eventId" value="">
            <input type="hidden" id="eventMode" value="create">
            <input type="hidden" id="repeat_weekdays" value="[]">
            <input type="hidden" id="reminderId" value="">
            <input type="hidden" id="reminderStatus" value="">
            <input type="hidden" id="reminderCallStatus" value="">
            <input type="hidden" id="reminderColor" value="">

            @if(empty(auth()->user()?->telegram_id))
                <div class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-200">
                    <div class="font-semibold">Warning</div>
                    <div class="mt-1">Telegram is not connected. Notifications are currently sent only via Telegram.</div>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-4">
                <div class="space-y-4">
                    <div class="rounded-[24px] border border-slate-200 bg-gradient-to-br from-slate-50 to-white p-4 dark:border-slate-800 dark:from-slate-900/60 dark:to-slate-950 sm:p-5">
                        <div class="mb-4 flex items-center gap-2">
                            <span class="grid h-9 w-9 place-items-center rounded-2xl bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900">
                                <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4"><path d="M8 2v4M16 2v4M3 9h18M5 6h14a2 2 0 0 1 2 2v11a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V8a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                            <div>
                                <div class="text-sm font-semibold text-slate-900 dark:text-slate-50">Basic info</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">Title, description and status.</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Title</label>
                                <input id="title" type="text" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-2 focus:ring-slate-200 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100 dark:focus:border-slate-700 dark:focus:ring-slate-800" placeholder="Example: Team meeting">
                                <p class="mt-1 hidden text-xs text-rose-600" data-error-for="title"></p>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Description</label>
                                <textarea id="description" rows="5" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-2 focus:ring-slate-200 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100 dark:focus:border-slate-700 dark:focus:ring-slate-800" placeholder="Add notes, agenda, or details..."></textarea>
                                <p class="mt-1 hidden text-xs text-rose-600" data-error-for="description"></p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[24px] border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950 sm:p-5">
                        <div class="mb-4 flex items-center gap-2">
                            <span class="grid h-9 w-9 place-items-center rounded-2xl bg-sky-500 text-white">
                                <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4"><path d="M3 8h18M7 2v4M17 2v4M6 12h4m4 0h4m-8 4h4m-4 4h4M6 6h12a3 3 0 0 1 3 3v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V9a3 3 0 0 1 3-3Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                            <div>
                                <div class="text-sm font-semibold text-slate-900 dark:text-slate-50">Date & time</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">Choose start and end time.</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-900/40">
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Start</label>
                                <input id="start_at" type="text" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-3 text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-2 focus:ring-slate-200 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100 dark:focus:border-slate-700 dark:focus:ring-slate-800" placeholder="YYYY-MM-DD HH:MM">
                                <p class="mt-1 hidden text-xs text-rose-600" data-error-for="start_at"></p>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-900/40">
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">End</label>
                                <input id="end_at" type="text" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-3 text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-2 focus:ring-slate-200 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100 dark:focus:border-slate-700 dark:focus:ring-slate-800" placeholder="YYYY-MM-DD HH:MM">
                                <p class="mt-1 hidden text-xs text-rose-600" data-error-for="end_at"></p>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <label class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/40">
                                <div>
                                    <div class="text-sm font-medium text-slate-800 dark:text-slate-100">All day</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">Hide time and show full day.</div>
                                </div>
                                <input id="all_day" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400">
                            </label>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/40">
                                <div class="text-sm font-medium text-slate-800 dark:text-slate-100">Status</div>
                                <div class="mt-2">
                                    <select id="status" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100">
                                        <option value="active">Active</option>
                                        <option value="draft">Draft</option>
                                        <option value="sent">Sent</option>
                                    </select>
                                    <div id="statusReadonly" class="hidden rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200"></div>
                                </div>
                                <p class="mt-1 hidden text-xs text-rose-600" data-error-for="status"></p>
                            </div>
                        </div>
                    </div>

                    <div id="reminderInfoBox"></div>

                    <div class="rounded-[24px] border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950 sm:p-5">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-sm font-semibold text-slate-900 dark:text-slate-50">Repeat</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">Daily, weekly, monthly or yearly.</div>
                            </div>

                            <label class="inline-flex items-center gap-3 rounded-full bg-slate-100 px-3 py-2 text-sm font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                <span>Repeat</span>
                                <input id="repeat" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400">
                            </label>
                        </div>

                        <div id="repeatFields" class="mt-4 hidden space-y-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/40">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Repeat type</label>
                                <select id="repeat_type" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100 dark:focus:border-slate-700 dark:focus:ring-slate-800">
                                    <option value="day">Every day</option>
                                    <option value="week">Every week</option>
                                    <option value="month">Every month</option>
                                    <option value="year">Every year</option>
                                </select>
                            </div>

                            {{-- <div id="repeatWeekdaysWrap" class="hidden">
                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Weekdays</label>
                                <div class="grid grid-cols-7 gap-2">
                                    <button type="button" class="repeat-weekday rounded-xl border border-slate-200 bg-white px-2 py-2 text-xs font-semibold uppercase text-slate-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200" data-weekday="mon">Mo</button>
                                    <button type="button" class="repeat-weekday rounded-xl border border-slate-200 bg-white px-2 py-2 text-xs font-semibold uppercase text-slate-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200" data-weekday="tue">Tu</button>
                                    <button type="button" class="repeat-weekday rounded-xl border border-slate-200 bg-white px-2 py-2 text-xs font-semibold uppercase text-slate-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200" data-weekday="wed">We</button>
                                    <button type="button" class="repeat-weekday rounded-xl border border-slate-200 bg-white px-2 py-2 text-xs font-semibold uppercase text-slate-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200" data-weekday="thu">Th</button>
                                    <button type="button" class="repeat-weekday rounded-xl border border-slate-200 bg-white px-2 py-2 text-xs font-semibold uppercase text-slate-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200" data-weekday="fri">Fr</button>
                                    <button type="button" class="repeat-weekday rounded-xl border border-slate-200 bg-white px-2 py-2 text-xs font-semibold uppercase text-slate-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200" data-weekday="sat">Sa</button>
                                    <button type="button" class="repeat-weekday rounded-xl border border-slate-200 bg-white px-2 py-2 text-xs font-semibold uppercase text-slate-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200" data-weekday="sun">Su</button>
                                </div>
                            </div> --}}
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-5 flex items-center justify-between border-t border-slate-200/80 pt-4 dark:border-slate-800">
                <button id="deleteFromModal" type="button" class="hidden inline-flex items-center gap-2 rounded-full bg-rose-50 px-4 py-2 text-sm font-medium text-rose-700 hover:bg-rose-100 dark:bg-rose-950/50 dark:text-rose-200 dark:hover:bg-rose-950">
                    <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4"><path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2m-8 0h8m-9 0v14a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2V6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Delete
                </button>

                <div class="ml-auto flex flex-wrap items-center gap-2">
                    <button id="notCompleteEvent" type="button" class="hidden inline-flex h-11 w-11 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100 dark:border-rose-900 dark:bg-rose-950/50 dark:text-rose-200" title="Not complete" aria-label="Not complete">
                        <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
                    </button>

                    <button id="completeEvent" type="button" class="hidden inline-flex h-11 w-11 items-center justify-center rounded-full border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-200" title="Complete" aria-label="Complete">
                        <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4"><path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>

                    <button id="cancelEvent" type="button" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200 dark:hover:bg-slate-900">
                        <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Cancel
                    </button>

                    <button id="saveEvent" type="submit"
    class="hidden inline-flex items-center gap-2 rounded-full bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
    {{-- <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4">
        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
    </svg> --}}
    <span data-save-label>Create</span>
</button>
                </div>
            </div>
        </form>
    </div>
</div>
