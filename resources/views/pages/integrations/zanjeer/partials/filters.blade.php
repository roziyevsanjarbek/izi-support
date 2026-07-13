<div class="mb-6 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
    <form method="GET" action="{{ route('integrations.zanjeer.queries') }}" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <input type="hidden" name="sort" value="{{ request('sort', '-id') }}">


        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Custom ID</label>
            <input
                type="text"
                name="filter[custom_id]"
                value="{{ data_get($filtersInput, 'custom_id') }}"
                placeholder="EGS00016"
                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-none focus:border-brand-500 dark:border-gray-700 dark:text-white"
            />
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Cargo</label>
            <input
                type="text"
                name="filter[cargo_name]"
                value="{{ data_get($filtersInput, 'cargo_name') }}"
                placeholder="Cargo name"
                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-none focus:border-brand-500 dark:border-gray-700 dark:text-white"
            />
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Loading Date</label>
            <input
                type="date"
                name="filter[loading_at]"
                value="{{ data_get($filtersInput, 'loading_at') }}"
                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-none focus:border-brand-500 dark:border-gray-700 dark:text-white"
            />
        </div>

        <div class="flex items-end gap-3">
            <button
                type="submit"
                class="inline-flex h-11 items-center justify-center rounded-lg bg-brand-500 px-5 text-sm font-medium text-white hover:bg-brand-600">
                Search
            </button>

            <a
                href="{{ route('integrations.zanjeer.queries') }}"
                class="inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 px-5 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                Reset
            </a>
        </div>
    </form>
</div>