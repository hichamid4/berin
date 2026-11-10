{{-- Offer fields shared by the create and edit forms. Expects $offer (may be new) and $remainingPhotos. --}}
<div class="row g-3">
    <div class="col-sm-4">
        <label class="form-label" for="price">Price</label>
        <div class="input-group">
            <input class="form-control @error('price') is-invalid @enderror" id="price" name="price" type="number" min="1" step="0.01" value="{{ old('price', $offer->price !== null ? (float) $offer->price : null) }}" required>
            <span class="input-group-text">MAD</span>
            @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-sm-4">
        <label class="form-label" for="start_date">Start date</label>
        <input class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date" type="date" min="{{ now()->toDateString() }}" value="{{ old('start_date', $offer->start_date?->toDateString()) }}" required>
        @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-sm-4">
        <label class="form-label" for="end_date">End date</label>
        <input class="form-control @error('end_date') is-invalid @enderror" id="end_date" name="end_date" type="date" min="{{ now()->toDateString() }}" value="{{ old('end_date', $offer->end_date?->toDateString()) }}" required>
        @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label class="form-label" for="description">What your offer includes</label>
        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4" required placeholder="Work included, materials, guarantee, what the homeowner should prepare…">{{ old('description', $offer->description) }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label class="form-label" for="photos">Photos <span class="text-secondary fw-normal">(optional, up to {{ $remainingPhotos }} more, e.g. similar past work)</span></label>
        <input class="form-control @error('photos') is-invalid @enderror" id="photos" name="photos[]" type="file" accept="image/jpeg,image/png,image/webp" multiple data-preview="offerPhotoPreview" @disabled($remainingPhotos <= 0)>
        @error('photos')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @foreach ($errors->get('photos.*') as $messages)<div class="text-danger small">{{ $messages[0] }}</div>@endforeach
        <div id="offerPhotoPreview" class="photo-grid mt-2"></div>
    </div>
</div>
