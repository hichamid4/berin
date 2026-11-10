@extends('layouts.public')

@section('title', $service->name)
@section('meta_description', Str::limit($service->description, 150))

@section('hero')
<section class="page-hero">
    <div class="container">
        <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li><li class="breadcrumb-item"><a href="{{ route('services.index') }}">Services</a></li><li class="breadcrumb-item"><a href="{{ route('services.index', ['category' => $service->category_id]) }}">{{ $service->category->name }}</a></li><li class="breadcrumb-item active" aria-current="page">{{ $service->name }}</li></ol></nav>
        <div class="d-flex align-items-center gap-3">
            <span class="icon-tile icon-tone-1 d-grid rounded-4" style="width:3.5rem;height:3.5rem;place-items:center;font-size:1.6rem"><i class="bi {{ $service->category->icon() }}"></i></span>
            <div><h1>{{ $service->name }}</h1><p>{{ $service->category->name }}</p></div>
        </div>
    </div>
</section>
@endsection

@section('content')
<section class="section pt-4">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5">About this service</h2>
                        <p class="text-secondary mb-4">{{ $service->description }}</p>
                        <h3 class="h6 text-uppercase text-secondary fw-bold" style="letter-spacing:.08em">Skills a handyman must hold</h3>
                        <div class="d-flex flex-wrap gap-2 mb-4">
                            @foreach ($service->skills as $skill)
                                <span class="chip" title="{{ $skill->description }}"><i class="bi bi-patch-check-fill"></i>{{ $skill->name }}</span>
                            @endforeach
                        </div>
                        <div class="row g-3">
                            <div class="col-sm-4"><x-stat-tile icon="bi-people" label="Matching handymen" :value="$handymen->count()" tone="icon-tone-1" /></div>
                            <div class="col-sm-4"><x-stat-tile icon="bi-clipboard-check" label="Open requests" :value="$openRequestCount" tone="icon-tone-2" /></div>
                            <div class="col-sm-4"><x-stat-tile icon="bi-check2-circle" label="Jobs completed" :value="$completedRequestCount" tone="icon-tone-7" /></div>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
                    <h2 class="h4 mb-0">Handymen qualified for this service</h2>
                    <a class="small fw-bold" href="{{ route('handymen.index', ['skill' => $service->skills->first()?->id]) }}">See all</a>
                </div>
                @if ($handymen->isEmpty())
                    <x-empty-state icon="bi-person-badge" title="No qualified handyman yet" text="Handymen who declare one of the required skills will appear here." />
                @else
                    <div class="row g-4">
                        @foreach ($handymen as $handyman)
                            <div class="col-md-6"><x-handyman-card :handyman="$handyman" /></div>
                        @endforeach
                    </div>
                @endif
            </div>
            <aside class="col-lg-4">
                <div class="card bg-primary-soft border-0 mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5">Need this done at home?</h2>
                        <p class="text-secondary small">Create a request with your description, city and preferred date. Qualified handymen will send you offers in MAD.</p>
                        @auth
                            <a class="btn btn-primary w-100" href="{{ route(auth()->user()->dashboardRouteName()) }}">Go to my dashboard</a>
                        @else
                            <a class="btn btn-primary w-100 mb-2" href="{{ route('register', 'homeowner') }}">Create a homeowner account</a>
                            <a class="btn btn-outline-primary w-100" href="{{ route('login') }}">I already have an account</a>
                        @endauth
                    </div>
                </div>
                @if ($relatedServices->isNotEmpty())
                    <h2 class="h6 text-uppercase text-secondary fw-bold mb-3" style="letter-spacing:.08em">More in {{ $service->category->name }}</h2>
                    <div class="d-grid gap-3">
                        @foreach ($relatedServices as $related)
                            <x-service-card :service="$related" />
                        @endforeach
                    </div>
                @endif
            </aside>
        </div>
    </div>
</section>
@endsection
