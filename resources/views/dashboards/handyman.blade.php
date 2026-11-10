@extends('layouts.dashboard')

@section('title', 'Handyman dashboard')
@section('role_label', 'Handyman')
@section('subtitle', ($profile->city ? $profile->city.' · ' : '').$profile->skills->count().' declared '.Str::plural('skill', $profile->skills->count()))

@section('hero_actions')
    <a class="btn btn-accent" href="{{ route('handyman.requests.index') }}"><i class="bi bi-bullseye me-1"></i>Browse matching requests</a>
    <a class="btn btn-outline-light" href="{{ route('handymen.show', $profile) }}"><i class="bi bi-person-badge me-1"></i>Public profile</a>
@endsection

@section('dashboard')
    <div id="overview" class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><x-stat-tile icon="bi-bullseye" label="Matching open requests" :value="$stats['matching']" tone="icon-tone-1" /></div>
        <div class="col-6 col-xl-3"><x-stat-tile icon="bi-hourglass-split" label="Pending offers" :value="$stats['pending']" tone="icon-tone-2" /></div>
        <div class="col-6 col-xl-3"><x-stat-tile icon="bi-check2-circle" label="Jobs completed" :value="$stats['completed']" tone="icon-tone-7" /></div>
        <div class="col-6 col-xl-3"><x-stat-tile icon="bi-star-fill" label="Average rating" :value="$stats['rating'] ? number_format($stats['rating'], 1).' / 5' : 'New'" tone="icon-tone-3" /></div>
    </div>

    <div class="card mb-4 border-0 text-white" style="background:linear-gradient(120deg,var(--brein-navy),#0f4b45)">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="small text-uppercase fw-bold" style="letter-spacing:.1em;opacity:.7">Completed work value</div>
                <div class="display-6 fw-bold">{{ number_format($stats['earnings'], 0, ',', ' ') }} <span class="fs-5">MAD</span></div>
            </div>
            <div class="text-end small" style="opacity:.8">Based on {{ $stats['completed'] }} accepted {{ Str::plural('offer', $stats['completed']) }} on completed requests.</div>
        </div>
    </div>

    <div id="matching" class="mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h5 mb-0">Open requests matching your skills</h2>
            <span class="small text-secondary">Only requests whose service needs one of your skills are shown</span>
        </div>
        @if ($matchingRequests->isEmpty())
            <x-empty-state icon="bi-bullseye" title="No new matching request right now" text="You will see new requests here as soon as homeowners post them." />
        @else
            <div class="row g-3">
                @foreach ($matchingRequests as $request)
                    <div class="col-md-6"><x-request-card :request="$request" :show-status="false" :url="route('handyman.requests.show', $request)" /></div>
                @endforeach
            </div>
        @endif
    </div>

    <div id="jobs" class="card mb-4">
        <div class="card-body p-4">
            <h2 class="h5 mb-3">Active jobs</h2>
            @if ($activeJobs->isEmpty())
                <x-empty-state icon="bi-hammer" title="No active job" text="Accepted offers on booked requests appear here." />
            @else
                @foreach ($activeJobs as $offer)
                    <div class="list-row align-items-center">
                        <span class="icon-tile icon-tone-{{ ($loop->index % 8) + 1 }} d-grid rounded-3 flex-shrink-0" style="width:2.75rem;height:2.75rem;place-items:center"><i class="bi {{ $offer->request->service->category->icon() }}"></i></span>
                        <div class="flex-grow-1 min-w-0">
                            <a class="fw-bold d-block text-truncate text-decoration-none" href="{{ route('handyman.requests.show', $offer->request_id) }}">{{ $offer->request->title }}</a>
                            <small class="text-secondary"><i class="bi bi-geo-alt me-1"></i>{{ $offer->request->service_city }} · {{ $offer->start_date->translatedFormat('j M') }} – {{ $offer->end_date->translatedFormat('j M Y') }}</small>
                        </div>
                        <div class="text-end"><div class="price-tag">{{ number_format((float) $offer->price, 0, ',', ' ') }}<small>MAD</small></div><x-status-badge :status="$offer->request->status" />
                            @if ($offer->request->status === \App\RequestStatus::Booked)
                                <form method="post" action="{{ route('handyman.offers.complete', $offer) }}" class="mt-2" data-confirm="Mark this job as completed? The homeowner will be asked to confirm." data-confirm-button="Mark as completed" data-confirm-variant="success">@csrf<button class="btn btn-sm btn-success" type="submit"><i class="bi bi-check2-circle me-1"></i>Mark done</button></form>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <div id="offers" class="card mb-4">
        <div class="card-body p-4">
            <h2 class="h5 mb-3">My offers</h2>
            @if ($offers->isEmpty())
                <x-empty-state icon="bi-cash-stack" title="You have not sent an offer yet" />
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead><tr><th>Request</th><th>Service</th><th>Price</th><th>Dates</th><th>Offer status</th><th>Request</th></tr></thead>
                        <tbody>
                            @foreach ($offers as $offer)
                                <tr>
                                    <td class="min-w-0"><a class="fw-bold d-block text-truncate text-decoration-none" style="max-width:16rem" href="{{ route('handyman.requests.show', $offer->request_id) }}">{{ $offer->request->title }}</a><small class="text-secondary">{{ $offer->request->service_city }} · sent {{ $offer->created_at->diffForHumans() }}</small></td>
                                    <td class="small fw-bold">{{ $offer->request->service->name }}</td>
                                    <td class="text-nowrap fw-bold">{{ $offer->formattedPrice() }}</td>
                                    <td class="text-nowrap small">{{ $offer->start_date->translatedFormat('j M') }} – {{ $offer->end_date->translatedFormat('j M Y') }}</td>
                                    <td><x-status-badge :status="$offer->status" /></td>
                                    <td><x-status-badge :status="$offer->request->status" /></td>
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
            <div id="reviews" class="card h-100">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Latest reviews</h2>
                    @if ($reviews->isEmpty())
                        <x-empty-state icon="bi-star" title="No reviews yet" text="Homeowners can review you after confirming a completed job." />
                    @else
                        @foreach ($reviews as $review)
                            <div class="list-row">
                                <x-avatar :user="$review->offer->request->homeowner->user" />
                                <div class="flex-grow-1">
                                    <div class="d-flex flex-wrap justify-content-between gap-2"><strong>{{ $review->offer->request->homeowner->user->first_name }} {{ Str::substr($review->offer->request->homeowner->user->last_name, 0, 1) }}.</strong><small class="text-secondary">{{ $review->created_at->translatedFormat('j M Y') }}</small></div>
                                    <x-rating :value="$review->rating" :show-value="false" class="my-1" />
                                    <p class="small mb-1">{{ $review->comment }}</p>
                                    <small class="text-secondary">{{ $review->offer->request->service->name }}</small>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div id="skills" class="card h-100">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">My skills</h2>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($profile->skills as $skill)
                            <span class="chip" title="{{ $skill->description }}"><i class="bi bi-patch-check-fill"></i>{{ $skill->name }}</span>
                        @endforeach
                    </div>
                    <p class="small text-secondary mt-3 mb-2">Skills determine which requests you can see and offer on.</p>
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('account.edit') }}#skills"><i class="bi bi-pencil me-1"></i>Edit my skills</a>
                </div>
            </div>
        </div>
    </div>
@endsection
