@extends('layouts.auth')

@php
    $labels = ['homeowner' => 'Homeowner', 'handyman' => 'Handyman', 'admin' => 'Administrator'];
    $loginRoute = match ($role) {
        'handyman' => 'handyman.login.store',
        'admin' => 'admin.login.store',
        default => 'login.store',
    };
@endphp

@section('title', $labels[$role].' sign in')
@section('aside_title', $role === 'handyman' ? 'Your next job is waiting.' : ($role === 'admin' ? 'Keep the marketplace healthy.' : 'Welcome back home.'))
@section('aside_text', $role === 'handyman' ? 'Sign in to see requests that match your skills and track your offers.' : ($role === 'admin' ? 'Manage users, the catalog and skills, and monitor platform activity.' : 'Sign in to follow your requests, compare offers and book trusted handymen.'))

@section('content')
    @if ($role !== 'admin')
        <div class="role-switch mb-4">
            <a class="{{ $role === 'homeowner' ? 'active' : '' }}" href="{{ route('login') }}"><i class="bi bi-house-door me-1"></i>Homeowner</a>
            <a class="{{ $role === 'handyman' ? 'active' : '' }}" href="{{ route('handyman.login') }}"><i class="bi bi-tools me-1"></i>Handyman</a>
        </div>
    @else
        <span class="chip mb-4"><i class="bi bi-shield-lock"></i>Protected administration area</span>
    @endif
    <h1 class="h3 mb-1">Sign in as {{ strtolower($labels[$role]) }}</h1>
    <p class="text-secondary mb-4">Use the email address and password of your BreIn account.</p>
    <form method="post" action="{{ route($loginRoute) }}" novalidate>
        @csrf
        <div class="mb-3">
            <label class="form-label" for="email">Email address</label>
            <input class="form-control form-control-lg @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" autofocus>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <div class="d-flex justify-content-between"><label class="form-label" for="password">Password</label>@if ($role !== 'admin')<a class="small" href="{{ route('password.request') }}">Forgot your password?</a>@endif</div>
            <input class="form-control form-control-lg @error('password') is-invalid @enderror" id="password" name="password" type="password" required autocomplete="current-password">
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-check mb-4"><input class="form-check-input" id="remember" type="checkbox" name="remember" value="1"><label class="form-check-label" for="remember">Keep me signed in</label></div>
        <button class="btn btn-primary btn-lg w-100" type="submit">Sign in</button>
    </form>
    @if ($role !== 'admin')
        <hr class="my-4">
        @if ($role === 'homeowner')<p class="mb-0 text-center">New to BreIn? <a class="fw-bold" href="{{ route('register', 'homeowner') }}">Create a homeowner account</a></p>@endif
        @if ($role === 'handyman')<p class="mb-0 text-center">Not registered yet? <a class="fw-bold" href="{{ route('register', 'handyman') }}">Join BreIn as a handyman</a></p>@endif
    @endif
@endsection
