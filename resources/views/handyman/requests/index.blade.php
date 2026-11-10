@extends('layouts.dashboard')

@section('title', 'Matching requests')
@section('heading', 'Requests matching your skills')
@section('subtitle', $requests->total().' open '.Str::plural('request', $requests->total()).' for services that need '.$profile->skills->pluck('name')->take(3)->join(', ').($profile->skills->count() > 3 ? '…' : ''))

@section('dashboard')
    @if ($profile->skills->isEmpty())
        <div class="alert alert-warning d-flex gap-2"><i class="bi bi-exclamation-triangle-fill"></i><div>You have not declared any skill, so no request can match. <a href="{{ route('account.edit') }}#skills">Choose your skills</a>.</div></div>
    @endif

    <form class="filter-bar mb-4" method="get" action="{{ route('handyman.requests.index') }}">
        <div class="row g-2">
            <div class="col-md-4"><input class="form-control" type="search" name="q" value="{{ $search }}" placeholder="Search title or description" aria-label="Search"></div>
            <div class="col-md-3">
                <select class="form-select" name="service" aria-label="Service">
                    <option value="">All my services</option>
                    @foreach ($categories as $category)
                        <optgroup label="{{ $category->name }}">
                            @foreach ($category->services as $service)<option value="{{ $service->id }}" @selected($serviceId === $service->id)>{{ $service->name }}</option>@endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <input class="form-control" list="requestCities" name="city" value="{{ $city }}" placeholder="City" aria-label="City">
                <datalist id="requestCities">@foreach ($cities as $option)<option value="{{ $option }}">@endforeach</datalist>
            </div>
            <div class="col-md-2 d-grid"><button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button></div>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-2">
            <a class="chip text-decoration-none {{ $categoryId === null ? '' : 'chip-muted' }}" href="{{ route('handyman.requests.index', array_filter(['q' => $search, 'city' => $city])) }}">All categories</a>
            @foreach ($categories as $category)
                <a class="chip text-decoration-none {{ $categoryId === $category->id ? '' : 'chip-muted' }}" href="{{ route('handyman.requests.index', array_filter(['category' => $category->id, 'q' => $search, 'city' => $city])) }}"><i class="bi {{ $category->icon() }}"></i>{{ $category->name }}</a>
            @endforeach
        </div>
    </form>

    @if ($requests->isEmpty())
        <x-empty-state icon="bi-bullseye" title="No open request matches these filters" text="Try clearing the filters, or add more skills to your profile." />
    @else
        <div class="row g-3">
            @foreach ($requests as $request)
                <div class="col-md-6">
                    <x-request-card :request="$request" :show-status="false" :url="route('handyman.requests.show', $request)">
                        @if ($request->has_my_offer)<div class="mt-3"><span class="badge text-bg-primary"><i class="bi bi-send-check me-1"></i>You sent an offer</span></div>@endif
                    </x-request-card>
                </div>
            @endforeach
        </div>
        <div class="mt-4">{{ $requests->links() }}</div>
    @endif
@endsection
