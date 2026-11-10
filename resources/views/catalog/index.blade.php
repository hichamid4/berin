@extends('layouts.public')

@section('title', 'Home services')

@section('hero')
<section class="page-hero">
    <div class="container">
        <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li><li class="breadcrumb-item active" aria-current="page">Services</li></ol></nav>
        <h1>Home services</h1>
        <p>Browse the catalog by category or search for the work you need.</p>
    </div>
</section>
@endsection

@section('content')
<section class="section pt-4">
    <div class="container">
        <form class="filter-bar mb-4" method="get" action="{{ route('services.index') }}" role="search">
            <div class="row g-2 align-items-center">
                <div class="col-md-6"><input class="form-control" type="search" name="q" value="{{ $search }}" placeholder="Search services, e.g. tiling, AC, painting" aria-label="Search services"></div>
                <div class="col-md-4">
                    <select class="form-select" name="category" aria-label="Category">
                        <option value="">All categories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected($selectedCategory === $category->id)>{{ $category->name }} ({{ $category->services_count }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-grid"><button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Filter</button></div>
            </div>
        </form>

        <div class="row g-4">
            <aside class="col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <h2 class="h6 text-uppercase text-secondary fw-bold mb-3" style="letter-spacing:.08em">Categories</h2>
                        <nav class="nav flex-column dashboard-nav gap-1">
                            <a class="nav-link {{ $selectedCategory === null ? 'active' : '' }}" href="{{ route('services.index', array_filter(['q' => $search])) }}"><i class="bi bi-grid-3x3-gap"></i>All services</a>
                            @foreach ($categories as $category)
                                <a class="nav-link {{ $selectedCategory === $category->id ? 'active' : '' }}" href="{{ route('services.index', array_filter(['category' => $category->id, 'q' => $search])) }}">
                                    <i class="bi {{ $category->icon() }}"></i>{{ $category->name }}
                                    <span class="badge text-bg-light border">{{ $category->services_count }}</span>
                                </a>
                            @endforeach
                        </nav>
                    </div>
                </div>
            </aside>
            <div class="col-lg-9">
                <p class="text-secondary small mb-3">{{ $services->total() }} {{ Str::plural('service', $services->total()) }} found</p>
                @if ($services->isEmpty())
                    <x-empty-state icon="bi-search" title="No service matches your search" text="Try another keyword or pick a category from the list." />
                @else
                    <div class="row g-4">
                        @foreach ($services as $service)
                            <div class="col-md-6 col-xxl-4"><x-service-card :service="$service" /></div>
                        @endforeach
                    </div>
                    <div class="mt-4">{{ $services->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
