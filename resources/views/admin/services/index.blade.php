@extends('layouts.admin')

@section('title', 'Services')
@section('page_title', 'Services')
@section('page_subtitle', 'Each service is mapped to the skills a handyman must hold to see its requests.')
@section('breadcrumbs')<li class="breadcrumb-item active" aria-current="page">Services</li>@endsection

@section('content')
<form class="card mb-3" method="get" action="{{ route('admin.services.index') }}">
    <div class="card-body">
        <div class="row g-2 align-items-center">
            <div class="col-md-4">
                <select class="form-select" name="category">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)<option value="{{ $category->id }}" @selected($categoryId === $category->id)>{{ $category->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2 d-grid"><button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button></div>
        </div>
    </div>
</form>
<div class="card table-card">
    <div class="card-header"><h3 class="card-title mb-0">{{ $services->total() }} {{ Str::plural('service', $services->total()) }}</h3></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Service</th><th>Category</th><th>Required skills</th><th>Requests</th><th>Status</th></tr></thead>
            <tbody>
                @foreach ($services as $service)
                    <tr>
                        <td><strong class="d-block">{{ $service->name }}</strong><small class="text-secondary d-block text-truncate" style="max-width:26rem">{{ $service->description }}</small></td>
                        <td class="small"><i class="bi {{ $service->category->icon() }} text-primary me-1"></i>{{ $service->category->name }}</td>
                        <td><div class="d-flex flex-wrap gap-1">@foreach ($service->skills as $skill)<span class="chip">{{ $skill->name }}</span>@endforeach</div></td>
                        <td class="fw-bold">{{ $service->requests_count }}</td>
                        <td><span class="badge {{ $service->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $service->is_active ? 'Active' : 'Inactive' }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if ($services->hasPages())<div class="card-footer">{{ $services->links() }}</div>@endif
</div>
@endsection
