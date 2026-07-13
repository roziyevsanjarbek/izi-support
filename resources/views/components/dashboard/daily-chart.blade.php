@props(['chart'])

<div class="rounded-2xl border border-gray-200 bg-white px-5 pb-5 pt-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6">
    <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                Tasks Created in the Last 30 Days
            </h3>
            <p class="mt-1 text-gray-500 text-theme-sm dark:text-gray-400">
                Daily task creation trend
            </p>
        </div>
    </div>

    <div id="dashboard-daily-chart" class="h-[340px]"></div>
</div>