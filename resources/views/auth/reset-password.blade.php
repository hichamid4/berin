@extends('layouts.auth')

@section('title', 'Choose a new password')
@section('aside_title', 'Almost there.')
@section('aside_text', 'Choose a strong password of at least 12 characters that you do not use anywhere else.')

@section('content')
    <span class="chip mb-4"><i class="bi bi-shield-lock"></i>Secure reset</span>
    <h1 class="h3 mb-4">Choose a new password</h1>
    <form method="post" action="{{ route('password.store') }}">@csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="mb-3"><label class="form-label" for="email">Email address</label><input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email', $email) }}" required autocomplete="email">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="mb-3"><label class="form-label" for="password">New password</label><input class="form-control @error('password') is-invalid @enderror" id="password" name="password" type="password" minlength="12" required autocomplete="new-password">@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="mb-4"><label class="form-label" for="password_confirmation">Confirm new password</label><input class="form-control" id="password_confirmation" name="password_confirmation" type="password" minlength="12" required autocomplete="new-password"></div>
        <button class="btn btn-primary btn-lg w-100" type="submit">Save new password</button>
    </form>
@endsection
