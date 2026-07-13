<div
    class="relative isolate overflow-visible z-[999999]"
    x-data="{
        dropdownOpen: false,
        toggleDropdown() { this.dropdownOpen = !this.dropdownOpen },
        closeDropdown() { this.dropdownOpen = false }
    }"
    @click.outside="closeDropdown()"
>
    <button
        type="button"
        class="flex items-center text-gray-700 dark:text-gray-400"
        @click.prevent="toggleDropdown()"
    >
        @php
            $user = auth()->user();
            $initial = strtoupper(substr($user->name, 0, 1));
        @endphp

        <span class="mr-3 flex h-11 w-11 items-center justify-center rounded-full bg-gray-200 font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-200">
            {{ $initial }}
        </span>

        <span class="mr-1 block font-medium text-theme-sm">
            {{ $user->name }}
        </span>

        <svg
            class="h-5 w-5 transition-transform duration-200"
            :class="{ 'rotate-180': dropdownOpen }"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M19 9l-7 7-7-7"
            ></path>
        </svg>
    </button>

    <div
        x-cloak
        x-show="dropdownOpen"
        x-transition.origin.top.right
        class="absolute right-0 top-full mt-2 w-[260px] rounded-2xl border border-gray-200 bg-white p-3 shadow-2xl z-[9999999] dark:border-gray-800 dark:bg-gray-dark"
        style="display: none;"
    >
        <div class="border-b border-gray-200 pb-3 dark:border-gray-800">
            <span class="block font-medium text-gray-700 dark:text-gray-300">
                {{ $user->name }}
            </span>
            <span class="block text-sm text-gray-500 dark:text-gray-400">
                {{ $user->email }}
            </span>
        </div>

        <ul class="flex flex-col gap-1 pt-3">
            <li>
                <a href="{{ route('profile') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M5.121 17.804A10 10 0 1118.879 6.196M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Profile
                </a>
            </li>
        </ul>

        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit"
                class="flex w-full items-center gap-3 rounded-lg px-3 py-2 font-medium text-red-600 hover:bg-red-50 dark:hover:bg-white/5">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Logout
            </button>
        </form>
    </div>
</div>