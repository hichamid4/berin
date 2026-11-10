@extends('layouts.admin')

@section('title', 'Services')
@section('page_title', 'Services')
@section('page_subtitle', 'Each service belongs to one category and requires one or more skills; handymen holding any of them see its requests.')
@section('breadcrumbs')<li class="breadcrumb-item active" aria-current="page">Services</li>@endsection

@section('content')
<form class="card mb-3" method="get" action="{{ route('admin.services.index') }}">
    <div class="card-body">
        <div class="row g-2 align-items-center">
            <div class="col-md-4"><input class="form-control" type="search" name="q" value="{{ $search }}" placeholder="Search services"></div>
            <div class="col-md-3">
                <select class="form-select" name="category">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)<option value="{{ $category->id }}" @selected($categoryId === $category->id)>{{ $category->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2 d-grid"><button class="btn btn-outline-primary" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button></div>
            <div class="col-md-3 d-grid"><a class="btn btn-primary" href="{{ route('admin.services.create', array_filter(['category' => $categoryId])) }}"><i class="bi bi-plus-lg me-1"></i>New service</a></div>
        </div>
    </div>
</form>
<div class="card table-card">
    <div class="card-header"><h3 class="card-title mb-0">{{ $services->total() }} {{ Str::plural('service', $services->total()) }}</h3></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Service</th><th>Category</th><th>Required skills</th><th>Requests</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @forelse ($services as $service)
                    <tr class="{{ $service->is_active ? '' : 'opacity-75' }}">
                        <td><strong class="d-block">{{ $service->name }}</strong><small class="text-secondary d-block text-truncate" style="max-width:14rem">{{ $service->description }}</small></td>
                        <td class="small"><i class="bi {{ $service->category->icon() }} text-primary me-1"></i>{{ $service->category->name }}</td>
                        <td style="min-width:11rem"><div class="d-flex flex-wrap gap-1">@forelse ($service->skills as $skill)<span class="chip">{{ $skill->name }}</span>@empty<span class="badge text-bg-danger">No skill — unreachable</span>@endforelse</div></td>
                        <td class="fw-bold">{{ $service->requests_count }}</td>
                        <td><span class="badge {{ $service->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $service->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td>@include('admin.partials.row-actions', [
                            'name' => $service->name,
                            'isActive' => $service->is_active,
                            'editUrl' => route('admin.services.edit', $service),
                            'toggleUrl' => route('admin.services.toggle', $service),
                            'deleteUrl' => route('admin.services.delete', $service),
                        ])</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-secondary py-4">No service matches these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($services->hasPages())<div class="card-footer">{{ $services->links() }}</div>@endif
</div>
@endsection
