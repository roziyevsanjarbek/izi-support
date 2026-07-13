@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <x-dashboard.summary-cards :stats="$stats" />

    <div class="grid gap-6 lg:grid-cols-3">
    <div>
        <x-dashboard.status-chart :chart="$charts['status']" />
    </div>

    <div>
        <x-dashboard.creators-chart :chart="$charts['creators']" />
    </div>

    <div>
        <x-dashboard.completers-chart :chart="$charts['completers']" />
    </div>

    <div class="lg:col-span-3">
        <x-dashboard.daily-chart :chart="$charts['daily']" />
        
    </div>
</div>

    <x-dashboard.scripts :charts="$charts" />
</div>
@endsection