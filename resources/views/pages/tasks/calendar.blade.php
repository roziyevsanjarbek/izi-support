@extends('layouts.app')

@section('title','Task Calendar')

@section('content')
    <div class="rounded-xl bg-white shadow p-6 dark:bg-gray-900">

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                Task Calendar
            </h1>
            <div class="flex items-center gap-4">
            @if(auth()->user()->isSuperAdmin())

                <div class="relative" id="usersSelectWrap">

                    <button id="usersSelectButton"
                            class="inline-flex min-w-[220px] items-center justify-between rounded-lg border px-4 py-2">

                            <span id="usersSelectValue" class="text-gray-900 dark:text-white">
                                {{ optional($users->firstWhere('id',$selectedUserId))->name ?? 'All users' }}
                            </span>

                        <svg
                            class="w-4 h-4"
                            viewBox="0 0 20 20"
                            fill="none">

                            <path
                                d="M5 7l5 5 5-5"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"/>

                        </svg>

                    </button>

                    <div id="usersSelectMenu"
                         class="hidden absolute right-0 mt-2 z-50 w-80 rounded-xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-900">
                        <input
                            id="usersSelectSearch"
                            class="w-full border-b p-3 bg-white text-gray-900 placeholder-gray-500 dark:bg-gray-900 dark:text-white dark:placeholder-gray-400 dark:border-gray-700"
                            placeholder="Search user..."
                        >

                        <div class="max-h-80 overflow-y-auto">

                            <button
                                data-user-option
                                data-user-id=""
                                class="block w-full px-3 py-2 text-left text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-800">
                                All users
                            </button>

                            @foreach($users as $user)

                                <button
                                    data-user-option
                                    data-user-id="{{ $user->id }}"
                                    class="block w-full px-3 py-2 text-left text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-800">
                                    {{ $user->name }}
                                </button>

                            @endforeach

                        </div>

                    </div>

                </div>

            @endif
{{--            <div class="flex gap-4 text-sm">--}}
{{--                <div class="flex items-center gap-2">--}}
{{--                    <span class="w-3 h-3 rounded-full bg-red-500"></span>--}}
{{--                    High--}}
{{--                </div>--}}

{{--                <div class="flex items-center gap-2">--}}
{{--                    <span class="w-3 h-3 rounded-full bg-yellow-500"></span>--}}
{{--                    Medium--}}
{{--                </div>--}}

{{--                <div class="flex items-center gap-2">--}}
{{--                    <span class="w-3 h-3 rounded-full bg-green-500"></span>--}}
{{--                    Low--}}
{{--                </div>--}}
{{--            </div>--}}
        </div>
        </div>
        <div id="task-calendar"></div>

    </div>
@endsection
