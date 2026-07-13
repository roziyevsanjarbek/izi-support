import './bootstrap';
import Alpine from 'alpinejs';
import ApexCharts from 'apexcharts';

// flatpickr
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

// FullCalendar
import { Calendar } from '@fullcalendar/core';

import axios from 'axios';

window.Alpine = Alpine;
window.ApexCharts = ApexCharts;
window.flatpickr = flatpickr;
window.FullCalendar = Calendar;
window.axios = axios;

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.appToast = function (message, type = 'info', title = null, timeout = 3500) {
    const wrap = document.getElementById('app-toast-wrap');
    if (!wrap || !message) return;

    const config = {
        success: {
            title: 'Success',
            classes: 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-900/20 dark:text-emerald-100',
        },
        error: {
            title: 'Error',
            classes: 'border-red-200 bg-red-50 text-red-900 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-100',
        },
        info: {
            title: 'Info',
            classes: 'border-blue-200 bg-blue-50 text-blue-900 dark:border-blue-900/40 dark:bg-blue-900/20 dark:text-blue-100',
        },
        warning: {
            title: 'Warning',
            classes: 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-100',
        },
        neutral: {
            title: 'Message',
            classes: 'border-gray-200 bg-white text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100',
        },
    };

    const item = config[type] || config.info;

    const el = document.createElement('div');
    el.className = [
        'pointer-events-auto rounded-2xl border shadow-lg px-4 py-3 transition-all duration-300 translate-x-0 opacity-100',
        item.classes,
    ].join(' ');

    el.innerHTML = `
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <div class="text-sm font-semibold">${title || item.title}</div>
                <div class="mt-1 text-sm leading-5 break-words">${message}</div>
            </div>
            <button type="button" class="shrink-0 text-sm opacity-70 hover:opacity-100">&times;</button>
        </div>
    `;

    const close = () => {
        el.classList.add('opacity-0', 'translate-x-3');
        setTimeout(() => el.remove(), 250);
    };

    el.querySelector('button').addEventListener('click', close);
    wrap.appendChild(el);

    if (timeout > 0) {
        setTimeout(close, timeout);
    }
};

window.toastSuccess = function (message, title = 'Success', timeout = 3500) {
    window.appToast(message, 'success', title, timeout);
};

window.toastError = function (message, title = 'Error', timeout = 3500) {
    window.appToast(message, 'error', title, timeout);
};

window.toastInfo = function (message, title = 'Info', timeout = 3500) {
    window.appToast(message, 'info', title, timeout);
};

window.toastWarning = function (message, title = 'Warning', timeout = 3500) {
    window.appToast(message, 'warning', title, timeout);
};

axios.interceptors.response.use(
    (response) => {
        if (response.data?.message) {
            toastSuccess(response.data.message);
        }

        return response;
    },
    (error) => {
        if (error.response?.status === 422) {
            return Promise.reject(error);
        }

        toastError(error.response?.data?.message || 'Something went wrong');

        return Promise.reject(error);
    }
);

Alpine.start();

// Initialize components on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    // Map imports
    if (document.querySelector('#mapOne')) {
        import('./components/map').then(module => module.initMap());
    }

    // Chart imports
    if (document.querySelector('#chartOne')) {
        import('./components/chart/chart-1').then(module => module.initChartOne());
    }
    if (document.querySelector('#chartTwo')) {
        import('./components/chart/chart-2').then(module => module.initChartTwo());
    }
    if (document.querySelector('#chartThree')) {
        import('./components/chart/chart-3').then(module => module.initChartThree());
    }
    if (document.querySelector('#chartSix')) {
        import('./components/chart/chart-6').then(module => module.initChartSix());
    }
    if (document.querySelector('#chartEight')) {
        import('./components/chart/chart-8').then(module => module.initChartEight());
    }
    if (document.querySelector('#chartThirteen')) {
        import('./components/chart/chart-13').then(module => module.initChartThirteen());
    }

    // Calendar init
    if (document.querySelector('#calendar')) {
        import('./components/calendar-init').then(module => module.calendarInit());
    }

    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('form.ajax-form');
        if (!form) return;

        event.preventDefault();

        try {
            const formData = new FormData(form);
            await axios.post(form.action, formData, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (form.dataset.close) {
                const modal = document.getElementById(form.dataset.close);
                if (modal) {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }
            }

            if (form.dataset.reload === '1') {
                window.location.reload();
            }
        } catch (error) {
            // interceptor toast chiqaradi
        }
    });
});