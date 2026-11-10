@extends('layouts.admin')

@php($editing = $skill->exists)
@section('title', $editing ? 'Edit skill' : 'New skill')
@section('page_title', $editing ? 'Edit “'.$skill->name.'”' : 'New skill')
@section('breadcrumbs')<li class="breadcrumb-item"><a href="{{ route('admin.skills.index') }}">Skills</a></li><li class="breadcrumb-item active" aria-current="page">{{ $editing ? 'Edit' : 'New' }}</li>@endsection

@section('content')
<form method="post" action="{{ $editing ? route('admin.skills.update', $skill) : route('admin.skills.store') }}">
    @csrf @if ($editing) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card mb-3"><div class="card-body p-4">
                <div class="mb-3">
                    <label class="form-label" for="name">Name</label>
                    <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $skill->name) }}" required maxlength="120">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="description">Description <span class="text-secondary fw-normal">(optional)</span></label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $skill->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <input type="hidden" name="is_active" value="0">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $skill->is_active))>
                    <label class="form-check-label" for="is_active">Active (handymen can select it)</label>
                </div>
            </div></div>
            @if ($editing)
                <div class="card">
                    <div class="card-header"><h3 class="card-title mb-0">Handymen holding this skill ({{ $skill->handymen->count() }})</h3></div>
                    <ul class="list-group list-group-flush">
                        @forelse ($skill->handymen as $handyman)
                            <li class="list-group-item d-flex align-items-center gap-2"><x-avatar :user="$handyman->user" size="avatar-sm" /><a href="{{ route('admin.users.show', $handyman->user_id) }}">{{ $handyman->user->fullName() }}</a><span class="ms-auto small text-secondary">{{ $handyman->city }}</span></li>
                        @empty
                            <li class="list-group-item small text-secondary">Nobody holds this skill yet.</li>
                        @endforelse
                    </ul>
                </div>
            @endif
        </div>
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header"><h3 class="card-title mb-0">Services that require this skill</h3></div>
                <div class="card-body p-4">
                    <p class="small text-secondary">Linking a service makes its requests visible to handymen with this skill. Unlinking only removes the relationship.</p>
                    @foreach ($categories as $category)
                        @continue($category->services->isEmpty())
                        <div class="mb-3">
                            <div class="small fw-bold text-uppercase text-secondary mb-1" style="letter-spacing:.06em"><i class="bi {{ $category->icon() }} me-1"></i>{{ $category->name }}</div>
                            <div class="row g-1">
                                @foreach ($category->services as $service)
                                    <div class="col-sm-6">
                                        <label class="form-check small">
                                            <input class="form-check-input" type="checkbox" name="service_ids[]" value="{{ $service->id }}" @checked(in_array($service->id, array_map('intval', $selectedServices), true))>
                                            <span class="form-check-label">{{ $service->name }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    @error('service_ids.*')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-3">
        <a class="btn btn-outline-secondary" href="{{ route('admin.skills.index') }}">Cancel</a>
        <button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1"></i>{{ $editing ? 'Save changes' : 'Create skill' }}</button>
    </div>
</form>
@endsection
