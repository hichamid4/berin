@extends('layouts.public')

@section('title', 'Find a handyman')

@section('hero')
<section class="page-hero">
    <div class="container">
        <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li><li class="breadcrumb-item active" aria-current="page">Handymen</li></ol></nav>
        <h1>Find a handyman</h1>
        <p>Skilled professionals rated by homeowners after completed jobs.</p>
    </div>
</section>
@endsection

@section('content')
<section class="section pt-4">
    <div class="container">
        <form class="filter-bar mb-4" method="get" action="{{ route('handymen.index') }}">
            <div class="row g-2 align-items-center">
                <div class="col-md-5">
                    <select class="form-select" name="skill" aria-label="Skill">
                        <option value="">Any skill</option>
                        @foreach ($skills as $skill)
                            <option value="{{ $skill->id }}" @selected($skillId === $skill->id)>{{ $skill->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <input class="form-control" list="cities" name="city" value="{{ $city }}" placeholder="City, e.g. Casablanca" aria-label="City">
                    <datalist id="cities">@foreach ($cities as $option)<option value="{{ $option }}">@endforeach</datalist>
                </div>
                <div class="col-md-2 d-grid"><button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button></div>
            </div>
        </form>

        <p class="text-secondary small mb-3">{{ $handymen->total() }} {{ Str::plural('handyman', $handymen->total()) }} found</p>
        @if ($handymen->isEmpty())
            <x-empty-state icon="bi-person-badge" title="No handyman matches these filters" text="Try a different skill or city." />
        @else
            <div class="row g-4">
                @foreach ($handymen as $handyman)
                    <div class="col-md-6 col-xl-4 col-xxl-3"><x-handyman-card :handyman="$handyman" /></div>
                @endforeach
            </div>
            <div class="mt-4">{{ $handymen->links() }}</div>
        @endif
    </div>
</section>
@endsection
