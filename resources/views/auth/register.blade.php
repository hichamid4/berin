@extends('layouts.auth')

@php($label = $role === 'homeowner' ? 'Homeowner' : 'Handyman')

@section('title', 'Create '.$label.' account')
@section('card_class', 'wide')
@section('aside_title', $role === 'handyman' ? 'Turn your skills into steady work.' : 'Get your home sorted, step by step.')
@section('aside_text', $role === 'handyman' ? 'Create your profile, declare your skills and start receiving requests that match them.' : 'Create your account to post requests, receive offers and book trusted handymen near you.')

@section('content')
    <div class="role-switch mb-4">
        <a class="{{ $role === 'homeowner' ? 'active' : '' }}" href="{{ route('register', 'homeowner') }}"><i class="bi bi-house-door me-1"></i>Homeowner</a>
        <a class="{{ $role === 'handyman' ? 'active' : '' }}" href="{{ route('register', 'handyman') }}"><i class="bi bi-tools me-1"></i>Handyman</a>
    </div>
    <h1 class="h3 mb-1">Create your {{ strtolower($label) }} account</h1>
    <p class="text-secondary mb-4">We will send a verification link to your email address to activate the account.</p>
    <form method="post" action="{{ route('register.store', $role) }}">
        @csrf
        <div class="row g-3">
            <div class="col-sm-6"><label class="form-label" for="first_name">First name</label><input class="form-control @error('first_name') is-invalid @enderror" id="first_name" name="first_name" value="{{ old('first_name') }}" required autocomplete="given-name">@error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-sm-6"><label class="form-label" for="last_name">Last name</label><input class="form-control @error('last_name') is-invalid @enderror" id="last_name" name="last_name" value="{{ old('last_name') }}" required autocomplete="family-name">@error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-sm-6"><label class="form-label" for="username">Username</label><input class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username') }}" required autocomplete="username">@error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-sm-6"><label class="form-label" for="email">Email address</label><input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-sm-6"><label class="form-label" for="phone_e164">Phone number <span class="text-secondary fw-normal">(optional)</span></label><input class="form-control @error('phone_e164') is-invalid @enderror" id="phone_e164" name="phone_e164" type="tel" value="{{ old('phone_e164') }}" placeholder="+212 6..." autocomplete="tel">@error('phone_e164')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-sm-6"><label class="form-label" for="city">City <span class="text-secondary fw-normal">(optional)</span></label><input class="form-control @error('city') is-invalid @enderror" id="city" name="city" value="{{ old('city') }}" placeholder="Casablanca" autocomplete="address-level2">@error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-12"><label class="form-label" for="address">Profile address <span class="text-secondary fw-normal">(optional)</span></label><input class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address') }}" autocomplete="street-address">@error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-sm-6"><label class="form-label" for="password">Password</label><input class="form-control @error('password') is-invalid @enderror" id="password" name="password" type="password" minlength="12" required autocomplete="new-password">@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror<div class="form-text">At least 12 characters.</div></div>
            <div class="col-sm-6"><label class="form-label" for="password_confirmation">Confirm password</label><input class="form-control" id="password_confirmation" name="password_confirmation" type="password" minlength="12" required autocomplete="new-password"></div>
            <div class="col-12 pt-2"><button class="btn btn-primary btn-lg w-100" type="submit">Create {{ strtolower($label) }} account</button></div>
        </div>
    </form>
    <hr class="my-4">
    <p class="mb-0 text-center">Already registered? <a class="fw-bold" href="{{ $role === 'handyman' ? route('handyman.login') : route('login') }}">Sign in</a></p>
@endsection
