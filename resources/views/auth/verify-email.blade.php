@extends('layouts.auth')

@section('title', 'Verify your email')
@section('aside_title', 'One last step.')
@section('aside_text', 'Verifying your email keeps every BreIn account genuine, for homeowners and handymen alike.')

@section('content')
    <span class="chip mb-4"><i class="bi bi-envelope-check"></i>Email verification</span>
    <h1 class="h3 mb-1">Verify your email address</h1>
    <p class="text-secondary">We sent a verification link to your registered address. Open the link to activate your BreIn account.</p>
    <div class="d-flex flex-wrap gap-2 mt-4">
        <form method="post" action="{{ route('verification.send') }}">@csrf<button class="btn btn-primary" type="submit"><i class="bi bi-send me-1"></i>Send a new verification link</button></form>
        <form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-outline-secondary" type="submit">Log out</button></form>
    </div>
@endsection
