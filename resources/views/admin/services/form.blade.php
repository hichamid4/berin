@extends('layouts.admin')

@php($editing = $service->exists)
@section('title', $editing ? 'Edit service' : 'New service')
@section('page_title', $editing ? 'Edit “'.$service->name.'”' : 'New service')
@section('breadcrumbs')<li class="breadcrumb-item"><a href="{{ route('admin.services.index') }}">Services</a></li><li class="breadcrumb-item active" aria-current="page">{{ $editing ? 'Edit' : 'New' }}</li>@endsection

@section('content')
<form method="post" action="{{ $editing ? route('admin.services.update', $service) : route('admin.services.store') }}">
    @csrf @if ($editing) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100"><div class="card-body p-4">
                <div class="mb-3">
                    <label class="form-label" for="category_id">Category</label>
                    <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id" required>
                        <option value="">Choose a category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((int) old('category_id', $service->category_id) === $category->id)>{{ $category->name }}{{ $category->is_active ? '' : ' (inactive)' }}</option>
                        @endforeach
                    </select>
                    @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="name">Name</label>
                    <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $service->name) }}" required maxlength="160">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="description">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="5" required>{{ old('description', $service->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <input type="hidden" name="is_active" value="0">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $service->is_active))>
                    <label class="form-check-label" for="is_active">Active (accepts new requests)</label>
                </div>
            </div></div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h3 class="card-title mb-0">Required skills</h3></div>
                <div class="card-body p-4">
                    <p class="small text-secondary">A handyman sees requests for this service when they hold <strong>at least one</strong> of the selected skills. Unchecking a skill only removes the link; the skill itself is never deleted.</p>
                    @error('skill_ids')<div class="alert alert-danger py-2 small">{{ $message }}</div>@enderror
                    <div class="row g-2" style="max-height:24rem;overflow:auto">
                        @foreach ($skills as $skill)
                            <div class="col-sm-6">
                                <label class="d-flex gap-2 align-items-start border rounded-3 p-2 h-100 {{ $skill->is_active ? '' : 'opacity-75' }}">
                                    <input class="form-check-input mt-1" type="checkbox" name="skill_ids[]" value="{{ $skill->id }}" @checked(in_array($skill->id, array_map('intval', $selectedSkills), true))>
                                    <span class="small"><strong class="d-block">{{ $skill->name }}</strong><span class="text-secondary">{{ $skill->handymen_count }} {{ Str::plural('handyman', $skill->handymen_count) }}{{ $skill->is_active ? '' : ' · inactive' }}</span></span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <a class="small d-inline-block mt-3" href="{{ route('admin.skills.create') }}"><i class="bi bi-plus-lg"></i> Create a new skill</a>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-3">
        <a class="btn btn-outline-secondary" href="{{ route('admin.services.index') }}">Cancel</a>
        <button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1"></i>{{ $editing ? 'Save changes' : 'Create service' }}</button>
    </div>
</form>
@endsection
