@extends('layouts.admin')

@section('title', 'Categories')
@section('page_title', 'Service categories')
@section('page_subtitle', 'Top-level grouping of the catalog. Each category contains services; skills are linked through services.')
@section('breadcrumbs')<li class="breadcrumb-item active" aria-current="page">Categories</li>@endsection

@section('content')
<div class="d-flex justify-content-end mb-3"><a class="btn btn-primary" href="{{ route('admin.categories.create') }}"><i class="bi bi-plus-lg me-1"></i>New category</a></div>
<div class="row g-3">
    @forelse ($categories as $category)
        <div class="col-md-6 col-xl-4">
            <div class="card h-100 {{ $category->is_active ? '' : 'opacity-75' }}">
                <div class="card-body d-flex gap-3">
                    <span class="icon-tile icon-tone-{{ ($loop->index % 8) + 1 }} d-grid rounded-3 flex-shrink-0" style="width:3rem;height:3rem;place-items:center;font-size:1.3rem"><i class="bi {{ $category->icon() }}"></i></span>
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex justify-content-between align-items-start gap-2"><strong>{{ $category->name }}</strong><span class="badge {{ $category->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $category->is_active ? 'Active' : 'Inactive' }}</span></div>
                        <div class="small text-secondary mt-1">{{ $category->active_services_count }}/{{ $category->services_count }} active {{ Str::plural('service', $category->services_count) }} · {{ $category->requests_count }} {{ Str::plural('request', $category->requests_count) }}</div>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <a class="small" href="{{ route('admin.services.index', ['category' => $category->id]) }}">Services <i class="bi bi-arrow-right-short"></i></a>
                            @include('admin.partials.row-actions', [
                                'name' => $category->name,
                                'isActive' => $category->is_active,
                                'editUrl' => route('admin.categories.edit', $category),
                                'toggleUrl' => route('admin.categories.toggle', $category),
                                'deleteUrl' => route('admin.categories.delete', $category),
                            ])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><x-empty-state icon="bi-grid" title="No category yet" text="Create the first category to start building the catalog." /></div>
    @endforelse
</div>
@endsection
