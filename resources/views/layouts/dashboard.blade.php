@extends('layouts.public')

@section('content')
<section class="page-hero">
    <div class="container">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <x-avatar :user="$user" size="avatar-lg" class="border border-2 border-white" />
                <div>
                    <p class="small text-uppercase fw-bold mb-1" style="letter-spacing:.1em;color:rgba(255,255,255,.7)">@yield('role_label')</p>
                    <h1 class="h3">Welcome back, {{ $user->first_name }}</h1>
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
                            @yield('sidebar')
                        </nav>
                        <hr>
                        <form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-outline-secondary w-100" type="submit"><i class="bi bi-box-arrow-right me-1"></i>Log out</button></form>
                    </div>
                </div>
            </aside>
            <div class="col-lg-9">
                @yield('dashboard')
            </div>
        </div>
    </div>
</section>
@endsection
