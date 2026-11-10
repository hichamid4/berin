@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')
@section('page_subtitle', 'Live overview of the BreIn marketplace, derived from stored records.')
@section('breadcrumbs')<li class="breadcrumb-item active" aria-current="page">Dashboard</li>@endsection

@section('content')
<div class="row g-3">
    <div class="col-md-6 col-xl-3">
        <div class="small-box text-bg-primary">
            <div class="inner"><h3>{{ $stats['homeowners'] + $stats['handymen'] }}</h3><p>Registered members</p><small>{{ $stats['homeowners'] }} homeowners · {{ $stats['handymen'] }} handymen</small></div>
            <i class="small-box-icon bi bi-people"></i>
            <a href="{{ route('admin.users.index') }}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">Manage users <i class="bi bi-arrow-right-short"></i></a>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="small-box text-bg-info">
            <div class="inner"><h3>{{ $stats['requests'] }}</h3><p>Service requests</p><small>{{ $stats['open_requests'] }} currently open</small></div>
            <i class="small-box-icon bi bi-clipboard-check"></i>
            <a href="{{ route('admin.requests.index') }}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">View requests <i class="bi bi-arrow-right-short"></i></a>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="small-box text-bg-warning">
            <div class="inner"><h3>{{ $stats['offers'] }}</h3><p>Offers submitted</p><small>{{ $stats['pending_offers'] }} awaiting a decision</small></div>
            <i class="small-box-icon bi bi-cash-stack"></i>
            <a href="{{ route('admin.offers.index') }}" class="small-box-footer link-dark link-underline-opacity-0 link-underline-opacity-50-hover">View offers <i class="bi bi-arrow-right-short"></i></a>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="small-box text-bg-success">
            <div class="inner"><h3>{{ $stats['rating'] ? number_format($stats['rating'], 1) : '–' }}</h3><p>Average rating</p><small>{{ $stats['reviews'] }} {{ Str::plural('review', $stats['reviews']) }}</small></div>
            <i class="small-box-icon bi bi-star"></i>
            <a href="{{ route('admin.reviews.index') }}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">Monitor reviews <i class="bi bi-arrow-right-short"></i></a>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center"><h3 class="card-title mb-0">Requests by category</h3><a class="small" href="{{ route('admin.categories.index') }}">Manage categories</a></div>
            <div class="card-body">
                @php($maxRequests = max(1, $categories->max('requests_count')))
                @foreach ($categories as $category)
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="icon-tile icon-tone-{{ ($loop->index % 8) + 1 }} d-grid rounded-3 flex-shrink-0" style="width:2.5rem;height:2.5rem;place-items:center"><i class="bi {{ $category->icon() }}"></i></span>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between small mb-1"><strong>{{ $category->name }}</strong><span class="text-secondary">{{ $category->requests_count }} {{ Str::plural('request', $category->requests_count) }} · {{ $category->services_count }} services</span></div>
                            <div class="progress progress-thin" role="progressbar" aria-valuenow="{{ $category->requests_count }}" aria-valuemin="0" aria-valuemax="{{ $maxRequests }}"><div class="progress-bar bg-primary" style="width: {{ round($category->requests_count / $maxRequests * 100) }}%"></div></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title mb-0">Request lifecycle</h3></div>
            <div class="card-body">
                @foreach (\App\RequestStatus::cases() as $status)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom"><x-status-badge :status="$status" /><strong>{{ $requestsByStatus->get($status->value, 0) }}</strong></div>
                @endforeach
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">Offer outcomes</h3></div>
            <div class="card-body">
                @foreach (\App\OfferStatus::cases() as $status)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom"><x-status-badge :status="$status" /><strong>{{ $offersByStatus->get($status->value, 0) }}</strong></div>
                @endforeach
                <div class="d-flex justify-content-between align-items-center pt-3 small"><span class="text-secondary">Value of accepted offers</span><strong>{{ number_format((float) $stats['accepted_value'], 0, ',', ' ') }} MAD</strong></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center"><h3 class="card-title mb-0">Recent activity</h3><a class="small" href="{{ route('admin.activity.index') }}">Full monitor</a></div>
            <div class="card-body">
                <ul class="activity-timeline">
                    @foreach ($recentActivity as $event)
                        <li>
                            <div class="small"><strong>{{ $event->actor?->fullName() ?? 'System' }}</strong> <span class="text-secondary">{{ str_replace('.', ' → ', $event->action) }}</span></div>
                            <small class="text-secondary">{{ $event->created_at->diffForHumans() }}@if ($event->metadata) · {{ collect($event->metadata)->map(fn ($value, $key) => "{$key}: {$value}")->join(', ') }}@endif</small>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    <div class="col-xl-7">
        <div class="card table-card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center"><h3 class="card-title mb-0">Latest requests</h3><a class="small" href="{{ route('admin.requests.index') }}">All requests</a></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Request</th><th>Homeowner</th><th>Offers</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($recentRequests as $request)
                            <tr>
                                <td><strong class="d-block text-truncate" style="max-width:16rem">{{ $request->title }}</strong><small class="text-secondary">{{ $request->service->name }} · {{ $request->service_city }}</small></td>
                                <td class="small">{{ $request->homeowner->user->fullName() }}</td>
                                <td class="fw-bold">{{ $request->offers_count }}</td>
                                <td><x-status-badge :status="$request->status" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card table-card">
            <div class="card-header d-flex justify-content-between align-items-center"><h3 class="card-title mb-0">Newest members</h3><a class="small" href="{{ route('admin.users.index') }}">All users</a></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Member</th><th>Role</th><th>Joined</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($recentUsers as $member)
                            <tr>
                                <td><div class="d-flex align-items-center gap-2"><x-avatar :user="$member" size="avatar-sm" /><div><strong class="d-block">{{ $member->fullName() }}</strong><small class="text-secondary">{{ '@'.$member->username }}</small></div></div></td>
                                <td class="small text-capitalize">{{ $member->role->value }}</td>
                                <td class="small text-nowrap">{{ $member->created_at->translatedFormat('j M Y') }}</td>
                                <td><span class="badge {{ $member->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($member->status) }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
