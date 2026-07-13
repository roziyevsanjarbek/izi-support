<div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-800/40">
                <tr>
                    @foreach($tableColumns as $column)
                        @if($column === 'cargo_name')
                            <th class="px-5 py-4 text-left text-sm font-medium text-gray-500">
                                Cargo / Operation
                            </th>
                        @else
                            <th class="px-5 py-4 text-left text-sm font-medium text-gray-500">
                                {{ $columnLabels[$column] ?? $column }}
                            </th>
                        @endif
                    @endforeach
                    <th class="px-5 py-4 text-left text-sm font-medium text-gray-500">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse($items as $item)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                        @foreach($tableColumns as $column)
                            @if($column === 'cargo_name')
                                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    <div class="flex max-w-[420px] flex-col">
                                        <span class="truncate text-sm font-medium text-gray-900 dark:text-white">
                                            {{ data_get($item, 'cargo_name', '-') }}
                                        </span>

                                        <span class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400">
                                            {{ data_get($item, 'operations.0.user.name', '-') }}
                                        </span>
                                    </div>
                                </td>
                            @elseif($column === 'created_at')
                                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    <span class="inline-flex max-w-[280px] truncate">
                                        @php($date = data_get($item, 'created_at'))

                                        {{ $date
                                            ? \Carbon\Carbon::parse($date)->setTimezone('Asia/Tashkent')->format('d.m.Y H:i')
                                            : '-'
                                        }}
                                    </span>
                                </td>
                            @else
                                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    <span class="inline-flex max-w-[280px] truncate">
                                        {{ data_get($item, $column, '-') }}
                                    </span>
                                </td>
                            @endif
                        @endforeach

                        <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    data-show-query
                                    data-record='@json($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG)'
                                    class="inline-flex items-center justify-center rounded-lg border border-brand-200 bg-brand-50 px-4 py-2 text-sm font-medium text-brand-700 hover:bg-brand-100 dark:border-brand-900/40 dark:bg-brand-900/20 dark:text-brand-200 dark:hover:bg-brand-900/30">
                                    Show
                                </button>

                                @if($canCreateTasks)
                                    <button
                                        type="button"
                                        data-open-task
                                        data-record='@json($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG)'
                                        class="inline-flex items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-100 dark:border-emerald-900/40 dark:bg-emerald-900/20 dark:text-emerald-200 dark:hover:bg-emerald-900/30">
                                        Add Task
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($tableColumns) + 1 }}" class="px-5 py-10 text-center text-sm text-gray-500">
                            No queries found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>