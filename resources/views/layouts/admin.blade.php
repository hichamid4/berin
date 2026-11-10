<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Administration') · BreIn Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Mulish:wght@400;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body class="layout-fixed sidebar-expand-lg">
<div class="app-wrapper">
    <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" aria-label="Toggle sidebar"><i class="bi bi-list fs-4"></i></a></li>
                <li class="nav-item d-none d-md-block"><a class="nav-link" href="{{ route('home') }}" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-1"></i>View public site</a></li>
            </ul>
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item d-none d-sm-block"><span class="nav-link text-secondary small"><i class="bi bi-calendar3 me-1"></i>{{ now()->translatedFormat('l j F Y') }}</span></li>
                <li class="nav-item dropdown user-menu">
                    <a href="#" class="nav-link dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown" aria-expanded="false">
                        <x-avatar :user="auth()->user()" size="avatar-sm" />
                        <span class="d-none d-md-inline fw-bold">{{ auth()->user()->fullName() }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><span class="dropdown-item-text small text-secondary">Signed in as administrator</span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><form method="post" action="{{ route('logout') }}">@csrf<button class="dropdown-item" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Sign out</button></form></li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>

    <aside class="app-sidebar brein-sidebar shadow" data-bs-theme="dark">
        <div class="sidebar-brand">
            <a href="{{ route('admin.dashboard') }}" class="brand-link">
                <span class="brand-mark"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10v9.5h13V10"/><path d="M14.8 12.2a2.4 2.4 0 0 0-3.1 3.1l-2.9 2.9 1.2 1.2 2.9-2.9a2.4 2.4 0 0 0 3.1-3.1l-1.5 1.5-1.2-1.2z"/></svg></span>
                <span class="brand-text">Bre<em>In</em><small>Administration</small></span>
            </a>
        </div>
        <div class="sidebar-wrapper">
            <div class="sidebar-user">
                <x-avatar :user="auth()->user()" />
                <div class="min-w-0">
                    <div class="fw-bold text-truncate">{{ auth()->user()->fullName() }}</div>
                    <small>Platform administrator</small>
                </div>
            </div>
            <nav class="mt-2" aria-label="Administration navigation">
                <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" data-accordion="false" id="navigation">
                    <li class="nav-header">Overview</li>
                    <li class="nav-item"><a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><i class="nav-icon bi bi-speedometer2"></i><p>Dashboard</p></a></li>
                    <li class="nav-item"><a href="{{ route('admin.activity.index') }}" class="nav-link {{ request()->routeIs('admin.activity.*') ? 'active' : '' }}"><i class="nav-icon bi bi-activity"></i><p>Activity monitor</p></a></li>
                    <li class="nav-header">People</li>
                    <li class="nav-item"><a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"><i class="nav-icon bi bi-people"></i><p>Users</p></a></li>
                    <li class="nav-header">Catalog</li>
                    <li class="nav-item"><a href="{{ route('admin.categories.index') }}" class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}"><i class="nav-icon bi bi-grid"></i><p>Categories</p></a></li>
                    <li class="nav-item"><a href="{{ route('admin.services.index') }}" class="nav-link {{ request()->routeIs('admin.services.*') ? 'active' : '' }}"><i class="nav-icon bi bi-briefcase"></i><p>Services</p></a></li>
                    <li class="nav-item"><a href="{{ route('admin.skills.index') }}" class="nav-link {{ request()->routeIs('admin.skills.*') ? 'active' : '' }}"><i class="nav-icon bi bi-tools"></i><p>Skills</p></a></li>
                    <li class="nav-header">Marketplace</li>
                    <li class="nav-item"><a href="{{ route('admin.requests.index') }}" class="nav-link {{ request()->routeIs('admin.requests.*') ? 'active' : '' }}"><i class="nav-icon bi bi-clipboard-check"></i><p>Requests</p></a></li>
                    <li class="nav-item"><a href="{{ route('admin.offers.index') }}" class="nav-link {{ request()->routeIs('admin.offers.*') ? 'active' : '' }}"><i class="nav-icon bi bi-cash-stack"></i><p>Offers</p></a></li>
                    <li class="nav-item"><a href="{{ route('admin.reviews.index') }}" class="nav-link {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}"><i class="nav-icon bi bi-star"></i><p>Reviews</p></a></li>
                </ul>
            </nav>
        </div>
    </aside>

    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-sm-7">
                        <h1 class="mb-0">@yield('page_title', 'Dashboard')</h1>
                        @hasSection('page_subtitle')<p class="text-secondary mb-0 mt-1">@yield('page_subtitle')</p>@endif
                    </div>
                    <div class="col-sm-5">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb float-sm-end">
                                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                                @yield('breadcrumbs')
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <div class="app-content">
            <div class="container-fluid">
                <x-flash class="mb-4" />
                @yield('content')
            </div>
        </div>
    </main>

    <footer class="app-footer">
        <div class="float-end d-none d-sm-inline">Laravel {{ app()->version() }} · AdminLTE 4</div>
        <strong>BreIn</strong> administration &copy; {{ date('Y') }}
    </footer>
</div>
</body>
</html>
