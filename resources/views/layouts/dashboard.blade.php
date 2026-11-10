@extends('layouts.public')

@section('content')
<section class="page-hero">
    <div class="container">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <x-avatar :user="$user" size="avatar-lg" class="border border-2 border-white" :photo-url="$user->isHandyman() && $user->handymanProfile?->photo_path ? route('handymen.photo', $user->handymanProfile) : null" />
                <div>
                    <p class="small text-uppercase fw-bold mb-1" style="letter-spacing:.1em;color:rgba(255,255,255,.7)">@yield('role_label', ucfirst($user->role->value))</p>
                    <h1 class="h3">@yield('heading', 'Welcome back, '.$user->first_name)</h1>
                    <p>@yield('subtitle')</p>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">@yield('hero_actions')</div>
        </div>
    </div>
</section>
<section class="dashboard-shell">
    <div class="container">
        <div class="row g-4">
            <aside class="col-lg-3">
                <div class="card dashboard-sidebar">
                    <div class="card-body">
                        <nav class="nav flex-column dashboard-nav gap-1" aria-label="Dashboard sections">
                            @include('dashboards.partials.'.$user->role->value.'-nav')
                        </nav>
                        <hr>
                        <form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-outline-secondary w-100" type="submit"><i class="bi bi-box-arrow-right me-1"></i>Log out</button></form>
                    </div>
                </div>
            </aside>
            <div class="col-lg-9">
                <x-flash class="mb-4" />
                @unless (View::hasSection('inline_errors'))
                    <x-form-errors class="mb-4" />
                @endunless
                @yield('dashboard')
            </div>
        </div>
    </div>
</section>
@endsection
