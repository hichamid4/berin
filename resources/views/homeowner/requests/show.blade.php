@extends('layouts.dashboard')

@php
    $status = $serviceRequest->status;
    $accepted = $serviceRequest->acceptedOffer;
    $review = $accepted?->review;
@endphp

@section('title', $serviceRequest->title)
@section('heading', $serviceRequest->title)
@section('subtitle', $serviceRequest->service->category->name.' › '.$serviceRequest->service->name.' · posted '.$serviceRequest->created_at->translatedFormat('j F Y'))
@section('hero_actions')
    @can('update', $serviceRequest)
        <a class="btn btn-outline-light" href="{{ route('homeowner.requests.edit', $serviceRequest) }}"><i class="bi bi-pencil me-1"></i>Edit</a>
    @endcan
    @can('cancel', $serviceRequest)
        <form method="post" action="{{ route('homeowner.requests.cancel', $serviceRequest) }}" data-confirm="Cancel this request? Pending offers will be declined. The request stays in your history and cannot be reopened." data-confirm-title="Cancel request" data-confirm-button="Cancel request" data-confirm-variant="danger">@csrf
            <button class="btn btn-outline-light" type="submit"><i class="bi bi-x-circle me-1"></i>Cancel request</button>
        </form>
    @endcan
@endsection

@section('dashboard')
    {{-- Lifecycle progress --}}
    @php($steps = ['open' => 'Open for offers', 'booked' => 'Booked', 'completion_pending' => 'Done by handyman', 'completed' => 'Confirmed'])
    @php($reached = array_search($status->value, array_keys($steps), true))
    <div class="card mb-4"><div class="card-body p-3 p-md-4">
        @if ($status === \App\RequestStatus::Cancelled)
            <div class="d-flex align-items-center gap-2 text-secondary"><i class="bi bi-x-circle-fill fs-4"></i><div><strong>Cancelled</strong> on {{ $serviceRequest->cancelled_at->translatedFormat('j F Y') }}. This request is kept for your records.</div></div>
        @else
            <div class="lifecycle">
                @foreach ($steps as $key => $label)
                    <div class="lifecycle-step {{ $loop->index <= $reached ? 'done' : '' }} {{ $loop->index === $reached ? 'current' : '' }}">
                        <span class="dot">@if ($loop->index < $reached || $status === \App\RequestStatus::Completed)<i class="bi bi-check-lg"></i>@else{{ $loop->iteration }}@endif</span>
                        <span class="label">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div></div>

    @if ($status === \App\RequestStatus::CompletionPending)
        <div class="card border-warning mb-4"><div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div><h2 class="h5 mb-1"><i class="bi bi-hourglass-split text-warning me-1"></i>{{ $accepted->handyman->user->first_name }} marked the job as done</h2><p class="text-secondary small mb-0">Marked on {{ $serviceRequest->handyman_completed_at->translatedFormat('j F Y \a\t H:i') }}. Confirm once you are satisfied; you can then leave a review.</p></div>
            <form method="post" action="{{ route('homeowner.requests.confirm', $serviceRequest) }}" data-confirm="Confirm that the work is finished to your satisfaction?" data-confirm-button="Yes, confirm completion" data-confirm-variant="success">@csrf
                <button class="btn btn-success" type="submit"><i class="bi bi-check2-circle me-1"></i>Confirm completion</button>
            </form>
        </div></div>
    @endif

    @if ($status === \App\RequestStatus::Completed && $accepted)
        <div class="card mb-4" id="review"><div class="card-body p-4">
            <h2 class="h5 mb-1">{{ $review ? 'Your review' : 'Rate '.$accepted->handyman->user->first_name }}</h2>
            <p class="text-secondary small">{{ $review ? 'You can update your review at any time.' : 'Your rating helps other homeowners choose with confidence.' }}</p>
            <form method="post" action="{{ $review ? route('homeowner.requests.review.update', $serviceRequest) : route('homeowner.requests.review.store', $serviceRequest) }}">
                @csrf @if ($review) @method('PUT') @endif
                <div class="star-input mb-3" role="radiogroup" aria-label="Rating">
                    @for ($star = 5; $star >= 1; $star--)
                        <input type="radio" id="rating{{ $star }}" name="rating" value="{{ $star }}" @checked((int) old('rating', $review?->rating) === $star) required>
                        <label for="rating{{ $star }}" title="{{ $star }} {{ Str::plural('star', $star) }}"><i class="bi bi-star-fill"></i></label>
                    @endfor
                </div>
                @error('rating')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                <textarea class="form-control mb-3 @error('comment') is-invalid @enderror" name="comment" rows="3" maxlength="2000" placeholder="Optional: how did it go?">{{ old('comment', $review?->comment) }}</textarea>
                @error('comment')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <button class="btn btn-primary" type="submit"><i class="bi bi-star me-1"></i>{{ $review ? 'Update review' : 'Publish review' }}</button>
            </form>
        </div></div>
    @endif

    <div class="row g-4 mb-4">
        <div class="col-xl-7">
            <div class="card h-100"><div class="card-body p-4">
                <h2 class="h5 mb-3">Request details</h2>
                <p style="white-space:pre-line">{{ $serviceRequest->description }}</p>
                <dl class="row small mb-0">
                    <dt class="col-sm-4 text-secondary">Service address</dt><dd class="col-sm-8"><i class="bi bi-lock-fill text-secondary me-1"></i>{{ $serviceRequest->service_location }}</dd>
                    <dt class="col-sm-4 text-secondary">City</dt><dd class="col-sm-8">{{ $serviceRequest->service_city }}</dd>
                    <dt class="col-sm-4 text-secondary">Preferred start</dt><dd class="col-sm-8">{{ $serviceRequest->desired_start_date->translatedFormat('l j F Y') }}</dd>
                </dl>
                @if ($serviceRequest->photos->isNotEmpty())
                    <div class="photo-grid mt-3">
                        @foreach ($serviceRequest->photos as $photo)
                            <a href="{{ route('photos.request', $photo) }}" target="_blank" rel="noopener"><img class="photo-thumb" src="{{ route('photos.request', $photo) }}" alt="{{ $photo->original_name }}"></a>
                        @endforeach
                    </div>
                @endif
            </div></div>
        </div>
        <div class="col-xl-5">
            @if ($accepted)
                <div class="card h-100 border-success-subtle"><div class="card-body p-4">
                    <span class="chip mb-3"><i class="bi bi-patch-check-fill"></i>Booked handyman</span>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <x-avatar :user="$accepted->handyman->user" size="avatar-lg" :photo-url="$accepted->handyman->photo_path ? route('handymen.photo', $accepted->handyman) : null" />
                        <div><a class="h5 mb-0 d-block text-decoration-none" href="{{ route('handymen.show', $accepted->handyman) }}">{{ $accepted->handyman->user->fullName() }}</a><small class="text-secondary">{{ $accepted->handyman->city }}</small></div>
                    </div>
                    <div class="price-tag fs-4 mb-1">{{ $accepted->formattedPrice() }}</div>
                    <div class="small text-secondary mb-3"><i class="bi bi-calendar-range me-1"></i>{{ $accepted->start_date->translatedFormat('j M') }} – {{ $accepted->end_date->translatedFormat('j M Y') }}</div>
                    @if ($whatsapp = $accepted->handyman->user->whatsappUrl('Hello '.$accepted->handyman->user->first_name.', I booked you on BreIn for "'.$serviceRequest->title.'".'))
                        <a class="btn btn-success w-100" href="{{ $whatsapp }}" target="_blank" rel="noopener"><i class="bi bi-whatsapp me-1"></i>Contact on WhatsApp</a>
                        <div class="small text-secondary text-center mt-2">{{ $accepted->handyman->user->phone_e164 }}</div>
                    @else
                        <div class="alert alert-light border small mb-0">This handyman has not added a WhatsApp number yet.</div>
                    @endif
                </div></div>
            @else
                <div class="card h-100 bg-primary-soft border-0"><div class="card-body p-4">
                    <h2 class="h5">How offers work</h2>
                    <ul class="small text-secondary ps-3 mb-0">
                        <li class="mb-2">Only handymen holding a skill required by <strong>{{ $serviceRequest->service->name }}</strong> can see this request.</li>
                        <li class="mb-2">Your exact address stays private until you accept an offer.</li>
                        <li>Accepting one offer automatically closes the other pending offers.</li>
                    </ul>
                </div></div>
            @endif
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Offers received <span class="badge text-bg-light border">{{ $serviceRequest->offers->count() }}</span></h2>
    </div>
    @forelse ($serviceRequest->offers as $offer)
        @php($rating = $ratings->get($offer->handyman_id))
        <div class="card mb-3 {{ $offer->status === \App\OfferStatus::Accepted ? 'border-success' : '' }} {{ in_array($offer->status, [\App\OfferStatus::Declined, \App\OfferStatus::Withdrawn, \App\OfferStatus::Superseded], true) ? 'opacity-75' : '' }}">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap gap-3 justify-content-between">
                    <div class="d-flex gap-3 min-w-0">
                        <x-avatar :user="$offer->handyman->user" :photo-url="$offer->handyman->photo_path ? route('handymen.photo', $offer->handyman) : null" />
                        <div class="min-w-0">
                            <a class="fw-bold text-decoration-none" href="{{ route('handymen.show', $offer->handyman) }}">{{ $offer->handyman->user->fullName() }}</a>
                            <div class="d-flex flex-wrap align-items-center gap-2 small text-secondary">
                                <x-rating :value="$rating?->average" :count="$rating?->total ?? 0" />
                                <span>· {{ $offer->handyman->city }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="price-tag fs-5">{{ number_format((float) $offer->price, 0, ',', ' ') }}<small>MAD</small></div>
                        <x-status-badge :status="$offer->status" />
                    </div>
                </div>
                <p class="mt-3 mb-2">{{ $offer->description }}</p>
                <div class="small text-secondary mb-2"><i class="bi bi-calendar-range me-1"></i>{{ $offer->start_date->translatedFormat('j M') }} – {{ $offer->end_date->translatedFormat('j M Y') }} · sent {{ $offer->created_at->diffForHumans() }}</div>
                @if ($offer->photos->isNotEmpty())
                    <div class="photo-grid mb-2">@foreach ($offer->photos as $photo)<a href="{{ route('photos.offer', $photo) }}" target="_blank" rel="noopener"><img class="photo-thumb sm" src="{{ route('photos.offer', $photo) }}" alt="{{ $photo->original_name }}"></a>@endforeach</div>
                @endif
                @can('respond', $offer)
                    <div class="d-flex flex-wrap gap-2 justify-content-end mt-3">
                        <form method="post" action="{{ route('homeowner.offers.decline', $offer) }}" data-confirm="Decline the offer from {{ $offer->handyman->user->fullName() }}?" data-confirm-button="Decline offer" data-confirm-variant="danger">@csrf
                            <button class="btn btn-outline-danger" type="submit"><i class="bi bi-x-lg me-1"></i>Decline</button>
                        </form>
                        <form method="post" action="{{ route('homeowner.offers.accept', $offer) }}" data-confirm="Accept {{ $offer->handyman->user->fullName() }}'s offer for {{ $offer->formattedPrice() }}? The other pending offers will be closed and your service address will be shared with this handyman." data-confirm-title="Accept this offer" data-confirm-button="Accept offer" data-confirm-variant="success">@csrf
                            <button class="btn btn-success" type="submit"><i class="bi bi-check2 me-1"></i>Accept offer</button>
                        </form>
                    </div>
                @endcan
            </div>
        </div>
    @empty
        <x-empty-state icon="bi-inbox" title="No offer yet" text="Qualified handymen are notified through their matching requests list. Offers will appear here." />
    @endforelse
@endsection
