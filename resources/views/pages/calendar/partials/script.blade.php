<style>
    html, body {
        height: 100%;
        overflow: hidden;
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

    @media (max-width: 767px) {
        .calendar-grid {
            grid-template-columns: 56px repeat(7, minmax(112px, 1fr));
        }

        .month-cell {
            min-height: 84px;
        }
    }
</style>
<style>
    .flatpickr-calendar {
        border-radius: 22px !important;
        border: 1px solid rgb(226 232 240) !important;
        box-shadow: 0 24px 60px rgba(15, 23, 42, .18) !important;
        overflow: hidden;
        font-size: 14px;
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
</style>
<style>
    #detailsDrawer {
        display: none;
    }

    #detailsDrawer:not(.hidden) {
        display: flex;
    }

    .drawer-backdrop {
        background: rgba(15, 23, 42, .50);
        backdrop-filter: blur(14px);
    }

    #detailsDrawer .shadow-\[0_30px_80px_rgba\(15\,23\,42\2c \.28\)\] {
        animation: modalPop .18s ease-out;
    }

    @keyframes modalPop {
        from {
            transform: translateY(14px) scale(.98);
            opacity: 0;
        }
        to {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
    }

    #detailsDrawer .detail-chip {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        border-radius: 9999px;
        padding: .4rem .7rem;
        font-size: 12px;
        font-weight: 600;
        line-height: 1;
    }

    @media (max-width: 640px) {
        #detailsDrawer {
            padding: 16px;
        }

        #detailsDrawer > div:last-child {
            max-width: 100%;
            border-radius: 24px;
        }

        #detailTitle {
            font-size: 18px;
        }
    }
</style>