@props(['stats'])

<div class="rounded-2xl border border-gray-200 bg-white px-5 pb-5 pt-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6">
    <div class="flex flex-col gap-5 mb-6 sm:flex-row sm:justify-between">
        <div class="w-full">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                Overview
            </h3>
            <p class="mt-1 text-gray-500 text-theme-sm dark:text-gray-400">
                Key task and user metrics
            </p>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Tasks</div>
            <div class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $stats['tasks_total'] }}</div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Users</div>
            <div class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $stats['users_total'] }}</div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="text-sm text-gray-500 dark:text-gray-400">Pending</div>
            <div class="mt-2 text-3xl font-semibold text-amber-600 dark:text-amber-400">{{ $stats['pending_total'] }}</div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="text-sm text-gray-500 dark:text-gray-400">In Progress</div>
            <div class="mt-2 text-3xl font-semibold text-blue-600 dark:text-blue-400">{{ $stats['in_progress_total'] }}</div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="text-sm text-gray-500 dark:text-gray-400">Completed</div>
            <div class="mt-2 text-3xl font-semibold text-emerald-600 dark:text-emerald-400">{{ $stats['completed_total'] }}</div>
        </div>
    </div>
</div>