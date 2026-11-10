@extends('layouts.auth')

@push('meta')<meta name="robots" content="noindex, nofollow, noarchive">@endpush

@section('title', 'Set up two-factor authentication')
@section('card_class', 'wide')
@section('aside_title', 'Protect the administration area.')
@section('aside_text', 'Every administrator signs in with a password and a code from an authenticator app.')

@section('content')
    <span class="chip mb-4"><i class="bi bi-shield-lock"></i>Required for administrators</span>
    <h1 class="h3 mb-1">Set up two-factor authentication</h1>
    <p class="text-secondary mb-4">Your password was correct. Before you can open the administration area, link an authenticator app such as Google Authenticator, Microsoft Authenticator, 1Password or Aegis.</p>

    <div class="row g-4 align-items-start">
        <div class="col-md-5 text-center">
            <div class="bg-white border rounded-3 p-2 d-inline-block" role="img" aria-label="QR code to add BreIn to your authenticator app">{!! $qrCode !!}</div>
        </div>
        <div class="col-md-7">
            <ol class="small ps-3 mb-3">
                <li class="mb-1">In your authenticator app, add an account and scan the QR code.</li>
                <li class="mb-1">If you cannot scan it, enter this key manually (time-based, 6 digits):
                    <code class="d-block fs-6 my-1 user-select-all text-break" data-two-factor-secret>{{ $secret }}</code>
                    <span class="text-secondary">Account: {{ $issuer }} ({{ $accountName }})</span>
                </li>
                <li>Enter the 6-digit code the app shows to finish.</li>
            </ol>
            <form method="post" action="{{ route('admin.two-factor.confirm') }}" novalidate>
                @csrf
                <label class="form-label" for="code">6-digit code</label>
                <div class="input-group input-group-lg">
                    <input class="form-control text-center @error('code') is-invalid @enderror" id="code" name="code" type="text" required autofocus autocomplete="one-time-code" inputmode="numeric" pattern="[0-9 ]*" maxlength="7" placeholder="123456" style="letter-spacing:.2em">
                    <button class="btn btn-primary" type="submit">Confirm</button>
                </div>
                @error('code')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </form>
        </div>
    </div>
    <hr class="my-4">
    <p class="small text-secondary mb-0">After confirming, you will see one-time recovery codes. Store them somewhere safe (a password manager or printed copy); each one lets you sign in once if you lose your phone.</p>
@endsection
