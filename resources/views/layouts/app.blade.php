<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') | IziSupport</title>


    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js -->
    {{--
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script> --}}

    <!-- Theme Store -->
    @php
    $sidebarDefault = $__env->yieldContent('sidebar_default', 'auto');
    $sidebarHoverEnabled = filter_var($__env->yieldContent('sidebar_hover_enabled', 'true'), FILTER_VALIDATE_BOOLEAN);
@endphp

<script>
    window.appLayout = {
        sidebarDefault: @json($sidebarDefault),
        sidebarHoverEnabled: @json($sidebarHoverEnabled),
    };
</script>
    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('theme', {
            init() {
                const savedTheme = localStorage.getItem('theme');
                const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                this.theme = savedTheme || systemTheme;
                this.updateTheme();
            },
            theme: 'light',
            toggle() {
                this.theme = this.theme === 'light' ? 'dark' : 'light';
                localStorage.setItem('theme', this.theme);
                this.updateTheme();
            },
            updateTheme() {
                const html = document.documentElement;
                const body = document.body;

                if (this.theme === 'dark') {
                    html.classList.add('dark');
                    body.classList.add('dark', 'bg-gray-900');
                } else {
                    html.classList.remove('dark');
                    body.classList.remove('dark', 'bg-gray-900');
                }
            }
        });

        const sidebarDefault = window.appLayout?.sidebarDefault || 'auto';
        const sidebarHoverEnabled = window.appLayout?.sidebarHoverEnabled ?? true;

        Alpine.store('sidebar', {
    hoverEnabled: sidebarHoverEnabled,

    isExpanded: sidebarDefault === 'collapsed'
        ? false
        : sidebarDefault === 'expanded'
            ? true
            : window.innerWidth >= 1280,

    isMobileOpen: false,
    isHovered: false,

    toggleExpanded() {
        this.isExpanded = !this.isExpanded;
        this.isMobileOpen = false;
        this.isHovered = false;
    },

    toggleMobileOpen() {
        this.isMobileOpen = !this.isMobileOpen;
    },

    setMobileOpen(val) {
        this.isMobileOpen = val;
    },

    setHovered(val) {
        if (!this.hoverEnabled) return;
        if (window.innerWidth >= 1280 && !this.isExpanded) {
            this.isHovered = val;
        }
    }
});
    });
</script>

    <!-- Apply dark mode immediately to prevent flash -->
    <script>
        (function () {
            const savedTheme = localStorage.getItem('theme');
            const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            const theme = savedTheme || systemTheme;
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
                document.body.classList.add('dark', 'bg-gray-900');
            } else {
                document.documentElement.classList.remove('dark');
                document.body.classList.remove('dark', 'bg-gray-900');
            }
        })();
    </script>

</head>
<div id="app-toast-wrap" class="fixed right-4 top-4 z-[99999] flex w-full max-w-sm flex-col gap-3 pointer-events-none">
</div>

<script>
    window.appToast = function (message, type = 'info') {
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
            neutral: {
                title: 'Message',
                classes: 'border-gray-200 bg-white text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100',
            },
        };

        const item = config[type] || config.info;

        const el = document.createElement('div');
        el.className = [
            'pointer-events-auto rounded-2xl border shadow-lg px-4 py-3 transition-all duration-300 translate-x-0 opacity-100',
            item.classes
        ].join(' ');

        el.innerHTML = `
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-sm font-semibold">${item.title}</div>
                    <div class="mt-1 text-sm leading-5">${message}</div>
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

        setTimeout(close, 3500);
    };

    @if (session('toast.message'))
        document.addEventListener('DOMContentLoaded', function () {
            window.appToast(@json(session('toast.message')), @json(session('toast.type', 'info')));
        });
    @endif
</script>

<body x-data="{ loaded: true }" x-init="loaded = true">
    <div id="app-toast-wrap"
        class="fixed top-4 right-4 z-[2147483647] flex w-full max-w-sm flex-col gap-3 px-4 pointer-events-none sm:px-0">
    </div>

    <x-common.preloader />

    <div class="min-h-screen xl:flex">
        @include('layouts.backdrop')
        @include('layouts.sidebar')

        <div class="flex-1 transition-all duration-300 ease-in-out" :class="{
                'xl:ml-[290px]': $store.sidebar.isExpanded || $store.sidebar.isHovered,
                'xl:ml-[90px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered,
                'ml-0': $store.sidebar.isMobileOpen
            }">
            @include('layouts.app-header')

            @php
                $contentWrapperClass = trim($__env->yieldContent(
                    'content_wrapper_class',
                    'p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6'
                ));
            @endphp

            <div class="{{ $contentWrapperClass }}">
                @yield('content')
            </div>
        </div>
    </div>
</body>

@stack('scripts')

</html>