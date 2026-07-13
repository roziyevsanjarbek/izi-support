@extends('layouts.app')

@section('title', 'Zanjeer Queries')

@section('content')
@php
    $filtersInput = $filters ?? [];

    $columnLabels = [
        'id' => 'ID',
        'query_source_id' => 'Source ID',
        'custom_id' => 'Custom ID',
        'created_at' => 'Created At',
        'cargo_name' => 'Cargo',
        'state_number' => 'State Number',
        'carrier_id' => 'Carrier ID',
        'shipment_type_id' => 'Shipment Type',
        'query_status_id' => 'Status',
        'loading_at' => 'Loading',
        'payment_method' => 'Payment Method',
        'customer_id' => 'Customer ID',
        'payment_method_op' => 'Payment Method OP',
        'rate_valid_until' => 'Rate Valid Until',
        'at_the_top' => 'Top Up Time',
        'comments' => 'Comments',
        'special_conditions' => 'Special Conditions',
    ];

    $tableColumns = [
        'custom_id',
        'cargo_name',
        'created_at',
    ];

    $currentPage = (int) data_get($pagination, 'current_page', 1);
    $lastPage = (int) data_get($pagination, 'last_page', 1);
    $queryParams = request()->query();

    $hasFilters = collect($filtersInput)->filter(fn ($value) => filled($value))->isNotEmpty();
@endphp

<div class="mx-auto w-full max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-white">
                Zanjeer Queries
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Filter, search and browse queries
            </p>
        </div>

        <div class="rounded-2xl bg-gray-50 px-4 py-2 text-sm text-gray-600 dark:bg-gray-900 dark:text-gray-300">
            Total: {{ number_format(data_get($pagination, 'total', 0)) }}
        </div>
    </div>

    @include('pages.integrations.zanjeer.partials.filters', [
        'filtersInput' => $filtersInput,
        'columnLabels' => $columnLabels,
    ])

    @if($hasFilters)
        <div class="mb-4 flex flex-wrap gap-2">
            @foreach($filtersInput as $key => $value)
                @if(filled($value))
                    <span class="rounded-full border border-brand-200 bg-brand-50 px-3 py-1 text-xs font-medium text-brand-700 dark:border-brand-900/40 dark:bg-brand-900/20 dark:text-brand-200">
                        {{ $columnLabels[$key] ?? $key }}: {{ is_array($value) ? implode(', ', $value) : $value }}
                    </span>
                @endif
            @endforeach
        </div>
    @endif

    @include('pages.integrations.zanjeer.partials.table', [
        'items' => $items,
        'tableColumns' => $tableColumns,
        'columnLabels' => $columnLabels,
        'canCreateTasks' => $canCreateTasks,
    ])

    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <div class="text-sm text-gray-500 dark:text-gray-400">
            Showing {{ data_get($pagination, 'from', 0) }} to {{ data_get($pagination, 'to', 0) }} of {{ number_format(data_get($pagination, 'total', 0)) }}
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a
                href="{{ $currentPage > 1 ? route('integrations.zanjeer.queries', array_merge($queryParams, ['page' => $currentPage - 1])) : '#' }}"
                class="rounded-lg border px-3 py-2 text-sm {{ $currentPage > 1 ? 'border-gray-300 text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800' : 'pointer-events-none border-gray-200 text-gray-400 dark:border-gray-800' }}">
                Previous
            </a>

            @php
                $pages = collect([
                    1,
                    $currentPage - 2,
                    $currentPage - 1,
                    $currentPage,
                    $currentPage + 1,
                    $currentPage + 2,
                    $lastPage,
                ])
                ->filter(fn ($page) => $page >= 1 && $page <= $lastPage)
                ->unique()
                ->sort()
                ->values();
            @endphp

            @foreach ($pages as $index => $page)
                @if ($index > 0 && $page - $pages[$index - 1] > 1)
                    <span class="px-2 text-gray-500">...</span>
                @endif

                <a
                    href="{{ route('integrations.zanjeer.queries', array_merge($queryParams, ['page' => $page])) }}"
                    class="rounded-lg px-3 py-2 text-sm {{ $page === $currentPage ? 'bg-brand-500 text-white' : 'border border-gray-300 text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800' }}">
                    {{ $page }}
                </a>
            @endforeach

            <a
                href="{{ $currentPage < $lastPage ? route('integrations.zanjeer.queries', array_merge($queryParams, ['page' => $currentPage + 1])) : '#' }}"
                class="rounded-lg border px-3 py-2 text-sm {{ $currentPage < $lastPage ? 'border-gray-300 text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800' : 'pointer-events-none border-gray-200 text-gray-400 dark:border-gray-800' }}">
                Next
            </a>
        </div>
    </div>
</div>

@include('pages.integrations.zanjeer.partials.modal')

@include('pages.integrations.zanjeer.partials.scripts', [
    'columnLabels' => $columnLabels,
])
@endsection