@extends('layouts.dashboard')

@section('title', 'Edit offer')
@section('heading', 'Edit your offer')
@section('subtitle', 'For “'.$offer->request->title.'” · '.$offer->request->service_city)

@section('dashboard')
<form class="card" method="post" enctype="multipart/form-data" action="{{ route('handyman.offers.update', $offer) }}">
    @csrf @method('PUT')
    <div class="card-body p-4">
        @include('handyman.offers.fields', ['remainingPhotos' => \App\Models\Offer::MAX_PHOTOS - $offer->photos->count()])
    </div>
    <div class="card-footer bg-transparent d-flex justify-content-end gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('handyman.requests.show', $offer->request_id) }}">Cancel</a>
        <button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1"></i>Save offer</button>
    </div>
</form>

@if ($offer->photos->isNotEmpty())
    <div class="card mt-4"><div class="card-body p-4">
        <h2 class="h6 mb-3">Current photos</h2>
        <div class="photo-grid">
            @foreach ($offer->photos as $photo)
                <div class="photo-item">
                    <img class="photo-thumb" src="{{ route('photos.offer', $photo) }}" alt="{{ $photo->original_name }}">
                    <form method="post" action="{{ route('handyman.offers.photos.destroy', [$offer, $photo]) }}" data-confirm="Remove this photo from your offer?" data-confirm-button="Remove" data-confirm-variant="danger">@csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger photo-remove" type="submit" title="Remove photo"><i class="bi bi-x-lg"></i></button>
                    </form>
                </div>
            @endforeach
        </div>
    </div></div>
@endif
@endsection
