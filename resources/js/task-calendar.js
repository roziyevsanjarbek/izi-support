import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';

document.addEventListener('DOMContentLoaded', () => {
    const calendarEl = document.getElementById('task-calendar');

    if (!calendarEl) return;

    const calendar = new Calendar(calendarEl, {
        plugins: [
            dayGridPlugin,
            timeGridPlugin,
            listPlugin,
            interactionPlugin,
        ],

        initialView: 'dayGridMonth',

        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
        },

        firstDay: 1,

        contentHeight: 'auto',

        events: '/tasks/calendar/events',

        dayMaxEvents: 3,

        moreLinkClick: 'popover',

        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        },

        eventClick(info) {
            info.jsEvent.preventDefault();

            console.log(info.event);

            window.location.href = `/tasks/${info.event.id}`;

        }
    });

    calendar.render();
});
