@extends('layouts.admin')

@php($editing = $category->exists)
@section('title', $editing ? 'Edit category' : 'New category')
@section('page_title', $editing ? 'Edit “'.$category->name.'”' : 'New category')
@section('breadcrumbs')<li class="breadcrumb-item"><a href="{{ route('admin.categories.index') }}">Categories</a></li><li class="breadcrumb-item active" aria-current="page">{{ $editing ? 'Edit' : 'New' }}</li>@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-6">
        <form class="card" method="post" action="{{ $editing ? route('admin.categories.update', $category) : route('admin.categories.store') }}">
            @csrf @if ($editing) @method('PUT') @endif
            <div class="card-body p-4">
                <div class="mb-3">
                    <label class="form-label" for="name">Name</label>
                    <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $category->name) }}" required maxlength="120" autofocus>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <input type="hidden" name="is_active" value="0">
                <div class="form-check form-switch mb-1">
                    <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $category->is_active))>
                    <label class="form-check-label" for="is_active">Active (visible in the public catalog)</label>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end gap-2">
                <a class="btn btn-outline-secondary" href="{{ route('admin.categories.index') }}">Cancel</a>
                <button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1"></i>{{ $editing ? 'Save changes' : 'Create category' }}</button>
            </div>
        </form>
    </div>
    @if ($editing)
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center"><h3 class="card-title mb-0">Services in this category</h3><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.services.create', ['category' => $category->id]) }}"><i class="bi bi-plus-lg"></i> Add service</a></div>
                <ul class="list-group list-group-flush">
                    @forelse ($category->services as $service)
                        <li class="list-group-item d-flex justify-content-between align-items-center"><a href="{{ route('admin.services.edit', $service) }}">{{ $service->name }}</a><span class="badge {{ $service->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $service->is_active ? 'Active' : 'Inactive' }}</span></li>
                    @empty
                        <li class="list-group-item text-secondary small">No service yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    @endif
</div>
@endsection
