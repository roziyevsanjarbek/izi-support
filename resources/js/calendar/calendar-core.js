import './calendar-sidebar.js';
import {
    darkenColor,
    ensureDate,
    escapeHtml,
    formatTime,
    hexToRgba,
    localDate,
    normalizeDate,
    pad,
    startOfDay,
    endOfDay,
    todayISO,
    toLocalInputValue,
    weekdayKeyFromDate,
} from './calendar-utils.js';

(() => {
    if (window.__calendarCoreBooted) return;
    window.__calendarCoreBooted = true;

    const app = document.querySelector('.calendar-app');
    if (!app) return;

    const cfg = window.__calendarConfig || {};
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const requestedView = new URL(window.location.href).searchParams.get('view');
    const initialView = ['day', 'week', 'month'].includes(requestedView) ? requestedView : 'month';

    if (window.axios) {
        window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
        if (csrf) window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf;
    }

    const authUserId = Number(window.__calendarAuthUserId || 0) || null;
    const authUserName = window.__calendarAuthUserName || 'My calendar';

    function normalizeFilter(value) {
        return String(value || 'pending').trim().replace(/\s+/g, '_').toLowerCase();
    }

    const state = {
        view: initialView,
        selectedDate: normalizeDate(app?.dataset?.selectedDate || window.__calendarInitialDate || todayISO()),
        selectedUserId: Number(window.__calendarSelectedUserId || new URL(window.location.href).searchParams.get('user_id') || 0) || null,
        selectedUserName: window.__calendarSelectedUserName || '',
        canManage: !!window.__calendarCanManage,
        events: Array.isArray(window.__calendarInitialEvents) ? window.__calendarInitialEvents : [],
        visibleEvents: [],
        filters: {
            q: new URL(window.location.href).searchParams.get('q') || '',
            status: new URL(window.location.href).searchParams.get('status') || 'all',
        },
        sidebarFilter: normalizeFilter(window.__calendarInitialFilter || new URL(window.location.href).searchParams.get('filter') || 'pending'),
        current: null,
        navigating: false,
        saving: false,
        refreshTicket: 0,
        manualEndDirty: false,
        showOnly: false,
        selectedRepeatWeekdays: new Set(),
        monthWheelLock: false,
        _slotSuppressUntil: 0,
        drag: null,
    };

    const rowHeight = 44;
    const headerHeight = 32;

    let eventModal = null;
    let eventForm = null;
    let eventModalTitle = null;
    let eventModalSubtitle = null;
    let eventId = null;
    let eventMode = null;
    let statusReadonly = null;
    let reminderInfoBox = null;
    let sidebarEventList = null;
    let usersSelectButton = null;
    let usersSelectSearch = null;

    const fields = {
        title: null,
        description: null,
        start_at: null,
        end_at: null,
        all_day: null,
        status: null,
        repeat: null,
        repeatFields: null,
        repeat_type: null,
        repeatWeekdaysWrap: null,
        repeat_weekdays: null,
        saveEvent: null,
        deleteFromModal: null,
        completeEvent: null,
        notCompleteEvent: null,
        cancelEvent: null,
    };

    let startPicker = null;
    let endPicker = null;
    let searchTimer = null;
    let dragState = null;

    function cacheDom() {
        eventModal = document.getElementById('eventModal');
        eventForm = document.getElementById('eventForm');
        eventModalTitle = document.getElementById('eventModalTitle');
        eventModalSubtitle = document.getElementById('eventModalSubtitle');
        eventId = document.getElementById('eventId');
        eventMode = document.getElementById('eventMode');
        statusReadonly = document.getElementById('statusReadonly');
        reminderInfoBox = document.getElementById('reminderInfoBox');
        sidebarEventList = document.getElementById('sidebarEventList');
        usersSelectButton = document.getElementById('usersSelectButton');
        usersSelectSearch = document.getElementById('usersSelectSearch');

        fields.title = document.getElementById('title');
        fields.description = document.getElementById('description');
        fields.start_at = document.getElementById('start_at');
        fields.end_at = document.getElementById('end_at');
        fields.all_day = document.getElementById('all_day');
        fields.status = document.getElementById('status');
        fields.repeat = document.getElementById('repeat');
        fields.repeatFields = document.getElementById('repeatFields');
        fields.repeat_type = document.getElementById('repeat_type');
        fields.repeatWeekdaysWrap = document.getElementById('repeatWeekdaysWrap');
        fields.repeat_weekdays = document.getElementById('repeat_weekdays');
        fields.saveEvent = document.getElementById('saveEvent');
        fields.deleteFromModal = document.getElementById('deleteFromModal');
        fields.completeEvent = document.getElementById('completeEvent');
        fields.notCompleteEvent = document.getElementById('notCompleteEvent');
        fields.cancelEvent = document.getElementById('cancelEvent');
    }

    function toast(type, message) {
        return { type, message };
    }

    function clearErrors() {
        document.querySelectorAll('[data-error-for]').forEach((el) => {
            el.textContent = '';
            el.classList.add('hidden');
        });
    }

    function showErrors(errors = {}) {
        clearErrors();
        Object.entries(errors).forEach(([key, messages]) => {
            const el = document.querySelector(`[data-error-for="${key}"]`);
            if (!el) return;
            el.textContent = Array.isArray(messages) ? messages[0] : String(messages);
            el.classList.remove('hidden');
        });
    }

    function isEditableStatus(status) {
        return ['draft', 'active', 'planned', 'new', ].includes(String(status || '').toLowerCase());
    }

    function getOwnerId(event = null) {
        const eventOwnerId = Number(event?.user_id || 0) || null;
        if (eventOwnerId) return eventOwnerId;
        if (state.selectedUserId) return state.selectedUserId;
        return authUserId;
    }

    function canEditEvent(event = null) {
        const ownerId = getOwnerId(event);
        return !!ownerId && !!authUserId && Number(ownerId) === Number(authUserId);
    }

    function canShowCardActions(event = null) {
        return canEditEvent(event) && String(event?.status || '').toLowerCase() === 'sent' && !!event?.id;
    }

    function canShowCompletionButtons(event = null) {
        return canShowCardActions(event);
    }

    function setPickerDate(picker, value, trigger = false) {
        if (!picker) return;
        const date = ensureDate(value);
        if (!date) return;
        picker.setDate(date, trigger);
    }

    function applyAllDayPreset(baseDate) {
        const d = ensureDate(baseDate);
        if (!d) return;
        const start = new Date(d);
        start.setHours(0, 0, 0, 0);
        const end = new Date(d);
        end.setHours(23, 59, 0, 0);
        setPickerDate(startPicker, start, false);
        setPickerDate(endPicker, end, false);
    }

    function syncRepeatWeekdayButtons() {
        document.querySelectorAll('.repeat-weekday').forEach((btn) => {
            const active = state.selectedRepeatWeekdays?.has(btn.dataset.weekday);
            btn.classList.toggle('bg-slate-900', active);
            btn.classList.toggle('text-white', active);
            btn.classList.toggle('border-slate-900', active);
            btn.classList.toggle('dark:bg-slate-100', active);
            btn.classList.toggle('dark:text-slate-900', active);
            btn.classList.toggle('dark:border-slate-100', active);
        });
        if (fields.repeat_weekdays) fields.repeat_weekdays.value = JSON.stringify([...state.selectedRepeatWeekdays || []]);
    }

    function setRepeatWeekdays(list) {
        state.selectedRepeatWeekdays = new Set(Array.isArray(list) ? list.filter(Boolean).map(String) : []);
        syncRepeatWeekdayButtons();
    }

    function syncRepeatUI() {
        const enabled = !!fields.repeat?.checked;
        fields.repeatFields?.classList.toggle('hidden', !enabled);
        if (fields.repeat_type?.value === 'week' && enabled) fields.repeatWeekdaysWrap?.classList.remove('hidden');
        else fields.repeatWeekdaysWrap?.classList.add('hidden');
    }
    function canShowSaveButton(event = null) {
    if (!event) return true; // create mode
    return canEditEvent(event) && isEditableStatus(event.status);
}

    function resetModalState() {
        state.current = null;
        state.showOnly = false;
        state.manualEndDirty = false;
        state.selectedRepeatWeekdays = new Set();
        syncRepeatWeekdayButtons();
    }

    function syncReminderInfo(event = null) {
        if (!reminderInfoBox) return;

        const reminderId = event?.reminder_id ?? null;
        const st = String(event?.reminder_status || '').toLowerCase();
        const cl = String(event?.reminder_call_status || '').toLowerCase();

        if (!reminderId) {
            reminderInfoBox.innerHTML = `
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-500 dark:border-slate-800 dark:bg-slate-900/40 dark:text-slate-400">
                    No reminder attached to this event.
                </div>`;
            return;
        }

        const checkIcon = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5"><path d="M20 6 9 17l-5-5"/></svg>`;
        const crossIcon = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5"><path d="M18 6 6 18M6 6l12 12"/></svg>`;
        const phoneIcon = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.8 19.8 0 0 1 3.1 5.18 2 2 0 0 1 5.08 3h3a2 2 0 0 1 2 1.72c.12.86.32 1.7.59 2.51a2 2 0 0 1-.45 2.11L9 10a16 16 0 0 0 5 5l.66-.66a2 2 0 0 1 2.11-.45c.81.27 1.65.47 2.51.59A2 2 0 0 1 22 16.92z"/></svg>`;
        const dotIcon = `<svg viewBox="0 0 24 24" fill="currentColor" class="h-2 w-2"><circle cx="12" cy="12" r="4"/></svg>`;
        function chip(color, icon) { return `<span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full ${color} text-white shadow-sm">${icon}</span>`; }
        const stIcon = st === 'sent' ? chip('bg-emerald-600', checkIcon) : st === 'failed' ? chip('bg-rose-600', crossIcon) : chip('bg-slate-400', dotIcon);
        const clIcon = cl === 'called' ? chip('bg-emerald-600', phoneIcon) : (cl === 'not_called' || cl === 'failed') ? chip('bg-rose-600', phoneIcon) : chip('bg-slate-400', dotIcon);

        reminderInfoBox.innerHTML = `
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                <div class="mb-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Reminder #${escapeHtml(String(reminderId))}</div>
                <div class="space-y-2.5">
                    <div class="flex items-center gap-2.5">
                        <span class="w-12 shrink-0 text-xs text-slate-500 dark:text-slate-400">Status</span>
                        ${stIcon}
                        <span class="text-sm font-medium text-slate-800 dark:text-slate-200">${escapeHtml(st || '—')}</span>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <span class="w-12 shrink-0 text-xs text-slate-500 dark:text-slate-400">Call</span>
                        ${clIcon}
                        <span class="text-sm font-medium text-slate-800 dark:text-slate-200">${escapeHtml(cl || '—')}</span>
                    </div>
                </div>
            </div>`;
    }

    function setReadonlyMode(readonly, event = null) {
        state.showOnly = readonly;
        const inputs = [fields.title, fields.description, fields.start_at, fields.end_at, fields.all_day, fields.status, fields.repeat, fields.repeat_type];
        inputs.forEach((el) => { if (!el) return; if (readonly) el.setAttribute('disabled', 'disabled'); else el.removeAttribute('disabled'); });
        document.querySelectorAll('.repeat-weekday').forEach((btn) => { if (readonly) btn.setAttribute('disabled', 'disabled'); else btn.removeAttribute('disabled'); });

        if (readonly && event) {
            statusReadonly?.classList.remove('hidden');
            fields.status?.classList.add('hidden');
            if (statusReadonly) statusReadonly.textContent = String(event.status || 'draft').toUpperCase();
        } else {
            statusReadonly?.classList.add('hidden');
            fields.status?.classList.remove('hidden');
        }

        if (fields.saveEvent) {
    const show =
        !readonly &&
        (
            !event || canShowSaveButton(event)
        );

    fields.saveEvent.classList.toggle('hidden', !show);
}
        if (fields.deleteFromModal) fields.deleteFromModal.classList.toggle('hidden', !(event?.id && canEditEvent(event)));
        syncCompletionButtons(event);
        syncReminderInfo(event);
    }

    function syncAllDayFields() {
        const isAllDay = !!fields.all_day?.checked;
        [fields.start_at, fields.end_at].forEach((el) => {
            if (!el) return;
            el.disabled = isAllDay;
            el.classList.toggle('cursor-not-allowed', isAllDay);
            el.classList.toggle('bg-slate-100', isAllDay);
            el.classList.toggle('text-slate-500', isAllDay);
            el.classList.toggle('dark:bg-slate-900/70', isAllDay);
            el.classList.toggle('dark:text-slate-400', isAllDay);
        });
        if (startPicker) { startPicker.set('enableTime', !isAllDay); startPicker.set('dateFormat', isAllDay ? 'Y-m-d' : 'Y-m-d H:i'); }
        if (endPicker) { endPicker.set('enableTime', !isAllDay); endPicker.set('dateFormat', isAllDay ? 'Y-m-d' : 'Y-m-d H:i'); }
        const currentStart = startPicker?.selectedDates?.[0] || new Date();
        if (isAllDay) applyAllDayPreset(currentStart);
    }

    function buildEventPayload(event) {
        return {
            ...event,
            user_id: event?.user_id ?? null,
            reminder_id: event?.reminder_id ?? null,
            reminder_status: event?.reminder_status ?? null,
            reminder_call_status: event?.reminder_call_status ?? null,
            reminder_color: event?.reminder_color ?? event?.color ?? '#0f172a',
            occurrence_at: event?.occurrence_at ?? null,
        };
    }

    function applyEventToForm(event, fallbackSlot = null) {
        const normalized = event || {};
        if (fields.title) fields.title.value = normalized?.title ?? '';
        if (fields.description) fields.description.value = normalized?.description ?? '';
        if (fields.all_day) fields.all_day.checked = !!normalized?.all_day || !!fallbackSlot?.all_day;
        if (fields.status) fields.status.value = normalized?.status || 'active';
        if (fields.repeat) fields.repeat.checked = !!normalized?.repeat;
        if (fields.repeat_type) fields.repeat_type.value = normalized?.repeat_type || 'day';

        if (normalized?.start_at) {
            const start = ensureDate(normalized.start_at);
            const end = ensureDate(normalized.end_at) || (start ? new Date(start.getTime() + 60 * 60 * 1000) : null);
            setPickerDate(startPicker, start, false);
            setPickerDate(endPicker, end, false);
        } else if (fallbackSlot?.start_at) {
            const start = ensureDate(fallbackSlot.start_at);
            const end = ensureDate(fallbackSlot.end_at) || (start ? new Date(start.getTime() + 60 * 60 * 1000) : null);
            if (fallbackSlot.all_day) applyAllDayPreset(start);
            else { setPickerDate(startPicker, start, false); setPickerDate(endPicker, end, false); }
        } else {
            const now = new Date(); now.setSeconds(0, 0);
            const end = new Date(now.getTime() + 60 * 60 * 1000);
            if (fields.all_day?.checked) applyAllDayPreset(now);
            else { setPickerDate(startPicker, now, false); setPickerDate(endPicker, end, false); }
        }

        if (fields.repeat.checked && fields.repeat_type.value === 'week' && !state.selectedRepeatWeekdays.size) {
            const base = startPicker?.selectedDates?.[0] || normalized?.start_at || state.selectedDate;
            setRepeatWeekdays([weekdayKeyFromDate(base)]);
        }

        syncAllDayFields();
        syncRepeatUI();
        syncRepeatWeekdayButtons();
        syncReminderInfo(normalized);
    }

    function decorateModalActionButtons(mode = 'create') {
    if (fields.completeEvent && fields.completeEvent.dataset.iconified !== '1') {
        fields.completeEvent.innerHTML = `<svg viewBox="0 0 24 24" fill="none" class="h-4 w-4"><path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
        fields.completeEvent.dataset.iconified = '1';
        fields.completeEvent.title = 'Complete';
        fields.completeEvent.setAttribute('aria-label', 'Complete');
    }

    if (fields.notCompleteEvent && fields.notCompleteEvent.dataset.iconified !== '1') {
        fields.notCompleteEvent.innerHTML = `<svg viewBox="0 0 24 24" fill="none" class="h-4 w-4"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
        fields.notCompleteEvent.dataset.iconified = '1';
        fields.notCompleteEvent.title = 'Not complete';
        fields.notCompleteEvent.setAttribute('aria-label', 'Not complete');
    }

    if (fields.saveEvent) {
        const label = mode === 'edit' ? 'Update' : 'Create';
        fields.saveEvent.innerHTML = `
            <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4">
                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <span>${label}</span>
        `;
    }
}

    function openModal(mode = 'create', event = null, slot = null) {
        clearErrors();
        const hasEvent = !!event;
        const normalized = hasEvent ? buildEventPayload(event) : null;
        const isReadonly = mode === 'show' || (hasEvent && !isEditableStatus(normalized?.status)) || !state.canManage;

        if (!state.canManage && (mode === 'create' || mode === 'edit')) return;
        if (!eventModal) return;

        state.current = normalized;
        if (eventMode) eventMode.value = mode;
        if (eventId) eventId.value = normalized?.id ?? '';
        if (eventModalTitle) eventModalTitle.textContent = isReadonly ? 'Event details' : (mode === 'edit' ? 'Edit event' : 'Create event');
        if (eventModalSubtitle) eventModalSubtitle.textContent = isReadonly ? 'View event information.' : (mode === 'edit' ? 'Update event details.' : 'Fill in the details below.');

        decorateModalActionButtons(mode);
        applyEventToForm(normalized, slot);
        if (mode === 'create' && fields.status) fields.status.value = 'active';
        setReadonlyMode(isReadonly, normalized);
        syncRepeatUI();

        eventModal.classList.remove('hidden');
        eventModal.classList.add('flex');
    }

    function closeModal() { if (!eventModal) return; eventModal.classList.add('hidden'); eventModal.classList.remove('flex'); resetModalState(); }

    function payloadFromForm() {
        return {
            title: fields.title?.value.trim() || '',
            description: fields.description?.value.trim() || '',
            start_at: startPicker ? toLocalInputValue(startPicker.selectedDates[0]) : '',
            end_at: endPicker ? toLocalInputValue(endPicker.selectedDates[0]) : '',
            all_day: !!fields.all_day?.checked,
            status: fields.status?.value || 'active',
            color: '#0051ff',
            repeat: !!fields.repeat?.checked,
            repeat_type: fields.repeat?.checked ? fields.repeat_type?.value : null,
            reminder_id: state.current?.reminder_id || null,
            occurrence_at: state.current?.occurrence_at || null,
            meta: { source: 'calendar', repeat_weekdays: [...state.selectedRepeatWeekdays] },
        };
    }

    function dayIntersection(dayDate, event) {
        const start = ensureDate(event.start_at);
        const end = ensureDate(event.end_at || event.start_at) || start;
        if (!start || !end) return null;
        const dayStart = startOfDay(dayDate);
        const dayEnd = endOfDay(dayDate);
        if (!dayStart || !dayEnd) return null;
        if (end < dayStart || start > dayEnd) return null;
        return { start: start > dayStart ? start : dayStart, end: end < dayEnd ? end : dayEnd };
    }

    function clearRenderedEvents() {
        document.querySelectorAll('[data-day-column] .js-event-layer').forEach((layer) => (layer.innerHTML = ''));
        document.querySelectorAll('[data-month-events]').forEach((layer) => (layer.innerHTML = ''));
        if (sidebarEventList) sidebarEventList.innerHTML = '';
    }

    function matchesMainCalendar(event) { return true; }

    function matchesSidebarFilter(event) {
        const filter = String(state.sidebarFilter || 'pending').toLowerCase();
        const status = String(event?.status || '').toLowerCase();
        if (filter === 'completed') return status === 'completed';
        if (filter === 'not_completed') return status === 'not_completed';
        if (filter === 'pending') return status !== 'completed' && status !== 'not_completed';
        return true;
    }

    function getRange() {
        const date = ensureDate(`${state.selectedDate}T00:00:00`) || new Date();
        if (state.view === 'day') return { start: startOfDay(date), end: endOfDay(date) };
        if (state.view === 'month') {
            return { start: new Date(date.getFullYear(), date.getMonth(), 1), end: new Date(date.getFullYear(), date.getMonth() + 1, 0, 23, 59, 59, 999) };
        }
        const day = date.getDay() === 0 ? 7 : date.getDay();
        const mondayOffset = day - 1;
        const start = new Date(date);
        start.setDate(date.getDate() - mondayOffset);
        start.setHours(0, 0, 0, 0);
        const end = new Date(start);
        end.setDate(start.getDate() + 6);
        end.setHours(23, 59, 59, 999);
        return { start, end };
    }

    function buildUrl(view, date) {
        const nextUrl = new URL(cfg.routeIndex || window.location.pathname, window.location.origin);
        nextUrl.searchParams.set('view', view);
        nextUrl.searchParams.set('date', normalizeDate(date));
        if (state.filters.status && state.filters.status !== 'all') nextUrl.searchParams.set('status', state.filters.status);
        if (state.filters.q) nextUrl.searchParams.set('q', state.filters.q);
        if (state.selectedUserId) nextUrl.searchParams.set('user_id', String(state.selectedUserId));
        return nextUrl.toString();
    }

    function replaceSection(doc, id) {
        const current = document.getElementById(id);
        const next = doc.getElementById(id);
        if (current && next) current.outerHTML = next.outerHTML;
    }

    function syncStateFromDocument(doc) {
        const nextApp = doc.querySelector('.calendar-app');
        if (!nextApp) return;
        state.view = nextApp.dataset.view || state.view;
        state.selectedDate = normalizeDate(nextApp.dataset.selectedDate || state.selectedDate);
        if (app) {
            app.dataset.view = state.view;
            app.dataset.selectedDate = state.selectedDate;
        }
    }

    function buildDayMap(visible) {
        const dayMap = new Map();
        visible.forEach((event) => {
            const start = ensureDate(event.start_at);
            const end = ensureDate(event.end_at || event.start_at) || start;
            if (!start || !end) return;
            let cursor = new Date(startOfDay(start));
            const last = endOfDay(end);
            while (cursor <= last) {
                const key = localDate(cursor);
                const hit = dayIntersection(cursor, event);
                if (hit) {
                    if (!dayMap.has(key)) dayMap.set(key, []);
                    dayMap.get(key).push({ ...event, _segmentStart: hit.start, _segmentEnd: hit.end });
                }
                cursor.setDate(cursor.getDate() + 1);
            }
        });
        return dayMap;
    }

    function renderEventActions(event, compact = false) {
        if (!canShowCardActions(event)) return '';
        return `
            <div class="calendar-event-actions ${compact ? 'calendar-event-actions--compact' : ''}">
                <button type="button" data-event-action="not-complete" data-event-id="${event.id}" class="calendar-event-action-btn calendar-event-action-btn--not-complete" title="Not complete" aria-label="Not complete">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <button type="button" data-event-action="complete" data-event-id="${event.id}" class="calendar-event-action-btn calendar-event-action-btn--complete" title="Complete" aria-label="Complete">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>
        `;
    }

    function buildEventSegment(event, dayKey, { top = 4, height = 36, laneCount = 1, laneIndex = 0 } = {}) {
        const card = document.createElement('button');
        card.type = 'button';
        card.className = 'event-card event-lane text-left relative overflow-hidden';
        card.style.top = `${top}px`;
        card.style.height = `${height}px`;
        card.style.background = hexToRgba(event.color || '#0f172a', 0.10);
        card.style.borderColor = hexToRgba(event.color || '#0f172a', 0.22);
        card.style.color = darkenColor(event.color || '#0f172a', 0.10);
        card.style.setProperty('--lane-count', String(laneCount));
        card.style.setProperty('--lane-index', String(laneIndex));
        card.dataset.eventId = event.id;
        if (event.reminder_id) card.dataset.reminderId = event.reminder_id;
        if (event.occurrence_at) card.dataset.occurrenceAt = event.occurrence_at;

        card.innerHTML = `
            <div class="calendar-event-card-inner min-w-0">
                <div class="min-w-0">
                    <div class="event-title truncate">${escapeHtml(event.title || 'Untitled')}</div>
                    <div class="text-[11px] opacity-80 truncate">
                        ${event.all_day ? 'All day' : `${formatTime(event._segmentStart || event.start_at)}${event._segmentEnd ? ' – ' + formatTime(event._segmentEnd) : ''}`}
                    </div>
                </div>
            </div>
            ${renderEventActions(event, false)}
        `;
        return card;
    }

    function renderMonthBadge(event) {
        const badge = document.createElement('button');
        badge.type = 'button';
        badge.className = 'month-event-badge w-full rounded-lg px-2 py-1 text-left text-[11px] font-medium truncate cursor-pointer relative overflow-hidden';
        badge.style.background = hexToRgba(event.color || '#0f172a', 0.11);
        badge.style.color = darkenColor(event.color || '#0f172a', 0.08);
        badge.dataset.eventId = event.id;

        if (event.reminder_id) badge.dataset.reminderId = event.reminder_id;
        if (event.occurrence_at) badge.dataset.occurrenceAt = event.occurrence_at;

        badge.innerHTML = `
        <div class="flex items-center gap-1 min-w-0">
            <span class="h-2 w-2 shrink-0 rounded-full" style="background:${event.color || '#0f172a'}"></span>
            <span class="truncate">${escapeHtml(event.title || 'Untitled')}</span>
        </div>
    `;

        return badge;
    }

    function renderSidebarEvent(event) {
        const wrapper = document.createElement('div');
        wrapper.dataset.eventId = event.id;
        wrapper.className = 'calendar-sidebar-event w-full text-left';

        wrapper.innerHTML = `
            <div class="calendar-sidebar-event-main">
                <div class="calendar-sidebar-event-body">
                    <div class="flex items-start gap-2">
                        <span class="calendar-sidebar-event-dot" style="background:${event.color || '#0f172a'}"></span>
                        <div class="min-w-0">
                            <div class="calendar-sidebar-event-title truncate">${escapeHtml(event.title || 'Untitled')}</div>
                            <div class="calendar-sidebar-event-meta truncate">
                                ${event.all_day ? 'All day' : `${formatTime(event.start_at)}${event.end_at ? ' – ' + formatTime(event.end_at) : ''}`}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="calendar-sidebar-event-actions">
                    ${renderEventActions(event, true)}
                </div>
            </div>
        `;

        wrapper.addEventListener('click', () => {
            if (window.__calendarApp?.openEvent) {
                window.__calendarApp.openEvent(event.id);
            }
        });

        return wrapper;
    }

    function renderSidebarEvents(events) {
        sidebarEventList = document.getElementById('sidebarEventList');
        if (!sidebarEventList) return;
        const visible = (events || []).filter(matchesSidebarFilter);
        sidebarEventList.innerHTML = '';

        if (!visible.length) {
            sidebarEventList.innerHTML = `<div class="calendar-sidebar-empty">No events in this filter.</div>`;
            return;
        }

        visible.forEach((event) => {
            sidebarEventList.appendChild(renderSidebarEvent(event));
        });
    }

    function renderFiltersActive() {
        document.querySelectorAll('[data-filter]').forEach((btn) => {
            const active = btn.dataset.filter === state.sidebarFilter;
            btn.classList.toggle('bg-slate-900', active);
            btn.classList.toggle('text-white', active);
            btn.classList.toggle('border-slate-900', active);
            btn.classList.toggle('dark:bg-slate-100', active);
            btn.classList.toggle('dark:text-slate-900', active);
            btn.classList.toggle('dark:border-slate-100', active);
        });
    }

    function updateToolbarUserLabel() {
        const el = document.getElementById('usersSelectValue');
        if (!el) return;
        el.textContent = state.selectedUserName || window.__calendarSelectedUserName || 'Select user';
    }

    function syncViewSelectionMarkers() {
        document.querySelectorAll('[data-month-cell]').forEach((cell) => {
            const date = normalizeDate(cell.dataset.date);
            const isToday = date === todayISO();
            const isSelected = date === state.selectedDate;
            const marker = cell.querySelector('.inline-flex.h-7.w-7');
            if (!marker) return;
            if (isToday && isSelected) marker.className = 'inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900 text-sm font-semibold';
            else if (isSelected) marker.className = 'inline-flex h-7 w-7 items-center justify-center rounded-full bg-sky-100 text-sky-900 ring-1 ring-sky-300 text-sm font-semibold';
            else if (isToday) marker.className = 'inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900 text-sm font-semibold';
            else marker.className = 'inline-flex h-7 w-7 items-center justify-center rounded-full text-sm font-semibold';
        });
    }

    function monthGridStart(dateStr) {
        const date = ensureDate(`${normalizeDate(dateStr)}T00:00:00`) || new Date();
        date.setDate(1);
        const day = date.getDay() === 0 ? 7 : date.getDay();
        date.setDate(date.getDate() - (day - 1));
        return date;
    }

    function setSidebarMiniCalendarDates() {
        const start = monthGridStart(state.selectedDate);
        const cells = [...document.querySelectorAll('#calendarSidebar .mini-day')];
        const selectedMonthKey = state.selectedDate.slice(0, 7);
        cells.forEach((cell, index) => {
            const date = new Date(start);
            date.setDate(start.getDate() + index);
            const iso = localDate(date);
            const cellMonthKey = iso.slice(0, 7);
            cell.dataset.dateJump = iso;
            cell.dataset.miniDate = iso;
            cell.title = iso;
            const isToday = iso === todayISO();
            const isSelected = iso === state.selectedDate;
            if (isToday && isSelected) cell.className = 'mini-day bg-slate-900 text-white ring-2 ring-sky-400 font-semibold cursor-pointer';
            else if (isToday) cell.className = 'mini-day bg-slate-900 text-white font-semibold cursor-pointer';
            else if (isSelected) cell.className = 'mini-day bg-sky-100 text-sky-900 ring-1 ring-sky-300 font-semibold cursor-pointer';
            else if (cellMonthKey === selectedMonthKey) cell.className = 'mini-day hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer';
            else cell.className = 'mini-day text-slate-300 dark:text-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 cursor-pointer';
        });
    }

    function decorateSlotPlaceholders() {
        document.querySelectorAll('[data-slot]').forEach((slot) => {
            if (!slot.dataset.decorated) {
                slot.dataset.decorated = '1';
                slot.classList.add('group', 'relative');
                slot.innerHTML = `<span class="pointer-events-none absolute inset-0 flex items-center justify-center text-[16px] font-semibold leading-none text-slate-500/70 transition group-hover:text-slate-700 dark:text-slate-500/70 dark:group-hover:text-slate-300">+</span>`;
            }
        });
    }

    function decorateMonthEventContainers() {
        document.querySelectorAll('[data-month-events]').forEach((target) => {
            target.style.maxHeight = '78px';
            target.style.overflowY = 'auto';
            target.style.overflowX = 'hidden';
            target.style.paddingRight = '4px';
            target.style.scrollbarGutter = 'stable';
            target.style.scrollBehavior = 'smooth';
            target.style.webkitOverflowScrolling = 'touch';
        });
    }

    function syncScrollContainers() {
        const weekScroll = document.getElementById('calendarScroll');
        if (weekScroll) {
            weekScroll.style.overflowY = 'auto';
            weekScroll.style.overflowX = 'auto';
            weekScroll.style.height = '100%';
            weekScroll.style.overscrollBehavior = 'contain';
            weekScroll.style.scrollBehavior = 'smooth';
            weekScroll.style.webkitOverflowScrolling = 'touch';
        }
        const monthBody = document.getElementById('calendarMonthBody');
        if (monthBody) monthBody.style.overflow = 'hidden';
        const monthInner = monthBody?.firstElementChild;
        if (monthInner) {
            monthInner.style.overflowY = 'auto';
            monthInner.style.overflowX = 'hidden';
            monthInner.style.height = '100%';
            monthInner.style.overscrollBehavior = 'contain';
            monthInner.style.scrollBehavior = 'smooth';
            monthInner.style.webkitOverflowScrolling = 'touch';
        }
    }

    function canScrollInside(el, deltaY) {
        if (!el) return false;
        const max = el.scrollHeight - el.clientHeight;
        if (max <= 0) return false;
        if (deltaY > 0) return el.scrollTop < max - 1;
        if (deltaY < 0) return el.scrollTop > 0;
        return false;
    }

    function findScrollableTarget(target) {
        return target?.closest?.('#calendarScroll, #calendarMonthBody > div, [data-month-events], .overflow-y-auto, .calendar-scroll, #calendarSidebar') || null;
    }

    // function onDocumentWheel(e) {
    //     const target = findScrollableTarget(e.target);
    //     if (!target) return;
    //     if (target.matches?.('[data-month-events]') && canScrollInside(target, e.deltaY)) return;
    //     if (target.id === 'calendarScroll' || target.matches?.('#calendarMonthBody > div')) {
    //         if (canScrollInside(target, e.deltaY)) return;
    //     }
    //     if (state.view !== 'month') return;
    //     const monthBody = e.target.closest('#calendarMonthBody');
    //     if (!monthBody) return;
    //     const innerScroll = monthBody.firstElementChild;
    //     if (innerScroll && canScrollInside(innerScroll, e.deltaY)) return;
    //     if (state.navigating || state.monthWheelLock) { e.preventDefault(); return; }
    //     e.preventDefault();
    //     state.monthWheelLock = true;
    //     const step = e.deltaY > 0 ? 1 : -1;
    //     navigate('month', shiftDate(state.selectedDate, 'month', step)).finally(() => {
    //         setTimeout(() => { state.monthWheelLock = false; }, 180);
    //     });
    // }

    function initPickers() {
        if (typeof window.flatpickr !== 'function') return;
        if (!fields.start_at || !fields.end_at) return;
        const common = {
            enableTime: true,
            time_24hr: true,
            disableMobile: true,
            allowInput: true,
            minuteIncrement: 5,
            dateFormat: 'Y-m-d H:i',
            appendTo: document.body,
            onReady: function (_, __, instance) {
                if (instance?.calendarContainer) instance.calendarContainer.style.zIndex = '99999';
            },
        };
        if (startPicker) startPicker.destroy();
        if (endPicker) endPicker.destroy();
        startPicker = window.flatpickr(fields.start_at, {
            ...common,
            onChange: function (selectedDates) {
                if (!selectedDates.length) return;
                if (fields.all_day?.checked) { applyAllDayPreset(selectedDates[0]); return; }
                if (!state.manualEndDirty) setPickerDate(endPicker, new Date(selectedDates[0].getTime() + 60 * 60 * 1000), false);
                if (fields.repeat?.checked && fields.repeat_type?.value === 'week') setRepeatWeekdays([weekdayKeyFromDate(selectedDates[0])]);
            },
        });
        endPicker = window.flatpickr(fields.end_at, { ...common, onChange: function () { state.manualEndDirty = true; } });
    }

    function bindFormOnce() {
        if (!eventForm || eventForm.dataset.bound === '1') return;
        eventForm.dataset.bound = '1';
        eventForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            try {
                await saveEvent();
            } catch (error) {
                if (error.response?.status === 422) {
                    showErrors(error.response.data.errors || {});
                    toast('error', 'Please fix the form errors.');
                    return;
                }
                toast('error', error.response?.data?.message || 'Something went wrong.');
            }
        });
    }

    function shiftDate(dateStr, view, step) {
        const date = ensureDate(`${normalizeDate(dateStr)}T00:00:00`) || new Date();
        if (view === 'day') date.setDate(date.getDate() + step);
        else if (view === 'month') date.setMonth(date.getMonth() + step);
        else date.setDate(date.getDate() + (7 * step));
        return localDate(date);
    }

    function renderEvents(events) {
        clearRenderedEvents();
        const range = getRange();
        const visible = (events || []).filter((event) => {
            const start = ensureDate(event.start_at);
            const end = ensureDate(event.end_at || event.start_at) || start;
            if (!start || !end) return false;
            if (!matchesMainCalendar(event)) return false;
            return start.getTime() <= range.end.getTime() && end.getTime() >= range.start.getTime();
        });
        state.visibleEvents = visible;
        renderFiltersActive();
        updateToolbarUserLabel();
        if (state.view === 'month') {
            const MAX_EVENTS_PER_DAY = 3;
            const dayBuckets = new Map();

            visible.forEach((event) => {
                const start = ensureDate(event.start_at);
                const end = ensureDate(event.end_at || event.start_at) || start;
                if (!start || !end) return;

                let cursor = new Date(startOfDay(start));
                const last = endOfDay(end);

                while (cursor <= last) {
                    const key = localDate(cursor);

                    if (!dayBuckets.has(key)) {
                        dayBuckets.set(key, []);
                    }

                    dayBuckets.get(key).push(event);
                    cursor.setDate(cursor.getDate() + 1);
                }
            });

            dayBuckets.forEach((eventsForDay, dayKey) => {
                const target = document.querySelector(`[data-month-events="${dayKey}"]`);
                if (!target) return;

                eventsForDay.slice(0, MAX_EVENTS_PER_DAY).forEach((event) => {
                    target.appendChild(renderMonthBadge(event));
                });

                if (eventsForDay.length > MAX_EVENTS_PER_DAY) {
                    const moreBtn = document.createElement('button');
                    moreBtn.type = 'button';
                    moreBtn.className = 'month-event-more text-[11px] font-semibold text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100';
                    moreBtn.textContent = `+${eventsForDay.length - MAX_EVENTS_PER_DAY} more`;

                    moreBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        navigate('day', dayKey);
                    });
                    target.appendChild(moreBtn);
                }
            });

            return;
        }
        const dayMap = buildDayMap(visible);
        for (const [dayKey, dayEvents] of dayMap.entries()) {
            const dayColumn = document.querySelector(`[data-day-column][data-date="${dayKey}"]`);
            if (!dayColumn) continue;
            const layer = dayColumn.querySelector('.js-event-layer');
            if (!layer) continue;
            const allDayEvents = dayEvents.filter((e) => e.all_day);
            const timedEvents = dayEvents.filter((e) => !e.all_day);
            allDayEvents.forEach((event, index) => {
                layer.appendChild(buildEventSegment(event, dayKey, { top: 8 + index * 42, height: 36, laneCount: 1, laneIndex: 0 }));
            });
            const laneEnds = [];
            timedEvents.forEach((event) => {
                const segStart = event._segmentStart || ensureDate(event.start_at);
                const segEnd = event._segmentEnd || ensureDate(event.end_at || event.start_at);
                if (!segStart || !segEnd) return;
                let laneIndex = laneEnds.findIndex((laneEnd) => segStart.getTime() >= laneEnd);
                if (laneIndex === -1) {
                    laneIndex = laneEnds.length;
                    laneEnds.push(segEnd.getTime());
                } else {
                    laneEnds[laneIndex] = segEnd.getTime();
                }
                const laneCount = Math.max(1, laneEnds.length);
                const top = (segStart.getHours() * rowHeight) + (segStart.getMinutes() / 60) * rowHeight + 4;
                const durationHours = Math.max(0.5, (segEnd.getTime() - segStart.getTime()) / 36e5);
                const height = Math.max(38, durationHours * rowHeight - 8);
                layer.appendChild(buildEventSegment(event, dayKey, { top, height, laneCount, laneIndex }));
            });
        }
        updateTimeLine();
    }

    function syncCompletionButtons(event = null) {
        if (!fields.completeEvent || !fields.notCompleteEvent) return;
        const visible = canShowCompletionButtons(event);
        fields.completeEvent.classList.toggle('hidden', !visible);
        fields.notCompleteEvent.classList.toggle('hidden', !visible);
    }

    function updateTimeLine() {
        const line = document.getElementById('timeLine');
        const dot = document.getElementById('timeDot');
        if (!line || !dot || state.view === 'month') return;
        const currentColumn = document.querySelector(`[data-day-column][data-date="${state.selectedDate}"]`);
        if (!currentColumn) return;
        const now = new Date();
        const y = headerHeight + (now.getHours() * rowHeight) + (now.getMinutes() / 60) * rowHeight;
        const gridRect = document.getElementById('calendarGrid')?.getBoundingClientRect();
        const colRect = currentColumn.getBoundingClientRect();
        if (!gridRect) return;
        const left = colRect.left - gridRect.left;
        const width = colRect.width;
        line.style.top = `${y}px`;
        line.style.left = `${left}px`;
        line.style.width = `${width}px`;
        dot.style.top = `${y}px`;
        dot.style.left = `${left}px`;
        line.classList.remove('hidden');
        dot.classList.remove('hidden');
    }

    function clearSelection() {
        document.querySelectorAll('.slot-selected').forEach((el) => el.classList.remove('slot-selected', 'bg-sky-100/70', 'dark:bg-sky-500/20'));
        dragState = null;
    }

    function openSlotModal(date, hour = 0, allDay = false) {
        if (!state.canManage) return;
        const d = normalizeDate(date);
        openModal('create', null, { start_at: allDay ? `${d}T00:00` : `${d}T${pad(hour)}:00`, end_at: allDay ? `${d}T23:59` : `${d}T${pad(Math.min(hour + 1, 23))}:00`, all_day: allDay });
    }

    function openEventFromCard(eventIdValue) {
        const event = (state.visibleEvents.length ? state.visibleEvents : state.events).find((item) => String(item.id) === String(eventIdValue));
        if (!event) return;
        state.current = buildEventPayload(event);
        const readonly = !isEditableStatus(event.status) || !state.canManage;
        openModal(readonly ? 'show' : 'edit', state.current);
    }

    function setSelectedDateInPlace(date) {
        state.selectedDate = normalizeDate(date);
        if (app) app.dataset.selectedDate = state.selectedDate;
        history.replaceState({ view: state.view, date: state.selectedDate }, '', buildUrl(state.view, state.selectedDate));
        setSidebarMiniCalendarDates();
        syncViewSelectionMarkers();
        updateTimeLine();
    }

    function handleSidebarDateClick(date) {
        const normalized = normalizeDate(date);
        const currentKey = state.selectedDate.slice(0, 7);
        const targetKey = normalized.slice(0, 7);
        const sameDate = normalized === state.selectedDate;
        if (sameDate) {
            if (state.view === 'month') { navigate('day', normalized); return; }
            if (state.view === 'day') { navigate('month', normalized); return; }
            navigate('day', normalized); return;
        }
        if (state.view === 'month') {
            if (targetKey !== currentKey) { navigate('month', normalized); return; }
            setSelectedDateInPlace(normalized); return;
        }
        if (state.view === 'day') { navigate('day', normalized); return; }
        navigate('week', normalized);
    }

    function bindDragSelection() {
        document.querySelectorAll('[data-slot]').forEach((slot) => {
            if (slot.dataset.bound === '1') return;
            slot.dataset.bound = '1';
            slot.addEventListener('mousedown', (e) => {
                if (!state.canManage) return;
                e.preventDefault();
                dragState = { active: true, startSlot: slot, endSlot: slot };
                clearSelection();
                slot.classList.add('slot-selected', 'bg-sky-100/70', 'dark:bg-sky-500/20');
            });
            slot.addEventListener('mouseover', () => {
                if (!dragState?.active) return;
                dragState.endSlot = slot;
                const start = ensureDate(`${dragState.startSlot.dataset.date}T${pad(Number(dragState.startSlot.dataset.hour))}:00`);
                const end = ensureDate(`${dragState.endSlot.dataset.date}T${pad(Number(dragState.endSlot.dataset.hour) + 1)}:00`);
                if (!start || !end) return;
                const min = Math.min(start.getTime(), end.getTime());
                const max = Math.max(start.getTime(), end.getTime());
                document.querySelectorAll('[data-slot]').forEach((s) => {
                    const d = ensureDate(`${s.dataset.date}T${pad(Number(s.dataset.hour))}:00`);
                    if (!d) return;
                    const inRange = d.getTime() >= min && d.getTime() < max;
                    s.classList.toggle('slot-selected', inRange);
                    s.classList.toggle('bg-sky-100/70', inRange);
                    s.classList.toggle('dark:bg-sky-500/20', inRange);
                });
            });
        });
        document.addEventListener('mouseup', () => {
            if (!dragState?.active) return;
            dragState.active = false;
            const start = ensureDate(`${dragState.startSlot.dataset.date}T${pad(Number(dragState.startSlot.dataset.hour))}:00`);
            const end = ensureDate(`${dragState.endSlot.dataset.date}T${pad(Number(dragState.endSlot.dataset.hour) + 1)}:00`);
            if (start && end) openModal('create', null, { start_at: start, end_at: end, all_day: false });
            clearSelection();
        });
    }

    function filterUserOptions() {
        const search = document.getElementById('usersSelectSearch');
        const q = (search?.value || '').trim().toLowerCase();
        document.querySelectorAll('[data-user-option]').forEach((item) => {
            const name = String(item.dataset.userName || '').toLowerCase();
            item.classList.toggle('hidden', !!q && !name.includes(q));
        });
    }

    function initUsersSelect() {
        const btn = document.getElementById('usersSelectButton');
        const menu = document.getElementById('usersSelectMenu');
        const search = document.getElementById('usersSelectSearch');
        if (!btn || !menu) return;
        if (btn.dataset.bound === '1') return;
        btn.dataset.bound = '1';
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const m = document.getElementById('usersSelectMenu');
            if (!m) return;
            m.classList.toggle('hidden');
            if (!m.classList.contains('hidden')) {
                document.getElementById('usersSelectSearch')?.focus();
                filterUserOptions();
            }
        });
        if (search && !search.dataset.bound) {
            search.dataset.bound = '1';
            search.addEventListener('input', filterUserOptions);
        }
        document.querySelectorAll('[data-user-option]').forEach((item) => {
            if (item.dataset.bound === '1') return;
            item.dataset.bound = '1';
            item.addEventListener('click', () => {
                const url = item.dataset.userUrl;
                if (url) window.location.href = url;
            });
        });
        document.querySelectorAll('[data-go-self]').forEach((b) => {
            if (b.dataset.bound === '1') return;
            b.dataset.bound = '1';
            b.addEventListener('click', goSelf);
        });
    }

    function goSelf() {
        state.selectedUserId = authUserId || null;
        state.selectedUserName = authUserName || 'My calendar';
        navigate(state.view || 'month', state.selectedDate || todayISO());
    }

    function renderMonthGridDecorations() {
        decorateSlotPlaceholders();
        decorateMonthEventContainers();
        setSidebarMiniCalendarDates();
        syncViewSelectionMarkers();
        syncScrollContainers();
        updateTimeLine();
    }

    function onDocumentClick(e) {
        if (!e.target.closest('#usersSelectWrap')) document.getElementById('usersSelectMenu')?.classList.add('hidden');

        const actionBtn = e.target.closest('[data-event-action]'); if (actionBtn) { e.preventDefault(); e.stopPropagation(); const card = actionBtn.closest('[data-event-id]'); const id = card?.dataset.eventId; if (!id) return; if (actionBtn.dataset.eventAction === 'complete') { completeReminder(id); } else if (actionBtn.dataset.eventAction === 'not-complete') { notCompleteReminder(id); } return; }

        const filterBtn = e.target.closest('[data-filter]');
        if (filterBtn) {
            state.sidebarFilter = normalizeFilter(filterBtn.dataset.filter);
            renderFiltersActive();
            renderSidebarEvents(state.events);
            return;
        }

        const eventBtn = e.target.closest('[data-event-id]');
        if (eventBtn) { openEventFromCard(eventBtn.dataset.eventId); return; }

        const slot = e.target.closest('[data-slot]');
        if (slot) {
            if (Date.now() < (state._slotSuppressUntil || 0)) return;
            openSlotModal(slot.dataset.date, Number(slot.dataset.hour), false);
            return;
        }

        const monthCell = e.target.closest('[data-month-cell]');
        if (monthCell) {
            const now = new Date(); now.setSeconds(0, 0);
            const startAt = `${monthCell.dataset.date}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
            const end = new Date(now.getTime() + 60 * 60 * 1000);
            const endAt = `${monthCell.dataset.date}T${pad(end.getHours())}:${pad(end.getMinutes())}`;
            openModal('create', null, { start_at: startAt, end_at: endAt, all_day: false });
            return;
        }

        const viewSwitch = e.target.closest('[data-view-switch]');
        if (viewSwitch) { navigate(viewSwitch.dataset.viewSwitch, state.selectedDate); return; }

        const navBtn = e.target.closest('[data-nav]');
        if (navBtn) {
            const nextDate = shiftDate(state.selectedDate, state.view, navBtn.dataset.nav === 'next' ? 1 : -1);
            navigate(state.view, nextDate);
            return;
        }

        const jumpToday = e.target.closest('[data-jump-today]');
        if (jumpToday) { navigate(state.view, todayISO()); return; }

        const miniDate = e.target.closest('[data-date-jump]');
        if (miniDate) { handleSidebarDateClick(miniDate.dataset.dateJump); return; }

        const newEvent = e.target.closest('#newEventButton, #newEventButtonSidebar, #newEventButtonMobile');
        if (newEvent) {
            if (!state.canManage) return;
            const now = new Date(); now.setSeconds(0, 0);
            const startAt = `${state.selectedDate}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
            const end = new Date(now.getTime() + 60 * 60 * 1000);
            const endAt = `${state.selectedDate}T${pad(end.getHours())}:${pad(end.getMinutes())}`;
            openModal('create', null, { start_at: startAt, end_at: endAt, all_day: false });
            return;
        }

        const closeEventModal = e.target.closest('#closeEventModal, #cancelEvent, #eventModalBackdrop');
        if (closeEventModal) { closeModal(); return; }

        const completeBtn = e.target.closest('#completeEvent');
        if (completeBtn && state.current?.reminder_id && canShowCompletionButtons(state.current) && state.canManage) { completeReminder(state.current.id); return; }

        const notCompleteBtn = e.target.closest('#notCompleteEvent');
        if (notCompleteBtn && state.current?.reminder_id && canShowCompletionButtons(state.current) && state.canManage) { notCompleteReminder(state.current.id); return; }

        const deleteBtn = e.target.closest('#removeEvent, #deleteFromModal');
        if (deleteBtn) {
            if (!state.canManage) return;
            const id = deleteBtn.id === 'removeEvent' && state.current ? state.current.id : eventId?.value;
            if (id) deleteEvent(id, state.current?.reminder_id || null);
            return;
        }
    }

    function onDocumentInput(e) {
        const target = e.target;
        if (!target) return;
        if (target.id === 'searchInputMobile' || target.id === 'searchInput') {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                state.filters.q = target.value;
                refreshEvents();
            }, 250);
        }
    }

    function onDocumentChange(e) {
        const target = e.target;
        if (!target) return;
        if (target.id === 'statusFilterMobile' || target.id === 'statusFilter') {
            state.filters.status = target.value;
            refreshEvents();
        }
        if (target.id === 'eventFilter') {
            state.sidebarFilter = normalizeFilter(target.value);
            renderFiltersActive();
            renderSidebarEvents(state.events);
        }
        if (target.id === 'status' && state.current) syncCompletionButtons({ ...state.current, status: target.value });
        if (target.id === 'all_day') syncAllDayFields();
        if (target.id === 'repeat') syncRepeatUI();
        if (target.id === 'repeat_type') syncRepeatUI();
    }

    function bindRepeatButtons() {
        document.querySelectorAll('.repeat-weekday').forEach((btn) => {
            if (btn.dataset.bound === '1') return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', () => {
                const day = btn.dataset.weekday;
                if (fields.repeat_type?.value === 'week') state.selectedRepeatWeekdays = new Set([day]);
                else {
                    if (state.selectedRepeatWeekdays.has(day)) state.selectedRepeatWeekdays.delete(day);
                    else state.selectedRepeatWeekdays.add(day);
                }
                syncRepeatWeekdayButtons();
            });
        });
    }

    function onPointerDown(e) {
        const slot = e.target.closest('[data-slot]');
        if (!slot || e.button !== 0) return;
        state.drag = { date: slot.dataset.date, startHour: Number(slot.dataset.hour), x: e.clientX, y: e.clientY, moved: false };
    }

    function onPointerMove(e) {
        if (!state.drag) return;
        if (Math.abs(e.clientX - state.drag.x) > 8 || Math.abs(e.clientY - state.drag.y) > 8) state.drag.moved = true;
    }

    function onPointerUp(e) {
        if (!state.drag) return;
        const target = document.elementFromPoint(e.clientX, e.clientY);
        const slot = target?.closest?.('[data-slot]');
        const endHour = slot ? Number(slot.dataset.hour) : state.drag.startHour;
        if (!state.drag.moved) openSlotModal(state.drag.date, state.drag.startHour, false);
        else {
            const startHour = Math.min(state.drag.startHour, endHour);
            const finishHour = Math.max(state.drag.startHour, endHour);
            openModal('create', null, { start_at: `${state.drag.date}T${pad(startHour)}:00`, end_at: `${state.drag.date}T${pad(Math.min(finishHour + 1, 23))}:00`, all_day: false });
        }
        state._slotSuppressUntil = Date.now() + 300;
        state.drag = null;
    }

    function decorateModalActionButtons(mode = 'create') {
    if (fields.completeEvent && fields.completeEvent.dataset.iconified !== '1') {
        fields.completeEvent.innerHTML = `<svg viewBox="0 0 24 24" fill="none" class="h-4 w-4"><path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
        fields.completeEvent.dataset.iconified = '1';
        fields.completeEvent.title = 'Complete';
        fields.completeEvent.setAttribute('aria-label', 'Complete');
    }

    if (fields.notCompleteEvent && fields.notCompleteEvent.dataset.iconified !== '1') {
        fields.notCompleteEvent.innerHTML = `<svg viewBox="0 0 24 24" fill="none" class="h-4 w-4"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
        fields.notCompleteEvent.dataset.iconified = '1';
        fields.notCompleteEvent.title = 'Not complete';
        fields.notCompleteEvent.setAttribute('aria-label', 'Not complete');
    }

    if (fields.saveEvent) {
        const label = mode === 'edit' ? 'Update' : 'Create';
        const labelEl = fields.saveEvent.querySelector('[data-save-label]');
        if (labelEl) {
            labelEl.textContent = label;
        } else {
            fields.saveEvent.innerHTML = `

                <span data-save-label>${label}</span>
            `;
        }
    }
}

    async function saveEvent() {
        if (state.saving || state.showOnly || !state.canManage) return;
        if (eventMode?.value === 'edit' && !isEditableStatus(state.current?.status)) return;
        if (!canEditEvent(state.current)) return;
        state.saving = true;
        try {
            clearErrors();
            const payload = payloadFromForm();
            const id = eventId?.value;
            const mode = eventMode?.value;
            const response = (mode === 'edit' && id) ? await window.axios.patch(`/calendar/events/${id}`, payload) : await window.axios.post('/calendar/events', payload);
            if (response.data?.success) { closeModal(); await refreshEvents(); }
        } finally { state.saving = false; }
    }

    async function deleteEvent(id, reminderId = null) {
        const response = await window.axios.delete(`/calendar/events/${id}`, { data: { reminder_id: reminderId, occurrence_at: state.current?.occurrence_at || null } });
        if (response.data?.success) { closeModal(); await refreshEvents(); }
    }

    async function completeReminder(id) {
        const response = await window.axios.post(`/calendar/events/${id}/mark/complete`);
        if (response.data?.success) { closeModal(); await refreshEvents(); }
    }

    async function notCompleteReminder(id) {
        const response = await window.axios.post(`/calendar/events/${id}/mark/not-completed`);
        if (response.data?.success) { closeModal(); await refreshEvents(); }
    }

    function decorateEverything() {
        decorateModalActionButtons();
        decorateSlotPlaceholders();
        decorateMonthEventContainers();
        setSidebarMiniCalendarDates();
        syncViewSelectionMarkers();
        syncScrollContainers();
        bindFormOnce();
        updateTimeLine();
    }

    function matchSidebarSearchText(event) { return `${event.title || ''} ${event.description || ''} ${event.status || ''}`.toLowerCase(); }
    function buildSidebarCardTitle(event) { return `${event?.title || 'Untitled'}`; }

    async function refreshEvents() {
        const ticket = ++state.refreshTicket;
        const range = getRange();
        const response = await window.axios.get(cfg.routeEvents, {
            params: {
                start: range.start.toISOString(),
                end: range.end.toISOString(),
                q: state.filters.q,
                status: state.filters.status,
                user_id: state.selectedUserId || undefined,
            },
        });
        if (ticket !== state.refreshTicket) return;
        state.events = (response.data?.data || []);
        renderEvents(state.events);
        renderSidebarEvents(state.events);
        decorateSlotPlaceholders();
        decorateMonthEventContainers();
        bindDragSelection();
        syncViewSelectionMarkers();
        updateTimeLine();
    }

    async function navigate(view, date, push = true) {
        if (state.navigating) return;
        state.navigating = true;
        try {
            const normalized = normalizeDate(date);
            const nextUrl = buildUrl(view, normalized);
            const response = await fetch(nextUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');

            syncStateFromDocument(doc);
            replaceSection(doc, 'calendarToolbar');
            replaceSection(doc, 'calendarSidebar');
            replaceSection(doc, 'calendarWeekHeader');
            replaceSection(doc, 'calendarWeekBody');
            replaceSection(doc, 'calendarMonthBody');

            if (push) history.pushState({ view, date: normalized }, '', nextUrl);

            cacheDom();
            initPickers();
            syncScrollContainers();
            bindFormOnce();
            bindRepeatButtons();
            syncRepeatUI();
            initUsersSelect();
            renderFiltersActive();
            decorateEverything();

            await refreshEvents();
        } finally {
            state.navigating = false;
        }
    }

    function init() {
        cacheDom();
        initPickers();
        syncScrollContainers();
        bindFormOnce();
        bindRepeatButtons();
        syncRepeatUI();
        initUsersSelect();
        renderFiltersActive();
        renderEvents(state.events);
        renderSidebarEvents(state.events);
        decorateModalActionButtons();
        decorateEverything();
        bindDragSelection();
        updateTimeLine();

        document.addEventListener('click', onDocumentClick);
        document.addEventListener('keydown', (e) => {
            const card = e.target.closest?.('[data-event-id]');
            if (!card) return;
            if (e.key !== 'Enter' && e.key !== ' ') return;
            e.preventDefault();
            openEventFromCard(card.dataset.eventId);
        });
        document.addEventListener('input', onDocumentInput);
        document.addEventListener('change', onDocumentChange);
        // document.addEventListener('wheel', onDocumentWheel, { passive: false });
        document.addEventListener('pointerdown', onPointerDown, true);
        document.addEventListener('pointermove', onPointerMove, true);
        document.addEventListener('pointerup', onPointerUp, true);
        document.addEventListener('keydown', (e) => { if (e.key !== 'Escape') return; if (eventModal && !eventModal.classList.contains('hidden')) closeModal(); });
        window.addEventListener('popstate', () => {
            const current = new URL(window.location.href);
            const view = current.searchParams.get('view') || 'month';
            const date = normalizeDate(current.searchParams.get('date') || todayISO());
            const userId = current.searchParams.get('user_id');
            state.selectedUserId = userId ? Number(userId) : state.selectedUserId;
            state.sidebarFilter = normalizeFilter(current.searchParams.get('filter') || state.sidebarFilter);
            state.filters.status = current.searchParams.get('status') || 'all';
            state.filters.q = current.searchParams.get('q') || '';
            navigate(view, date, false);
        });
        window.addEventListener('resize', updateTimeLine);

        if (!window.__calendarInitialEvents || !window.__calendarInitialEvents.length) refreshEvents();

        window.__calendarApp = {
            state,
            refreshEvents,
            navigate,
            openModal,
            closeModal,
            openEvent: openEventFromCard,
            setUser(userId) {
                state.selectedUserId = userId ? Number(userId) : null;
                navigate(state.view, state.selectedDate);
            },
            setFilter(filter) {
                state.sidebarFilter = normalizeFilter(filter || 'pending');
                renderFiltersActive();
                renderSidebarEvents(state.events);
            },
            setStatus(status) {
                state.filters.status = status || 'all';
                refreshEvents();
            },
            setSearch(query) {
                state.filters.q = query || '';
                refreshEvents();
            },
            canManage() { return !!state.canManage; },
        };
    }

    init();
})();
