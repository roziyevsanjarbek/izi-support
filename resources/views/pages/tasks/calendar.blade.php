@extends('layouts.app')

@section('title','Task Calendar')

@section('content')
    <div class="rounded-xl bg-white shadow p-6 dark:bg-gray-900">

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">
                Task Calendar
            </h1>

            <div class="flex gap-4 text-sm">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-red-500"></span>
                    High
                </div>

                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
                    Medium
                </div>

                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-green-500"></span>
                    Low
                </div>
            </div>
        </div>

        <div id="task-calendar"></div>

    </div>
@endsection
