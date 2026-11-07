@extends('layouts.app')

@section('title', 'Home services made simpler')

@section('content')
<section class="row align-items-center g-5 py-4 py-lg-5">
    <div class="col-lg-7">
        <p class="text-success fw-semibold mb-2">Home services, made simpler</p>
        <h1 class="display-4 fw-bold text-body-emphasis">Find the right help for your home.</h1>
        <p class="lead text-secondary mt-3">Explore local services, describe the work you need, and compare offers from skilled handymen.</p>
        <div class="d-flex flex-wrap gap-2 mt-4">
            <a class="btn btn-success btn-lg" href="{{ route('register', 'homeowner') }}">Get started as a homeowner</a>
            <a class="btn btn-outline-success btn-lg" href="{{ route('handyman.login') }}">Handyman sign in</a>
        </div>
        <p class="small mt-4 mb-0"><a href="{{ route('register', 'handyman') }}">Join as a handyman</a></p>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm"><div class="card-body p-4 p-lg-5">
            <h2 class="h4">How BreIn works</h2>
            <ol class="mb-0 ps-3 text-secondary">
                <li class="mb-2">Choose a service and describe the work.</li>
                <li class="mb-2">Receive offers from handymen with matching skills.</li>
                <li>Choose an offer and contact the handyman through WhatsApp.</li>
            </ol>
        </div></div>
        <p class="text-end small mt-3"><a href="{{ route('admin.login') }}">Administrator sign in</a></p>
    </div>
</section>
@endsection
