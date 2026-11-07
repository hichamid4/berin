@extends('layouts.app')

@section('title', 'Verify your email')

@section('content')
<div class="row justify-content-center"><div class="col-md-8 col-lg-6"><div class="card border-0 shadow-sm"><div class="card-body p-4 p-lg-5">
    <p class="text-success fw-semibold">One last step</p>
    <h1 class="h3">Verify your email address</h1>
    <p class="text-secondary">We sent a verification link to your registered address. Open the link to activate your BreIn account.</p>
    <form method="post" action="{{ route('verification.send') }}">@csrf<button class="btn btn-outline-success" type="submit">Send a new verification link</button></form>
</div></div></div></div>
@endsection
