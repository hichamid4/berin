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
                        <div><h2 class="h6 mb-1">Email</h2><p class="small text-secondary mb-0">contact@brein.example</p></div>
                    </div></div>
                    <div class="card"><div class="card-body p-4 d-flex gap-3">
                        <span class="icon-tile icon-tone-4 d-grid rounded-3 flex-shrink-0" style="width:3rem;height:3rem;place-items:center;font-size:1.3rem"><i class="bi bi-clock"></i></span>
                        <div><h2 class="h6 mb-1">Support hours</h2><p class="small text-secondary mb-0">Monday to Saturday, 9:00 to 18:00</p></div>
                    </div></div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card"><div class="card-body p-4 p-lg-5">
                    <h2 class="h4 mb-1">Send us a message</h2>
                    <p class="text-secondary small mb-4">The contact form will be activated once the message handling workflow is finalised. In the meantime, write to us at the address on the left.</p>
                    <form novalidate>
                        <fieldset disabled>
                            <div class="row g-3">
                                <div class="col-sm-6"><label class="form-label" for="contact_name">Your name</label><input class="form-control" id="contact_name" placeholder="Full name"></div>
                                <div class="col-sm-6"><label class="form-label" for="contact_email">Email address</label><input class="form-control" id="contact_email" type="email" placeholder="you@example.com"></div>
                                <div class="col-12"><label class="form-label" for="contact_subject">Subject</label><input class="form-control" id="contact_subject" placeholder="How can we help?"></div>
                                <div class="col-12"><label class="form-label" for="contact_message">Message</label><textarea class="form-control" id="contact_message" rows="5"></textarea></div>
                                <div class="col-12"><button class="btn btn-primary" type="button">Coming soon</button></div>
                            </div>
                        </fieldset>
                    </form>
                </div></div>
            </div>
        </div>
    </div>
</section>
@endsection
