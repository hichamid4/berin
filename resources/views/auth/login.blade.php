@extends('layouts.app')

@php
    $labels = ['homeowner' => 'Homeowner', 'handyman' => 'Handyman', 'admin' => 'Administrator'];
    $loginRoute = match ($role) {
        'handyman' => 'handyman.login.store',
        'admin' => 'admin.login.store',
        default => 'login.store',
    };
@endphp

@section('title', $labels[$role].' sign in')

@section('content')
<div class="row justify-content-center"><div class="col-md-7 col-lg-5">
    <div class="card border-0 shadow-sm"><div class="card-body p-4 p-lg-5">
        <p class="text-success fw-semibold mb-1">{{ $labels[$role] }} account</p>
        <h1 class="h3 mb-4">Sign in</h1>
        <form method="post" action="{{ route($loginRoute) }}" novalidate>
            @csrf
            <div class="mb-3">
                <label class="form-label" for="email">Email address</label>
                <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" autofocus>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <input class="form-control @error('password') is-invalid @enderror" id="password" name="password" type="password" required autocomplete="current-password">
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-check mb-4"><input class="form-check-input" id="remember" type="checkbox" name="remember" value="1"><label class="form-check-label" for="remember">Remember me</label></div>
            <button class="btn btn-success w-100" type="submit">Sign in</button>
        </form>
        @if ($role !== 'admin')
            <p class="mt-3 mb-0"><a href="{{ route('password.request') }}">Forgot your password?</a></p>
            <hr>
            @if ($role === 'homeowner')<p class="mb-0">New to BreIn? <a href="{{ route('register', 'homeowner') }}">Create a homeowner account</a></p>@endif
            @if ($role === 'handyman')<p class="mb-0">Need a handyman account? <a href="{{ route('register', 'handyman') }}">Join BreIn</a></p>@endif
        @endif
    </div></div>
</div></div>
@endsection
