@extends('layouts.dashboard')

@section('title', 'Homeowner dashboard')
@section('role_label', 'Homeowner')
@section('subtitle', ($profile->city ? $profile->city.' · ' : '').$stats['total'].' '.Str::plural('request', $stats['total']).' so far')

@section('hero_actions')
    <a class="btn btn-accent" href="{{ route('homeowner.requests.create') }}"><i class="bi bi-plus-circle me-1"></i>Post a new request</a>
@endsection

@section('dashboard')
    <div id="overview" class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><x-stat-tile icon="bi-clipboard-check" label="Total requests" :value="$stats['total']" tone="icon-tone-1" /></div>
        <div class="col-6 col-xl-3"><x-stat-tile icon="bi-unlock" label="Open for offers" :value="$stats['open']" tone="icon-tone-4" /></div>
        <div class="col-6 col-xl-3"><x-stat-tile icon="bi-hammer" label="Jobs in progress" :value="$stats['active']" tone="icon-tone-2" /></div>
        <div class="col-6 col-xl-3"><x-stat-tile icon="bi-check2-circle" label="Completed" :value="$stats['completed']" tone="icon-tone-7" /></div>
    </div>

    @if ($awaitingConfirmation->isNotEmpty())
        <div class="alert alert-warning d-flex align-items-start gap-3 mb-4">
            <i class="bi bi-hourglass-split fs-4"></i>
            <div>
                <strong>{{ $awaitingConfirmation->count() }} {{ Str::plural('job', $awaitingConfirmation->count()) }} awaiting your confirmation.</strong>
                <div class="small">The handyman marked the work as done. Confirming completion will unlock the review step.</div>
            </div>
        </div>
    @endif

    <div id="requests" class="card mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h2 class="h5 mb-0">My requests</h2>
                <span class="small text-secondary">{{ $stats['offers'] }} {{ Str::plural('offer', $stats['offers']) }} received in total</span>
            </div>
            @if ($requests->isEmpty())
                <x-empty-state icon="bi-clipboard-plus" title="You have not posted a request yet" text="Describe the work you need and receive offers from qualified handymen."><a class="btn btn-primary mt-3" href="{{ route('homeowner.requests.create') }}">Post a request</a></x-empty-state>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead><tr><th>Request</th><th>Service</th><th>Start date</th><th>Offers</th><th>Status</th><th>Handyman</th></tr></thead>
                        <tbody>
                            @foreach ($requests as $request)
                                <tr>
                                    <td class="min-w-0"><a class="fw-bold d-block text-truncate text-decoration-none" style="max-width:18rem" href="{{ route('homeowner.requests.show', $request) }}">{{ $request->title }}</a><small class="text-secondary"><i class="bi bi-geo-alt me-1"></i>{{ $request->service_city }}</small></td>
                                    <td><span class="small fw-bold"><i class="bi {{ $request->service->category->icon() }} text-primary me-1"></i>{{ $request->service->name }}</span></td>
                                    <td class="text-nowrap small">{{ $request->desired_start_date->translatedFormat('j M Y') }}</td>
                                    <td><span class="fw-bold">{{ $request->offers_count }}</span>@if ($request->pending_offers_count) <small class="text-warning-emphasis">({{ $request->pending_offers_count }} new)</small>@endif</td>
                                    <td><x-status-badge :status="$request->status" /></td>
                                    <td>
                                        @if ($request->acceptedOffer)
                                            <div class="d-flex align-items-center gap-2"><x-avatar :user="$request->acceptedOffer->handyman->user" size="avatar-sm" /><div class="small lh-sm"><a class="fw-bold text-decoration-none" href="{{ route('handymen.show', $request->acceptedOffer->handyman) }}">{{ $request->acceptedOffer->handyman->user->fullName() }}</a><div class="text-secondary">{{ $request->acceptedOffer->formattedPrice() }}</div></div></div>
                                        @else
                                            <span class="text-secondary small">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-7">
            <div id="offers" class="card h-100">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Offers waiting for your decision</h2>
                    @if ($pendingOffers->isEmpty())
                        <x-empty-state icon="bi-inbox" title="No pending offers" text="New offers from qualified handymen will appear here." />
                    @else
                        @foreach ($pendingOffers as $offer)
                            <div class="list-row">
                                <x-avatar :user="$offer->handyman->user" />
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex flex-wrap justify-content-between gap-2">
                                        <a class="fw-bold text-decoration-none" href="{{ route('handymen.show', $offer->handyman) }}">{{ $offer->handyman->user->fullName() }}</a>
                                        <span class="price-tag">{{ number_format((float) $offer->price, 0, ',', ' ') }}<small>MAD</small></span>
                                    </div>
                                    <a class="small text-secondary text-truncate d-block" href="{{ route('homeowner.requests.show', $offer->request_id) }}#offers">for “{{ $offer->request->title }}” — review and decide</a>
                                    <p class="small mb-1 mt-1">{{ Str::limit($offer->description, 140) }}</p>
                                    <small class="text-secondary"><i class="bi bi-calendar-range me-1"></i>{{ $offer->start_date->translatedFormat('j M') }} – {{ $offer->end_date->translatedFormat('j M Y') }} · sent {{ $offer->created_at->diffForHumans() }}</small>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div id="favorites" class="card h-100">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Favorite handymen</h2>
                    @if ($favorites->isEmpty())
                        <x-empty-state icon="bi-heart" title="No favorites yet" text="Save handymen you like to find them again quickly." />
                    @else
                        @foreach ($favorites as $handyman)
                            <div class="list-row align-items-center">
                                <x-avatar :user="$handyman->user" />
                                <div class="flex-grow-1 min-w-0">
                                    <a class="fw-bold text-decoration-none d-block text-truncate" href="{{ route('handymen.show', $handyman) }}">{{ $handyman->user->fullName() }}</a>
                                    <small class="text-secondary">{{ $handyman->skills->pluck('name')->take(2)->join(', ') }} · {{ $handyman->city }}</small>
                                </div>
                                <div class="text-end"><x-rating :value="$handyman->reviews_avg_rating" :count="$handyman->reviews_count" />
                                    <form method="post" action="{{ route('homeowner.favorites.destroy', $handyman) }}" class="mt-1">@csrf @method('DELETE')<button class="btn btn-link btn-sm text-danger p-0" type="submit"><i class="bi bi-heartbreak me-1"></i>Remove</button></form></div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
