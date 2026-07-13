@extends('layouts.app')

@section('content')
<div
    class="mx-auto w-full max-w-screen-2xl p-4 md:p-6 2xl:p-10"
    x-data="userPage()"
    x-cloak
>
    @php($isSuperadmin = auth()->user()?->hasRole('superadmin'))

    @include('partials.users.header')

    @include('partials.users.filters')

    @include('partials.users.table')

    <div class="mt-6 rounded-2xl border border-gray-200 bg-white px-4 py-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 md:px-6">
        {{ $users->links() }}
    </div>

    @include('partials.users.modals')
</div>
@endsection

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@push('scripts')
@include('partials.users.scripts')
@endpush