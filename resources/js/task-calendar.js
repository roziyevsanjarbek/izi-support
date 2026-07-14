import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';

document.addEventListener('DOMContentLoaded', () => {
    const calendarEl = document.getElementById('task-calendar');
    let selectedUser = null;
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

        events(fetchInfo, success, failure) {

            fetch(`/tasks/calendar/events?user_id=${selectedUser ?? ''}`)
                .then(r => r.json())
                .then(success)
                .catch(failure);

        },


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

    document.querySelectorAll('[data-user-option]')
        .forEach(button => {

            button.addEventListener('click', () => {

                selectedUser = button.dataset.userId;

                calendar.refetchEvents();

            });

        });

    const usersSelectButton = document.getElementById('usersSelectButton');
    const usersSelectMenu = document.getElementById('usersSelectMenu');
    const usersSelectValue = document.getElementById('usersSelectValue');
    const usersSearch = document.getElementById('usersSelectSearch');

    if (usersSelectButton && usersSelectMenu) {

        usersSelectButton.addEventListener('click', () => {
            usersSelectMenu.classList.toggle('hidden');
        });

        document.addEventListener('click', (e) => {

            if (!document.getElementById('usersSelectWrap').contains(e.target)) {
                usersSelectMenu.classList.add('hidden');
            }

        });

    }

    document.querySelectorAll('[data-user-option]')
        .forEach(button => {

            button.addEventListener('click', () => {

                selectedUser = button.dataset.userId;

                usersSelectValue.textContent = button.textContent.trim();

                usersSelectMenu.classList.add('hidden');

                calendar.refetchEvents();

            });

        });

    if(usersSearch){

        usersSearch.addEventListener('input', function(){

            const keyword = this.value.toLowerCase();

            document.querySelectorAll('[data-user-option]')
                .forEach(button=>{

                    button.style.display =
                        button.textContent.toLowerCase().includes(keyword)
                            ? ''
                            : 'none';

                });

        });

    }

    calendar.render();



});
