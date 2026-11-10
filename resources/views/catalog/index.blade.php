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
        @php
            $filterQuery = array_filter(['q' => $search, 'min_price' => $minimumPrice, 'max_price' => $maximumPrice], fn ($value) => $value !== null && $value !== '');
        @endphp
        <form class="filter-bar mb-4" method="get" action="{{ route('services.index') }}" role="search">
            @if ($errors->any())
                <div class="alert alert-danger py-2 small d-flex align-items-start gap-2" role="alert">
                    <i class="bi bi-exclamation-octagon-fill mt-1"></i>
                    <div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
                </div>
            @endif
            <div class="row g-2 align-items-end">
                <div class="col-lg-5 col-md-7">
                    <label class="form-label small fw-bold mb-1" for="filter-q">Service</label>
                    <input class="form-control" id="filter-q" type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="e.g. tiling, AC, painting">
                </div>
                <div class="col-lg-3 col-md-5">
                    <label class="form-label small fw-bold mb-1" for="filter-category">Category</label>
                    <select class="form-select" id="filter-category" name="category">
                        <option value="">All categories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected($selectedCategory === $category->id)>{{ $category->name }} ({{ $category->services_count }})</option>
                        @endforeach
                    </select>
                </div>
                <fieldset class="col-lg-3 col-md-9">
                    <legend class="form-label small fw-bold mb-1 float-none w-auto">Offer price (MAD)</legend>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="visually-hidden" for="filter-min-price">Minimum offer price in MAD</label>
                            <div class="input-group">
                                <span class="input-group-text">From</span>
                                <input class="form-control @error('min_price') is-invalid @enderror" id="filter-min-price" type="number" name="min_price" value="{{ $minimumPrice !== null ? (float) $minimumPrice : '' }}" min="0" step="1" inputmode="numeric" placeholder="{{ $marketPrices?->minimum !== null ? (int) floor((float) $marketPrices->minimum) : '0' }}" aria-describedby="price-help">
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="visually-hidden" for="filter-max-price">Maximum offer price in MAD</label>
                            <div class="input-group">
                                <span class="input-group-text">To</span>
                                <input class="form-control @error('max_price') is-invalid @enderror" id="filter-max-price" type="number" name="max_price" value="{{ $maximumPrice !== null ? (float) $maximumPrice : '' }}" min="0" step="1" inputmode="numeric" placeholder="{{ $marketPrices?->maximum !== null ? (int) ceil((float) $marketPrices->maximum) : 'any' }}" aria-describedby="price-help">
                            </div>
                        </div>
                    </div>
                </fieldset>
                <div class="col-lg-1 col-md-3 d-grid"><button class="btn btn-primary" type="submit" aria-label="Filter services" title="Filter services"><i class="bi bi-search" aria-hidden="true"></i><span class="d-lg-none ms-1" aria-hidden="true">Filter</span></button></div>
            </div>
            <div class="form-text mt-2" id="price-help">
                <i class="bi bi-info-circle me-1" aria-hidden="true"></i>Services have no fixed price. The price filter uses the offers handymen have submitted (pending or accepted).
                @if ($marketPrices?->minimum !== null)
                    Offers currently range from {{ number_format((float) $marketPrices->minimum, 0, ',', ' ') }} to {{ number_format((float) $marketPrices->maximum, 0, ',', ' ') }} MAD.
                @endif
                @if ($isPriceFiltered || $search !== '' || $selectedCategory)<a class="ms-1" href="{{ route('services.index') }}">Clear all filters</a>@endif
            </div>
        </form>

        <div class="row g-4">
            <aside class="col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <h2 class="h6 text-uppercase text-secondary fw-bold mb-3" style="letter-spacing:.08em">Categories</h2>
                        <nav class="nav flex-column dashboard-nav gap-1">
                            <a class="nav-link {{ $selectedCategory === null ? 'active' : '' }}" href="{{ route('services.index', $filterQuery) }}"><i class="bi bi-grid-3x3-gap"></i>All services</a>
                            @foreach ($categories as $category)
                                <a class="nav-link {{ $selectedCategory === $category->id ? 'active' : '' }}" href="{{ route('services.index', ['category' => $category->id, ...$filterQuery]) }}">
                                    <i class="bi {{ $category->icon() }}"></i>{{ $category->name }}
                                    <span class="badge text-bg-light border">{{ $category->services_count }}</span>
                                </a>
                            @endforeach
                        </nav>
                    </div>
                </div>
            </aside>
            <div class="col-lg-9">
                @php
                    $mad = fn (float $amount): string => number_format($amount, 0, ',', ' ').' MAD';
                    $priceSummary = match (true) {
                        $minimumPrice !== null && $maximumPrice !== null => ' with offers between '.number_format($minimumPrice, 0, ',', ' ').' and '.$mad($maximumPrice),
                        $minimumPrice !== null => ' with offers from '.$mad($minimumPrice),
                        $maximumPrice !== null => ' with offers up to '.$mad($maximumPrice),
                        default => '',
                    };
                @endphp
                <p class="text-secondary small mb-3" aria-live="polite">{{ $services->total() }} {{ Str::plural('service', $services->total()) }} found{{ $priceSummary }}</p>
                @if ($services->isEmpty())
                    <x-empty-state icon="bi-search" title="No service matches your search" :text="$isPriceFiltered ? 'No service has received an offer in this price range yet. Try a wider range.' : 'Try another keyword or pick a category from the list.'" />
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
