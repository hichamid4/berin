<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @stack('meta')
    <title>@yield('title', 'Sign in') · BreIn</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Mulish:wght@400;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="auth-shell">
    <aside class="auth-aside">
        <a class="brand" href="{{ route('home') }}" style="color:#fff"><x-brand-mark /><span>Bre<em style="color:#5fd3bd">In</em></span></a>
        <div>
            <h2>@yield('aside_title', 'Home services, made simpler.')</h2>
            <p class="lead">@yield('aside_text', 'Describe the work you need, receive offers from skill-matched handymen and book with confidence.')</p>
            <ul class="value-list">
                <li><i class="bi bi-patch-check"></i><span><strong>Verified accounts.</strong><br>Email verification and reviewed profiles on both sides.</span></li>
                <li><i class="bi bi-currency-exchange"></i><span><strong>Transparent offers in MAD.</strong><br>Compare prices, dates and descriptions before you decide.</span></li>
                <li><i class="bi bi-whatsapp"></i><span><strong>Direct contact after booking.</strong><br>Reach your handyman on WhatsApp once an offer is accepted.</span></li>
            </ul>
        </div>
        <p class="small mb-0" style="color:rgba(255,255,255,0.55)">&copy; {{ date('Y') }} BreIn · Casablanca, Morocco</p>
    </aside>
    <div class="auth-main">
        <div class="d-flex justify-content-between align-items-center d-lg-none mb-3">
            <a class="brand" href="{{ route('home') }}"><x-brand-mark /><span>Bre<em>In</em></span></a>
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('home') }}"><i class="bi bi-arrow-left me-1"></i>Back to site</a>
        </div>
        <div class="d-none d-lg-flex justify-content-end mb-3">
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('home') }}"><i class="bi bi-arrow-left me-1"></i>Back to site</a>
        </div>
        <div class="auth-card @yield('card_class')">
            <x-flash />
            @yield('content')
        </div>
    </div>
</div>
</body>
</html>
