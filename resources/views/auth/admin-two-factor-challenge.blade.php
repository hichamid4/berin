@extends('layouts.auth')

@push('meta')<meta name="robots" content="noindex, nofollow, noarchive">@endpush

@section('title', 'Two-factor verification')
@section('aside_title', 'One more step.')
@section('aside_text', 'Administrator accounts are protected by a second factor from your authenticator app.')

@section('content')
    <span class="chip mb-4"><i class="bi bi-shield-lock"></i>Protected administration area</span>
    <h1 class="h3 mb-1">Enter your verification code</h1>
    <p class="text-secondary mb-4">Open your authenticator app and enter the 6-digit code for BreIn. If you no longer have your phone, enter one of your recovery codes instead.</p>
    <form method="post" action="{{ route('admin.two-factor.verify') }}" novalidate>
        @csrf
        <div class="mb-4">
            <label class="form-label" for="code">Authentication or recovery code</label>
            <input class="form-control form-control-lg text-center @error('code') is-invalid @enderror" id="code" name="code" type="text" required autofocus autocomplete="one-time-code" inputmode="text" maxlength="32" spellcheck="false" placeholder="123456" style="letter-spacing:.2em">
            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            @error('email')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
        </div>
        <button class="btn btn-primary btn-lg w-100" type="submit">Verify and sign in</button>
    </form>
    <hr class="my-4">
    <p class="small text-secondary mb-0">Lost your phone and your recovery codes? Ask someone with access to the server to run the reset command described in the README, then enrol your new phone at the next sign-in.</p>
@endsection
