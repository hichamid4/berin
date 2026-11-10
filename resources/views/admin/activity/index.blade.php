@extends('layouts.admin')

@section('title', 'Activity monitor')
@section('page_title', 'Activity monitor')
@section('page_subtitle', 'Persisted platform events: registrations, requests, offers, completions and reviews.')
@section('breadcrumbs')<li class="breadcrumb-item active" aria-current="page">Activity</li>@endsection

@section('content')
<form class="card mb-3" method="get" action="{{ route('admin.activity.index') }}">
    <div class="card-body">
        <div class="row g-2 align-items-center">
            <div class="col-md-4">
                <select class="form-select" name="action">
                    <option value="">All event types</option>
                    @foreach ($actions as $option)<option value="{{ $option }}" @selected($action === $option)>{{ $option }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2 d-grid"><button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button></div>
        </div>
    </div>
</form>
<div class="card table-card">
    <div class="card-header"><h3 class="card-title mb-0">{{ $events->total() }} {{ Str::plural('event', $events->total()) }}</h3></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>When</th><th>Actor</th><th>Event</th><th>Subject</th><th>Details</th></tr></thead>
            <tbody>
                @forelse ($events as $event)
                    <tr>
                        <td class="small text-nowrap">{{ $event->created_at->translatedFormat('j M Y H:i') }}<span class="text-secondary d-block">{{ $event->created_at->diffForHumans() }}</span></td>
                        <td>@if ($event->actor)<div class="d-flex align-items-center gap-2"><x-avatar :user="$event->actor" size="avatar-sm" /><span class="small fw-bold">{{ $event->actor->fullName() }}</span></div>@else<span class="small text-secondary">System</span>@endif</td>
                        <td><span class="chip">{{ $event->action }}</span></td>
                        <td class="small">{{ $event->subject_type }} #{{ $event->subject_id }}</td>
                        <td class="small text-secondary">{{ $event->metadata ? collect($event->metadata)->map(fn ($value, $key) => "{$key}: {$value}")->join(', ') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-secondary py-4">No event recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($events->hasPages())<div class="card-footer">{{ $events->links() }}</div>@endif
</div>
@endsection
