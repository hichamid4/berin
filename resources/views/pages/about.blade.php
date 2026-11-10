@extends('layouts.public')

@section('title', 'About BreIn')

@section('hero')
<section class="page-hero">
    <div class="container">
        <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li><li class="breadcrumb-item active" aria-current="page">About</li></ol></nav>
        <h1>About BreIn</h1>
        <p>A Moroccan marketplace built to make home services simple, fair and trustworthy.</p>
    </div>
</section>
@endsection

@section('content')
<section class="section">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <span class="section-eyebrow">Our mission</span>
                <h2 class="section-title">Connecting homeowners with the right hands</h2>
                <p class="text-secondary">Finding a reliable plumber, electrician or painter in Morocco still relies on word of mouth. BreIn replaces the guesswork with a transparent process: homeowners describe the job, handymen with matching skills respond with clear offers in MAD, and every completed job ends with a verified review.</p>
                <p class="text-secondary">The platform was designed as an end-of-studies project (PFA) and built with Laravel, Blade and Bootstrap, with a database model that enforces the business rules: one accepted offer per request, confirmed completion before a review, and privacy for the exact service address.</p>
            </div>
            <div class="col-lg-6">
                <div class="row g-3">
                    @foreach ([
                        ['bi-shield-check', 'Skill-matched requests', 'Handymen only see requests for services that need the skills they declared.'],
                        ['bi-cash-coin', 'Clear pricing in MAD', 'Every offer states a price, start and end dates, and a description before you commit.'],
                        ['bi-lock', 'Private by design', 'Your exact service address is shared only with the handyman you book.'],
                        ['bi-star-half', 'Honest reviews', 'Only homeowners who confirmed a completed job can rate the handyman.'],
                    ] as [$icon, $title, $text])
                        <div class="col-sm-6">
                            <div class="card h-100"><div class="card-body p-4">
                                <span class="icon-tile icon-tone-{{ $loop->iteration }} d-grid rounded-3 mb-3" style="width:3rem;height:3rem;place-items:center;font-size:1.35rem"><i class="bi {{ $icon }}"></i></span>
                                <h3 class="h6">{{ $title }}</h3>
                                <p class="small text-secondary mb-0">{{ $text }}</p>
                            </div></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section bg-white">
    <div class="container">
        <x-section-heading eyebrow="Three roles, one platform" title="Built for everyone involved" align="center" />
        <div class="row g-4">
            @foreach ([
                ['bi-house-heart', 'Homeowners', 'Post requests, compare offers, book with confidence and review the result.', route('register', 'homeowner'), 'Create a homeowner account'],
                ['bi-tools', 'Handymen', 'Declare your skills, receive matching requests and grow a reputation through reviews.', route('register', 'handyman'), 'Join as a handyman'],
                ['bi-shield-lock', 'Administrators', 'Manage users, the service catalog and skills, and monitor activity across the marketplace.', null, null],
            ] as [$icon, $title, $text, $url, $label])
                <div class="col-md-4">
                    <div class="card h-100 text-center"><div class="card-body p-4 d-flex flex-column">
                        <i class="bi {{ $icon }} display-5 text-primary mb-3"></i>
                        <h3 class="h5">{{ $title }}</h3>
                        <p class="text-secondary small">{{ $text }}</p>
                        @if ($url)<a class="btn btn-outline-primary mt-auto" href="{{ $url }}">{{ $label }}</a>@endif
                    </div></div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endsection
