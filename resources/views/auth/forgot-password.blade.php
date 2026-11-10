@extends('layouts.auth')

@section('title', 'Forgot password')
@section('aside_title', 'Locked out? It happens.')
@section('aside_text', 'Tell us the email address of your account and we will send you a secure link to choose a new password.')

@section('content')
    <span class="chip mb-4"><i class="bi bi-key"></i>Password recovery</span>
    <h1 class="h3 mb-1">Reset your password</h1>
    <p class="text-secondary mb-4">Enter the email address for your account. If it matches an eligible account, we will send a reset link.</p>
    <form method="post" action="{{ route('password.email') }}">@csrf
        <label class="form-label" for="email">Email address</label>
        <input class="form-control form-control-lg @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <button class="btn btn-primary btn-lg w-100 mt-4" type="submit">Send reset link</button>
    </form>
    <hr class="my-4">
    <p class="mb-0 text-center"><a class="fw-bold" href="{{ route('login') }}"><i class="bi bi-arrow-left me-1"></i>Back to sign in</a></p>
@endsection
