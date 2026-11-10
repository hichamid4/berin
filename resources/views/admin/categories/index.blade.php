@extends('layouts.admin')

@section('title', 'Categories')
@section('page_title', 'Service categories')
@section('page_subtitle', 'Top-level grouping of the service catalog.')
@section('breadcrumbs')<li class="breadcrumb-item active" aria-current="page">Categories</li>@endsection

@section('content')
<div class="row g-3">
    @foreach ($categories as $category)
        <div class="col-md-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body d-flex gap-3">
                    <span class="icon-tile icon-tone-{{ ($loop->index % 8) + 1 }} d-grid rounded-3 flex-shrink-0" style="width:3rem;height:3rem;place-items:center;font-size:1.3rem"><i class="bi {{ $category->icon() }}"></i></span>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start gap-2"><strong>{{ $category->name }}</strong><span class="badge {{ $category->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $category->is_active ? 'Active' : 'Inactive' }}</span></div>
                        <div class="small text-secondary mt-1">{{ $category->services_count }} {{ Str::plural('service', $category->services_count) }} · {{ $category->requests_count }} {{ Str::plural('request', $category->requests_count) }}</div>
                        <a class="small d-inline-block mt-2" href="{{ route('admin.services.index', ['category' => $category->id]) }}">View services <i class="bi bi-arrow-right-short"></i></a>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
<p class="small text-secondary mt-3 mb-0"><i class="bi bi-info-circle me-1"></i>Create, edit and deactivate actions arrive with the category management module.</p>
@endsection
