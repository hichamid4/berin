@extends('layouts.dashboard')

@php($editing = $serviceRequest->exists)
@section('title', $editing ? 'Edit request' : 'New request')
@section('heading', $editing ? 'Edit your request' : 'Post a new request')
@section('subtitle', $editing ? 'You can edit a request while it is open for offers.' : 'Describe the job. Only handymen with a matching skill will see it.')

@section('dashboard')
<form class="card" method="post" enctype="multipart/form-data" action="{{ $editing ? route('homeowner.requests.update', $serviceRequest) : route('homeowner.requests.store') }}">
    @csrf @if ($editing) @method('PUT') @endif
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label" for="service_id">Service</label>
                @if ($editing)
                    <div class="form-control bg-body-tertiary"><i class="bi {{ $serviceRequest->service->category->icon() }} text-primary me-1"></i>{{ $serviceRequest->service->category->name }} › {{ $serviceRequest->service->name }}</div>
                    <div class="form-text">The service cannot change after posting because handymen already responded to it. Cancel and post a new request if needed.</div>
                @else
                    <select class="form-select @error('service_id') is-invalid @enderror" id="service_id" name="service_id" required>
                        <option value="">Choose the service you need</option>
                        @foreach ($categories as $category)
                            <optgroup label="{{ $category->name }}">
                                @foreach ($category->services as $service)
                                    <option value="{{ $service->id }}" @selected((int) old('service_id', $serviceRequest->service_id) === $service->id)>{{ $service->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @error('service_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @endif
            </div>
            <div class="col-12">
                <label class="form-label" for="title">Title</label>
                <input class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $serviceRequest->title) }}" maxlength="180" required placeholder="e.g. Leaking kitchen tap and damp cabinet">
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label" for="description">Description</label>
                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="5" required placeholder="What needs to be done, sizes, materials you already have, access constraints…">{{ old('description', $serviceRequest->description) }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-8">
                <label class="form-label" for="service_location">Service address <span class="badge text-bg-light border"><i class="bi bi-lock-fill"></i> private</span></label>
                <input class="form-control @error('service_location') is-invalid @enderror" id="service_location" name="service_location" value="{{ old('service_location', $serviceRequest->service_location) }}" maxlength="500" required>
                <div class="form-text">Only the handyman whose offer you accept will see this address.</div>
                @error('service_location')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="service_city">City</label>
                <input class="form-control @error('service_city') is-invalid @enderror" id="service_city" name="service_city" value="{{ old('service_city', $serviceRequest->service_city) }}" maxlength="120" required>
                @error('service_city')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="desired_start_date">Preferred start date</label>
                <input class="form-control @error('desired_start_date') is-invalid @enderror" id="desired_start_date" name="desired_start_date" type="date" min="{{ now()->toDateString() }}" value="{{ old('desired_start_date', $serviceRequest->desired_start_date?->toDateString()) }}" required>
                @error('desired_start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-8">
                @php($remaining = \App\Models\ServiceRequest::MAX_PHOTOS - ($editing ? $serviceRequest->photos->count() : 0))
                <label class="form-label" for="photos">Photos <span class="text-secondary fw-normal">(optional, up to {{ $remaining }} more, JPG/PNG/WebP, 5 MB each)</span></label>
                <input class="form-control @error('photos') is-invalid @enderror @error('photos.*') is-invalid @enderror" id="photos" name="photos[]" type="file" accept="image/jpeg,image/png,image/webp" multiple data-preview="photoPreview" @disabled($remaining <= 0)>
                @error('photos')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @foreach ($errors->get('photos.*') as $messages)<div class="text-danger small">{{ $messages[0] }}</div>@endforeach
                <div id="photoPreview" class="photo-grid mt-2"></div>
            </div>
        </div>
    </div>
    <div class="card-footer bg-transparent d-flex justify-content-end gap-2">
        <a class="btn btn-outline-secondary" href="{{ $editing ? route('homeowner.requests.show', $serviceRequest) : route('homeowner.dashboard') }}">Cancel</a>
        <button class="btn btn-primary" type="submit"><i class="bi bi-send me-1"></i>{{ $editing ? 'Save changes' : 'Publish request' }}</button>
    </div>
</form>

@if ($editing && $serviceRequest->photos->isNotEmpty())
    <div class="card mt-4"><div class="card-body p-4">
        <h2 class="h6 mb-3">Current photos</h2>
        <div class="photo-grid">
            @foreach ($serviceRequest->photos as $photo)
                <div class="photo-item">
                    <img class="photo-thumb" src="{{ route('photos.request', $photo) }}" alt="{{ $photo->original_name }}">
                    <form method="post" action="{{ route('homeowner.requests.photos.destroy', [$serviceRequest, $photo]) }}" data-confirm="Remove this photo from the request?" data-confirm-button="Remove" data-confirm-variant="danger">@csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger photo-remove" type="submit" title="Remove photo"><i class="bi bi-x-lg"></i></button>
                    </form>
                </div>
            @endforeach
        </div>
    </div></div>
@endif
@endsection
