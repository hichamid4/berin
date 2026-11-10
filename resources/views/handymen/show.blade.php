@extends('layouts.public')

@section('title', $handyman->user->fullName().' · Handyman')

@section('hero')
<section class="page-hero">
    <div class="container">
        <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li><li class="breadcrumb-item"><a href="{{ route('handymen.index') }}">Handymen</a></li><li class="breadcrumb-item active" aria-current="page">{{ $handyman->user->fullName() }}</li></ol></nav>
        <div class="d-flex flex-wrap align-items-center gap-4">
            <x-avatar :user="$handyman->user" size="avatar-xl" class="border border-3 border-white shadow" />
            <div>
                <h1>{{ $handyman->user->fullName() }}</h1>
                <p class="mb-2"><i class="bi bi-geo-alt-fill me-1"></i>{{ $handyman->city ?? 'Morocco' }} · Member since {{ $handyman->user->created_at->translatedFormat('F Y') }}</p>
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <x-rating :value="$handyman->reviews_avg_rating" :count="$handyman->reviews_count" class="text-white" />
                    <span class="badge text-bg-light"><i class="bi bi-check2-circle me-1"></i>{{ $handyman->completed_jobs_count }} jobs completed</span>
                    <span class="badge text-bg-light"><i class="bi bi-heart-fill me-1 text-danger"></i>{{ $handyman->favorited_by_count }} favorites</span>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('content')
<section class="section pt-4">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Skills</h2>
                        <div class="row g-3">
                            @foreach ($handyman->skills as $skill)
                                <div class="col-sm-6">
                                    <div class="d-flex gap-3 p-3 rounded-3 bg-surface h-100">
                                        <span class="icon-tile icon-tone-{{ ($loop->index % 8) + 1 }} d-grid rounded-3 flex-shrink-0" style="width:2.5rem;height:2.5rem;place-items:center"><i class="bi bi-patch-check-fill"></i></span>
                                        <div><strong class="d-block">{{ $skill->name }}</strong><small class="text-secondary">{{ $skill->description }}</small></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Services {{ $handyman->user->first_name }} can take on</h2>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($services as $service)
                                <a class="chip text-decoration-none" href="{{ route('services.show', $service) }}"><i class="bi {{ $service->category->icon() }}"></i>{{ $service->name }}</a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                            <div>
                                <h2 class="h5 mb-1">Reviews</h2>
                                <p class="text-secondary small mb-0">Left by homeowners after a completed and confirmed job.</p>
                            </div>
                            <div class="text-end">
                                <div class="display-6 fw-bold lh-1">{{ $handyman->reviews_avg_rating ? number_format($handyman->reviews_avg_rating, 1) : '–' }}</div>
                                <x-rating :value="$handyman->reviews_avg_rating" :show-value="false" />
                                <div class="small text-secondary">{{ $handyman->reviews_count }} {{ Str::plural('review', $handyman->reviews_count) }}</div>
                            </div>
                        </div>
                        @if ($reviews->isEmpty())
                            <x-empty-state icon="bi-star" title="No reviews yet" text="Reviews appear once a homeowner confirms a completed job." />
                        @else
                            <div class="mb-4">
                                @foreach ($ratingBreakdown as $stars => $count)
                                    <div class="d-flex align-items-center gap-2 small mb-1">
                                        <span class="text-muted-strong" style="width:3.5rem">{{ $stars }} <i class="bi bi-star-fill text-accent"></i></span>
                                        <div class="progress flex-grow-1 progress-thin" style="height:.5rem" role="progressbar" aria-valuenow="{{ $count }}" aria-valuemin="0" aria-valuemax="{{ $reviews->count() }}"><div class="progress-bar bg-primary" style="width: {{ $reviews->count() ? round($count / $reviews->count() * 100) : 0 }}%"></div></div>
                                        <span class="text-secondary" style="width:1.5rem">{{ $count }}</span>
                                    </div>
                                @endforeach
                            </div>
                            @foreach ($reviews as $review)
                                <div class="list-row">
                                    <x-avatar :user="$review->offer->request->homeowner->user" />
                                    <div class="flex-grow-1">
                                        <div class="d-flex flex-wrap justify-content-between gap-2">
                                            <strong>{{ $review->offer->request->homeowner->user->first_name }} {{ Str::substr($review->offer->request->homeowner->user->last_name, 0, 1) }}.</strong>
                                            <small class="text-secondary">{{ $review->created_at->translatedFormat('j F Y') }}</small>
                                        </div>
                                        <x-rating :value="$review->rating" :show-value="false" class="my-1" />
                                        <p class="mb-1">{{ $review->comment }}</p>
                                        <small class="text-secondary"><i class="bi bi-briefcase me-1"></i>{{ $review->offer->request->service->name }} · {{ $review->offer->request->service_city }}</small>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
            <aside class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-body p-4">
                        <h2 class="h6 text-uppercase text-secondary fw-bold mb-3" style="letter-spacing:.08em">At a glance</h2>
                        <div class="d-grid gap-3">
                            <x-stat-tile icon="bi-check2-circle" label="Jobs completed" :value="$handyman->completed_jobs_count" tone="icon-tone-7" />
                            <x-stat-tile icon="bi-star-fill" label="Average rating" :value="$handyman->reviews_avg_rating ? number_format($handyman->reviews_avg_rating, 1).' / 5' : 'New'" tone="icon-tone-2" />
                            <x-stat-tile icon="bi-heart-fill" label="Homeowners who favorited" :value="$handyman->favorited_by_count" tone="icon-tone-6" />
                            <x-stat-tile icon="bi-geo-alt-fill" label="Based in" :value="$handyman->city ?? 'Morocco'" tone="icon-tone-4" />
                        </div>
                    </div>
                </div>
                <div class="card bg-primary-soft border-0">
                    <div class="card-body p-4">
                        <h2 class="h5">Work with {{ $handyman->user->first_name }}</h2>
                        <p class="small text-secondary mb-3">Post a request for one of the services above. If {{ $handyman->user->first_name }} sends an offer and you accept it, you can contact them directly on WhatsApp.</p>
                        <a class="btn btn-primary w-100" href="{{ auth()->check() ? route(auth()->user()->dashboardRouteName()) : route('register', 'homeowner') }}">Post a request</a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>
@endsection
