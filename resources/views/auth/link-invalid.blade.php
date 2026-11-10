@extends('layouts.auth')

@section('title', 'Link expired')
@section('aside_title', 'This link can no longer be used.')
@section('aside_text', 'For your security, verification links expire after '.config('auth.verification.expire', 60).' minutes and only work for the address they were sent to.')

@section('content')
    <span class="chip mb-4" style="background:#fdecec;color:#a12b2b"><i class="bi bi-exclamation-triangle"></i>Invalid or expired link</span>
    <h1 class="h3 mb-1">This verification link is not valid</h1>
    <p class="text-secondary">The link has expired, was already replaced by a newer one, or was modified. Request a new link and use the most recent email.</p>
    @auth
        @if (! auth()->user()->hasVerifiedEmail())
            <form method="post" action="{{ route('verification.send') }}" class="mt-4">@csrf
                <button class="btn btn-primary" type="submit"><i class="bi bi-send me-1"></i>Send me a new verification link</button>
            </form>
        @else
            <a class="btn btn-primary mt-4" href="{{ route(auth()->user()->dashboardRouteName()) }}">Go to my dashboard</a>
        @endif
    @else
        <p class="mb-2">Sign in to your account; you will be offered a new verification link.</p>
        <div class="d-flex flex-wrap gap-2 mt-3">
            <a class="btn btn-primary" href="{{ route('login') }}">Homeowner sign in</a>
            <a class="btn btn-outline-primary" href="{{ route('handyman.login') }}">Handyman sign in</a>
        </div>
    @endauth
@endsection
