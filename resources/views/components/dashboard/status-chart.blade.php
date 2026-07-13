@props(['chart'])

<div
    class="h-full rounded-2xl border border-gray-200/70 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
    <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h3 class="text-lg font-semibold tracking-tight text-gray-900 dark:text-white/90">
                Task Status Distribution
            </h3>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                All tasks grouped by their current status
            </p>
        </div>
    </div>

    <div id="dashboard-status-chart" class="h-[280px] sm:h-[320px]"></div>
</div>