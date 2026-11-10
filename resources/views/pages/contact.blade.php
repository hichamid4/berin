@extends('layouts.public')

@section('title', 'Contact us')

@section('hero')
<section class="page-hero">
    <div class="container">
        <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li><li class="breadcrumb-item active" aria-current="page">Contact</li></ol></nav>
        <h1>Contact us</h1>
        <p>Questions about BreIn, your account or a job? We are happy to help.</p>
    </div>
</section>
@endsection

@section('content')
<section class="section">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="d-grid gap-3">
                    <div class="card"><div class="card-body p-4 d-flex gap-3">
                        <span class="icon-tile icon-tone-1 d-grid rounded-3 flex-shrink-0" style="width:3rem;height:3rem;place-items:center;font-size:1.3rem"><i class="bi bi-geo-alt"></i></span>
                        <div><h2 class="h6 mb-1">Office</h2><p class="small text-secondary mb-0">Casablanca, Morocco</p></div>
                    </div></div>
                    <div class="card"><div class="card-body p-4 d-flex gap-3">
                        <span class="icon-tile icon-tone-2 d-grid rounded-3 flex-shrink-0" style="width:3rem;height:3rem;place-items:center;font-size:1.3rem"><i class="bi bi-envelope"></i></span>
                        <div><h2 class="h6 mb-1">Email</h2><p class="small text-secondary mb-0"><a href="mailto:contact@brein.example">contact@brein.example</a></p></div>
                    </div></div>
                    <div class="card"><div class="card-body p-4 d-flex gap-3">
                        <span class="icon-tile icon-tone-4 d-grid rounded-3 flex-shrink-0" style="width:3rem;height:3rem;place-items:center;font-size:1.3rem"><i class="bi bi-clock"></i></span>
                        <div><h2 class="h6 mb-1">Support hours</h2><p class="small text-secondary mb-0">Monday to Saturday, 9:00 to 18:00</p></div>
                    </div></div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card h-100"><div class="card-body p-4 p-lg-5">
                    <h2 class="h4 mb-3">Quick answers</h2>
                    <div class="accordion accordion-flush" id="faq">
                        @foreach ([
                            ['How do I hire a handyman?', 'Create a homeowner account, post a request for the service you need, then compare the offers you receive and accept the one you prefer.'],
                            ['When can I contact the handyman?', 'As soon as you accept an offer, a WhatsApp button appears on your request with the handyman\'s number.'],
                            ['Who sees my address?', 'Only the handyman whose offer you accept. Other handymen only see the city.'],
                            ['Why can\'t I see a request as a handyman?', 'You only see requests for services that require one of your declared skills. Update your skills in your account settings.'],
                            ['How do I close my account?', 'Open your account settings and use “Close my account”. Your personal data is removed; past jobs remain anonymously.'],
                        ] as [$question, $answer])
                            <div class="accordion-item">
                                <h3 class="accordion-header"><button class="accordion-button {{ $loop->first ? '' : 'collapsed' }} fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq{{ $loop->index }}">{{ $question }}</button></h3>
                                <div id="faq{{ $loop->index }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" data-bs-parent="#faq"><div class="accordion-body text-secondary">{{ $answer }}</div></div>
                            </div>
                        @endforeach
                    </div>
                </div></div>
            </div>
        </div>
    </div>
</section>
@endsection
