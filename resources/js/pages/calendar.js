import { Calendar } from '@fullcalendar/core'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'

const calendarEl = document.getElementById('calendar')

const modal = document.getElementById('eventModal')

const calendar = new Calendar(calendarEl, {
    plugins: [
        dayGridPlugin,
        timeGridPlugin,
        interactionPlugin,
    ],

    initialView: 'dayGridMonth',

    selectable: true,
    editable: true,

    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay',
    },

    events: [],

    select(info) {
        modal.classList.remove('hidden')
        modal.classList.add('flex')

        document.querySelector(
            '[name="start_at"]'
        ).value = info.startStr
    },

    eventClick(info) {
        console.log(info.event)
    },

    eventDrop(info) {
        console.log(info.event)
    },

    eventResize(info) {
        console.log(info.event)
    },
})

calendar.render()

document
    .getElementById('createEventBtn')
    .addEventListener('click', () => {
        modal.classList.remove('hidden')
        modal.classList.add('flex')
    })

document
    .getElementById('closeModal')
    .addEventListener('click', closeModal)

document
    .getElementById('cancelModal')
    .addEventListener('click', closeModal)

function closeModal() {
    modal.classList.add('hidden')
    modal.classList.remove('flex')
}