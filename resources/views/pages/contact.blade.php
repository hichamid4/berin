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
            <div class="col-lg-5 order-2 order-lg-1">
                <div class="d-grid gap-3">
                    <div class="card"><div class="card-body p-4 d-flex gap-3">
                        <span class="icon-tile icon-tone-1 d-grid rounded-3 flex-shrink-0" style="width:3rem;height:3rem;place-items:center;font-size:1.3rem"><i class="bi bi-geo-alt"></i></span>
                        <div><h2 class="h6 mb-1">Office</h2><p class="small text-secondary mb-0">Casablanca, Morocco</p></div>
                    </div></div>
                    {{-- PLACEHOLDER: contact@brein.example is not a real mailbox. Replace it once the owner provides the real support address (see FUNCTIONAL_SPECIFICATION.md §11.6). --}}
                    <div class="card"><div class="card-body p-4 d-flex gap-3">
                        <span class="icon-tile icon-tone-2 d-grid rounded-3 flex-shrink-0" style="width:3rem;height:3rem;place-items:center;font-size:1.3rem"><i class="bi bi-envelope"></i></span>
                        <div><h2 class="h6 mb-1">Email</h2><p class="small text-secondary mb-0"><a href="mailto:contact@brein.example">contact@brein.example</a></p></div>
                    </div></div>
                    <div class="card"><div class="card-body p-4 d-flex gap-3">
                        <span class="icon-tile icon-tone-4 d-grid rounded-3 flex-shrink-0" style="width:3rem;height:3rem;place-items:center;font-size:1.3rem"><i class="bi bi-clock"></i></span>
                        <div><h2 class="h6 mb-1">Support hours</h2><p class="small text-secondary mb-0">Monday to Saturday, 9:00 to 18:00</p></div>
                    </div></div>
                    <div class="card"><div class="card-body p-4 d-flex gap-3">
                        <span class="icon-tile icon-tone-3 d-grid rounded-3 flex-shrink-0" style="width:3rem;height:3rem;place-items:center;font-size:1.3rem"><i class="bi bi-shield-lock"></i></span>
                        <div><h2 class="h6 mb-1">Your privacy</h2><p class="small text-secondary mb-0">We only keep your name, email address and message so our team can reply. Please do not include passwords, card numbers or other sensitive details.</p></div>
                    </div></div>
                </div>
            </div>
            <div class="col-lg-7 order-1 order-lg-2">
                <div class="card h-100"><div class="card-body p-4 p-lg-5">
                    @if (session('contact_message_sent'))
                        <div class="text-center py-4" role="status">
                            <span class="contact-sent-icon mb-3"><i class="bi bi-check-lg"></i></span>
                            <h2 class="h4 mb-2">Message sent</h2>
                            <p class="text-secondary mb-4">{{ \App\Http\Controllers\ContactMessageController::CONFIRMATION }}</p>
                            <a class="btn btn-outline-primary" href="{{ route('contact') }}"><i class="bi bi-pencil-square me-1"></i>Send another message</a>
                        </div>
                    @else
                        <h2 class="h4 mb-1">Send us a message</h2>
                        <p class="text-secondary mb-4">Fill in the form and we will get back to you by email. All fields are required.</p>
                        <form method="post" action="{{ route('contact.store') }}" novalidate>
                            @csrf
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="form-label" for="name">Your name</label>
                                    <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" maxlength="100" required autocomplete="name">
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label" for="email">Email address</label>
                                    <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email') }}" maxlength="254" required autocomplete="email" inputmode="email">
                                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="subject">Subject</label>
                                    <input class="form-control @error('subject') is-invalid @enderror" id="subject" name="subject" value="{{ old('subject') }}" maxlength="150" required placeholder="For example: question about my request">
                                    @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="message">Message</label>
                                    <textarea class="form-control @error('message') is-invalid @enderror" id="message" name="message" rows="6" maxlength="5000" required aria-describedby="message-help">{{ old('message') }}</textarea>
                                    @error('message')<div class="invalid-feedback">{{ $message }}</div>@else<div class="form-text" id="message-help">Between 10 and 5,000 characters.</div>@enderror
                                </div>
                                <div class="contact-trap" aria-hidden="true">
                                    <label for="{{ \App\Http\Requests\StoreContactMessageRequest::HONEYPOT_FIELD }}">Leave this field empty</label>
                                    <input id="{{ \App\Http\Requests\StoreContactMessageRequest::HONEYPOT_FIELD }}" name="{{ \App\Http\Requests\StoreContactMessageRequest::HONEYPOT_FIELD }}" type="text" value="" tabindex="-1" autocomplete="off">
                                </div>
                                <div class="col-12 pt-1">
                                    <button class="btn btn-primary btn-lg w-100" type="submit"><i class="bi bi-send me-2"></i>Send message</button>
                                </div>
                            </div>
                        </form>
                    @endif
                </div></div>
            </div>
        </div>

        <div class="card mt-4"><div class="card-body p-4 p-lg-5">
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
</section>
@endsection
