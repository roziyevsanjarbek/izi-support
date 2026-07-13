<style>
    html,
    body {
        height: 100%;
        overflow: hidden;
    }

    .calendar-app .hidden,
#eventModal.hidden {
    display: none !important;
}

    .calendar-app {
        height: 100dvh;
        overflow: hidden;
    }

    .calendar-grid {
        display: grid;
        grid-template-columns: 72px repeat(7, minmax(140px, 1fr));
        min-width: 0;
    }

    .calendar-app[data-view="day"] .calendar-grid {
        grid-template-columns: 72px minmax(0, 1fr);
        width: 100%;
        min-width: 0;
    }

    .calendar-app[data-view="day"] #calendarScroll,
    .calendar-app[data-view="day"] #calendarGrid,
    .calendar-app[data-view="day"] #calendarGrid>[data-day-column] {
        min-width: 0;
    }

    .calendar-row {
        height: 44px;
    }

    .calendar-scroll {
        overflow: auto;
        overscroll-behavior: contain;
    }

    .calendar-scroll::-webkit-scrollbar {
        height: 10px;
        width: 10px;
    }

    .calendar-scroll::-webkit-scrollbar-thumb {
        background: rgba(148, 163, 184, .35);
        border-radius: 9999px;
    }

    .calendar-scroll::-webkit-scrollbar-track {
        background: transparent;
    }

    .mini-day {
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 9999px;
        font-size: 12px;
        line-height: 1;
    }

    .event-card {
        position: absolute;
        border-radius: 14px;
        padding: 10px 12px;
        font-size: 12px;
        line-height: 1.2;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .08);
        border: 1px solid rgba(148, 163, 184, .20);
        pointer-events: auto;
        cursor: pointer;
        overflow: hidden;
        transition: transform .15s ease, box-shadow .15s ease;
    }

    .event-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 24px rgba(15, 23, 42, .12);
    }

    .event-card .event-title {
        font-weight: 700;
        font-size: 12px;
        margin-bottom: 4px;
    }

    .event-lane {
        left: calc(8px + (var(--lane-index) * (100% - 16px) / var(--lane-count)));
        width: calc((100% - 16px) / var(--lane-count) - 4px);
        z-index: calc(20 + var(--lane-index));
    }

    .drawer-backdrop {
        background: rgba(15, 23, 42, .45);
        backdrop-filter: blur(10px);
    }

    .month-cell {
        min-height: 112px;
    }

    .calendar-event-actions {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        display: flex;
        gap: 6px;
        z-index: 6;
        align-items: center;
    }

    .calendar-event-action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 9999px;
        border: 1px solid transparent;
        box-shadow: 0 6px 14px rgba(15, 23, 42, .08);
        transition: transform .15s ease, box-shadow .15s ease, background-color .15s ease;
        flex-shrink: 0;
    }

    .calendar-event-action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 18px rgba(15, 23, 42, .12);
    }

    .calendar-event-action-btn--complete {
        background: rgba(16, 185, 129, .12);
        color: rgb(5, 150, 105);
        border-color: rgba(16, 185, 129, .22);
    }

    .calendar-event-action-btn--not-complete {
        background: rgba(244, 63, 94, .12);
        color: rgb(225, 29, 72);
        border-color: rgba(244, 63, 94, .22);
    }

    .calendar-event-action-btn svg {
        width: 14px;
        height: 14px;
    }

    .calendar-event-card-inner {
        padding-right: 76px;
    }

    .calendar-event-month-inner {
        padding-right: 54px;
    }

    .calendar-event-actions--compact .calendar-event-action-btn {
        width: 22px;
        height: 22px;
        box-shadow: 0 4px 10px rgba(15, 23, 42, .07);
    }

    .calendar-event-actions--compact .calendar-event-action-btn svg {
        width: 11px;
        height: 11px;
    }

    @media (max-width: 767px) {
        .calendar-grid {
            grid-template-columns: 56px repeat(7, minmax(112px, 1fr));
        }

        .month-cell {
            min-height: 84px;
        }
    }

    .flatpickr-calendar {
        border-radius: 22px !important;
        border: 1px solid rgb(226 232 240) !important;
        box-shadow: 0 24px 60px rgba(15, 23, 42, .18) !important;
        overflow: hidden;
        font-size: 14px;
        z-index: 99999 !important;
    }

    .dark .flatpickr-calendar {
        background: rgb(2 6 23) !important;
        border-color: rgb(30 41 59) !important;
        color: rgb(248 250 252) !important;
    }

    .flatpickr-months {
        padding-top: 6px;
        padding-bottom: 6px;
    }

    .flatpickr-current-month {
        font-weight: 700;
    }

    .flatpickr-day.selected,
    .flatpickr-day.startRange,
    .flatpickr-day.endRange {
        border-color: rgb(15 23 42) !important;
        background: rgb(15 23 42) !important;
        color: white !important;
    }

    .dark .flatpickr-day.selected,
    .dark .flatpickr-day.startRange,
    .dark .flatpickr-day.endRange {
        border-color: rgb(248 250 252) !important;
        background: rgb(248 250 252) !important;
        color: rgb(15 23 42) !important;
    }

    .flatpickr-time input,
    .flatpickr-input {
        font-variant-numeric: tabular-nums;
    }

    .calendar-sidebar-event {
        display: block;
        width: 100%;
        border-radius: 0;
        border: 0;
        padding: 8px 0;
        background: transparent;
        box-shadow: none;
        text-align: left;
    }

    .calendar-sidebar-event:hover {
        background: rgba(148, 163, 184, .06);
    }

    .dark .calendar-sidebar-event:hover {
        background: rgba(148, 163, 184, .06);
    }

    .calendar-sidebar-event-main {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 4px 0;
    }

    .calendar-sidebar-event-body {
        min-width: 0;
        flex: 1;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .calendar-sidebar-event-details {
        min-width: 0;
    }

    .calendar-sidebar-event-dot {
        width: 9px;
        height: 9px;
        border-radius: 9999px;
        flex-shrink: 0;
        margin-top: 0;
    }

    .calendar-sidebar-event-title {
        font-size: 13px;
        font-weight: 700;
        line-height: 1.25;
    }

    .calendar-sidebar-event-meta {
        margin-top: 2px;
        font-size: 11px;
        color: rgb(100 116 139);
    }

    .dark .calendar-sidebar-event-meta {
        color: rgb(148 163 184);
    }

    .calendar-sidebar-event-actions {
        display: flex;
        gap: 6px;
        margin-left: auto;
        flex-shrink: 0;
        align-items: center;
    }

    .calendar-sidebar-event .calendar-event-actions {
        position: static !important;
        transform: none !important;
        right: auto;
        top: auto;
        margin-left: auto;
    }

    .calendar-sidebar-event-actions .calendar-event-action-btn {
        width: 24px;
        height: 24px;
    }

    .calendar-sidebar-empty {
        border: 1px dashed rgb(226 232 240);
        border-radius: 16px;
        padding: 16px;
        font-size: 12px;
        color: rgb(100 116 139);
        text-align: center;
    }

    .dark .calendar-sidebar-empty {
        border-color: rgb(30 41 59);
        color: rgb(148 163 184);
    }

    #completeEvent,
    #notCompleteEvent {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        line-height: 0;
    }

    #completeEvent svg,
    #notCompleteEvent svg,
    .calendar-event-action-btn svg {
        display: block;
    }

    #sidebarEventList {
        overscroll-behavior: contain;
    }
</style>