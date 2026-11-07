<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'BreIn') · BreIn</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-body-tertiary">
<nav class="navbar navbar-expand-lg bg-white border-bottom">
    <div class="container">
        <a class="navbar-brand fw-bold text-success" href="{{ route('home') }}">BreIn</a>
        <div class="ms-auto d-flex gap-2">
            @auth
                @if(auth()->user()->hasVerifiedEmail())
                    <a class="btn btn-outline-success btn-sm" href="{{ route(auth()->user()->dashboardRouteName()) }}">Dashboard</a>
                @endif
                <form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-outline-secondary btn-sm" type="submit">Log out</button></form>
            @else
                <a class="btn btn-outline-success btn-sm" href="{{ route('login') }}">Homeowner login</a>
                <a class="btn btn-success btn-sm" href="{{ route('register', 'homeowner') }}">Join BreIn</a>
            @endauth
        </div>
    </div>
</nav>
<main class="container py-5">
    @if (session('status'))
        <div class="alert alert-success" role="status">{{ match(session('status')) {
            'verification-link-sent' => 'A verification link has been sent to your email address.',
            'email-verified' => 'Your email address is verified.',
            'password-reset-link-sent' => 'If an eligible account matches that email, a password reset link has been sent.',
            'password-reset' => 'Your password has been reset. You can now sign in.',
            default => 'Your request was completed.',
        } }}</div>
    @endif
    @yield('content')
</main>
</body>
</html>
