<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('meta_description', 'BreIn connects Moroccan homeowners with skilled, reviewed handymen for repairs, renovation and home services.')">
    <title>@yield('title', 'Home services made simpler') · BreIn</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Mulish:wght@400;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<header class="site-header">
    <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
        <div class="container">
            <a class="brand" href="{{ route('home') }}"><x-brand-mark /><span>Bre<em>In</em></span></a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#siteNav" aria-controls="siteNav" aria-expanded="false" aria-label="Toggle navigation"><i class="bi bi-list fs-3"></i></button>
            <div class="collapse navbar-collapse" id="siteNav">
                <ul class="navbar-nav mx-auto mb-3 mb-lg-0 gap-lg-1">
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Home</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('services.*') ? 'active' : '' }}" href="{{ route('services.index') }}">Services</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('handymen.*') ? 'active' : '' }}" href="{{ route('handymen.index') }}">Handymen</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('about') ? 'active' : '' }}" href="{{ route('about') }}">About</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('contact') ? 'active' : '' }}" href="{{ route('contact') }}">Contact</a></li>
                </ul>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    @auth
                        @if (auth()->user()->hasVerifiedEmail())
                            @if (auth()->user()->isHomeowner())
                                <a class="btn btn-primary" href="{{ route('homeowner.requests.create') }}"><i class="bi bi-plus-circle me-1"></i>Post a request</a>
                            @endif
                            <a class="btn btn-soft-primary d-inline-flex align-items-center gap-2" href="{{ route(auth()->user()->dashboardRouteName()) }}">
                                <x-avatar :user="auth()->user()" size="avatar-sm" />
                                <span>My dashboard</span>
                            </a>
                        @endif
                        <form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-outline-secondary" type="submit">Log out</button></form>
                    @else
                        <div class="dropdown">
                            <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-box-arrow-in-right me-1"></i>Sign in</button>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li><a class="dropdown-item" href="{{ route('login') }}"><i class="bi bi-house-door me-2 text-primary"></i>Homeowner</a></li>
                                <li><a class="dropdown-item" href="{{ route('handyman.login') }}"><i class="bi bi-tools me-2 text-primary"></i>Handyman</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="{{ route('admin.login') }}"><i class="bi bi-shield-lock me-2 text-primary"></i>Administrator</a></li>
                            </ul>
                        </div>
                        <a class="btn btn-primary" href="{{ route('register', 'homeowner') }}"><i class="bi bi-plus-circle me-1"></i>Post a request</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>
</header>

<main>
    @hasSection('hero')
        @yield('hero')
    @endif
    @if (session('status'))
        <div class="container pt-4"><x-flash /></div>
    @endif
    @yield('content')
</main>

<footer class="site-footer">
    <div class="container">
        <div class="row g-4 g-lg-5">
            <div class="col-lg-4">
                <a class="brand mb-3" href="{{ route('home') }}"><x-brand-mark /><span>Bre<em>In</em></span></a>
                <p class="mb-3">The Moroccan marketplace that connects homeowners with trusted, skill-matched handymen. Describe the work, compare offers and book with confidence.</p>
            </div>
            <div class="col-6 col-lg-2">
                <h6>Marketplace</h6>
                <ul class="footer-links">
                    <li><a href="{{ route('services.index') }}">Browse services</a></li>
                    <li><a href="{{ route('handymen.index') }}">Find a handyman</a></li>
                    <li><a href="{{ route('register', 'homeowner') }}">Post a request</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h6>Accounts</h6>
                <ul class="footer-links">
                    <li><a href="{{ route('login') }}">Homeowner sign in</a></li>
                    <li><a href="{{ route('handyman.login') }}">Handyman sign in</a></li>
                    <li><a href="{{ route('register', 'handyman') }}">Join as a handyman</a></li>
                    <li><a href="{{ route('admin.login') }}">Administration</a></li>
                </ul>
            </div>
            <div class="col-lg-4">
                <h6>Company</h6>
                <ul class="footer-links">
                    <li><a href="{{ route('about') }}">About BreIn</a></li>
                    <li><a href="{{ route('contact') }}">Contact us</a></li>
                </ul>
                <p class="small mt-3 mb-0"><i class="bi bi-geo-alt me-1"></i>Casablanca, Morocco</p>
            </div>
        </div>
        <div class="footer-bottom d-flex flex-wrap justify-content-between gap-2">
            <span>&copy; {{ date('Y') }} BreIn. All rights reserved.</span>
            <span>Prices in Moroccan dirham (MAD).</span>
        </div>
    </div>
</footer>
</body>
</html>
