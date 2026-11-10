@extends('layouts.public')

@section('title', 'Trusted handymen for every home in Morocco')

@section('hero')
<section class="hero">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="hero-eyebrow"><i class="bi bi-geo-alt-fill text-accent"></i> Casablanca, Rabat, Marrakech &amp; more</span>
                <h1 class="mt-3">Find the right handyman for your home, <span class="text-accent">without the guesswork.</span></h1>
                <p class="lead mt-3">Describe the work, receive offers from handymen whose skills match the job, compare prices in MAD and book the one you trust.</p>

                <form class="hero-search mt-4" action="{{ route('services.index') }}" method="get" role="search">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-5">
                            <div class="input-icon"><i class="bi bi-search"></i><input class="form-control" type="search" name="q" placeholder="What do you need done?" aria-label="Search services"></div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-icon"><i class="bi bi-grid"></i>
                                <select class="form-select" name="category" aria-label="Category">
                                    <option value="">All categories</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3"><button class="btn btn-primary w-100 py-3" type="submit">Search</button></div>
                    </div>
                </form>

                <div class="hero-popular mt-3">
                    <span>Popular:</span>
                    @foreach ($popularServices->take(4) as $service)
                        <a href="{{ route('services.show', $service) }}">{{ $service->name }}</a>
                    @endforeach
                </div>

                <div class="hero-stats mt-5">
                    <div class="hero-stat"><strong>{{ $stats['handymen'] }}</strong><span>Active handymen</span></div>
                    <div class="hero-stat"><strong>{{ $stats['services'] }}</strong><span>Home services</span></div>
                    <div class="hero-stat"><strong>{{ $stats['completed'] }}</strong><span>Jobs completed</span></div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="hero-visual">
                    @if ($featuredHandymen->isNotEmpty())
                        @php($top = $featuredHandymen->first())
                        <div class="hero-card one">
                            <div class="d-flex align-items-center gap-3">
                                <x-avatar :user="$top->user" />
                                <div class="min-w-0">
                                    <div class="fw-bold text-truncate">{{ $top->user->fullName() }}</div>
                                    <small class="text-secondary">{{ $top->skills->first()?->name }} · {{ $top->city }}</small>
                                </div>
                            </div>
                            <x-rating :value="$top->reviews_avg_rating" :count="$top->reviews_count" class="mt-2" />
                        </div>
                    @endif
                    @if ($testimonials->isNotEmpty())
                        @php($quote = $testimonials->first())
                        <div class="hero-card two">
                            <div class="rating mb-1">@for ($i = 0; $i < $quote->rating; $i++)<i class="bi bi-star-fill"></i>@endfor</div>
                            <p class="small mb-1">“{{ Str::limit($quote->comment, 110) }}”</p>
                            <small class="text-secondary">{{ $quote->offer->request->homeowner->user->first_name }}, {{ $quote->offer->request->service_city }}</small>
                        </div>
                    @endif
                    <div class="hero-card three">
                        <div class="d-flex align-items-center gap-3">
                            <span class="icon-tile icon-tone-2 d-grid rounded-3" style="width:2.75rem;height:2.75rem;place-items:center"><i class="bi bi-whatsapp fs-5"></i></span>
                            <div>
                                <div class="fw-bold">Offer accepted</div>
                                <small class="text-secondary">Contact your handyman on WhatsApp</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('content')
<section class="section">
    <div class="container">
        <x-section-heading eyebrow="Browse by category" title="What does your home need today?" lead="Eight categories cover the most common repairs, improvements and upkeep." />
        <div class="row g-3">
            @foreach ($categories as $category)
                <div class="col-sm-6 col-lg-3">
                    <a class="card card-hover category-card h-100" href="{{ route('services.index', ['category' => $category->id]) }}">
                        <span class="icon-tile icon-tone-{{ $loop->iteration }}"><i class="bi {{ $category->icon() }}"></i></span>
                        <span><strong>{{ $category->name }}</strong><small>{{ $category->services_count }} {{ Str::plural('service', $category->services_count) }}</small></span>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="section bg-white">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4 mb-lg-5">
            <x-section-heading eyebrow="Popular services" title="Most requested on BreIn" lead="Services homeowners book most often, each matched to the skills a handyman must hold." class="mb-0" />
            <a class="btn btn-outline-primary" href="{{ route('services.index') }}">All services <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
        <div class="row g-4">
            @foreach ($popularServices as $service)
                <div class="col-md-6 col-xl-4"><x-service-card :service="$service" /></div>
            @endforeach
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <x-section-heading eyebrow="How it works" title="From request to finished job in four steps" align="center" />
        <div class="row g-4 pt-3">
            @foreach ([
                ['bi-pencil-square', 'Describe the work', 'Choose a service, explain the job, add your city and preferred start date. Photos are optional.'],
                ['bi-inbox', 'Receive offers', 'Only handymen whose skills match the service see your request and send priced offers in MAD.'],
                ['bi-check2-circle', 'Choose and book', 'Compare prices, dates and reviews, then accept one offer. The others are closed automatically.'],
                ['bi-star', 'Confirm and review', 'When the handyman marks the job done, you confirm completion and leave a rating.'],
            ] as [$icon, $title, $text])
                <div class="col-md-6 col-xl-3">
                    <div class="card step-card card-hover">
                        <span class="step-number">{{ $loop->iteration }}</span>
                        <i class="bi {{ $icon }} d-block mb-3"></i>
                        <h3 class="h5">{{ $title }}</h3>
                        <p class="text-secondary small mb-0">{{ $text }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="section bg-white">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4 mb-lg-5">
            <x-section-heading eyebrow="Top-rated handymen" title="Professionals homeowners recommend" lead="Ratings come from verified reviews left after completed jobs." class="mb-0" />
            <a class="btn btn-outline-primary" href="{{ route('handymen.index') }}">Browse all handymen <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
        <div class="row g-4">
            @foreach ($featuredHandymen as $handyman)
                <div class="col-md-6 col-xl-3"><x-handyman-card :handyman="$handyman" /></div>
            @endforeach
        </div>
    </div>
</section>

@if ($testimonials->isNotEmpty())
<section class="section">
    <div class="container">
        <x-section-heading eyebrow="Homeowner stories" title="What people say after their job is done" align="center" />
        <div class="row g-4">
            @foreach ($testimonials as $review)
                <div class="col-md-4">
                    <div class="card testimonial">
                        <span class="quote-mark">“</span>
                        <x-rating :value="$review->rating" :show-value="false" class="mb-3" />
                        <p>{{ $review->comment }}</p>
                        <div class="d-flex align-items-center gap-2 mt-auto pt-3">
                            <x-avatar :user="$review->offer->request->homeowner->user" size="avatar-sm" />
                            <div class="small">
                                <strong>{{ $review->offer->request->homeowner->user->first_name }} {{ Str::substr($review->offer->request->homeowner->user->last_name, 0, 1) }}.</strong>
                                <span class="text-secondary d-block">{{ $review->offer->request->service->name ?? 'Job' }} with {{ $review->offer->handyman->user->first_name }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<section class="section pt-0">
    <div class="container">
        <div class="cta">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <span class="section-eyebrow text-white-50">For handymen</span>
                    <h2 class="section-title">Grow your business with requests that match your skills</h2>
                    <p class="mb-0">Declare your skills once. BreIn shows you only the requests you can take on, and you decide what to offer.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a class="btn btn-accent btn-lg" href="{{ route('register', 'handyman') }}">Join as a handyman</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
