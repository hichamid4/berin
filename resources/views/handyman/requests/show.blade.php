@extends('layouts.dashboard')

@section('title', $serviceRequest->title)
@section('heading', $serviceRequest->title)
@section('subtitle', $serviceRequest->service->category->name.' › '.$serviceRequest->service->name.' · '.$serviceRequest->service_city)
@section('hero_actions')<a class="btn btn-outline-light" href="{{ route('handyman.requests.index') }}"><i class="bi bi-arrow-left me-1"></i>All matching requests</a>@endsection

@section('dashboard')
    <div class="row g-4 mb-4">
        <div class="col-xl-7">
            <div class="card h-100"><div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                    <h2 class="h5 mb-0">Job description</h2>
                    <x-status-badge :status="$serviceRequest->status" />
                </div>
                <p style="white-space:pre-line">{{ $serviceRequest->description }}</p>
                <dl class="row small mb-0">
                    <dt class="col-sm-4 text-secondary">Homeowner</dt><dd class="col-sm-8">{{ $serviceRequest->homeowner->user->shortName() }}</dd>
                    <dt class="col-sm-4 text-secondary">City</dt><dd class="col-sm-8">{{ $serviceRequest->service_city }}</dd>
                    <dt class="col-sm-4 text-secondary">Service address</dt>
                    <dd class="col-sm-8">
                        @if ($canSeeLocation)
                            <i class="bi bi-geo-alt-fill text-primary me-1"></i>{{ $serviceRequest->service_location }}
                        @else
                            <span class="text-secondary"><i class="bi bi-lock-fill me-1"></i>Shared only once your offer is accepted</span>
                        @endif
                    </dd>
                    <dt class="col-sm-4 text-secondary">Preferred start</dt><dd class="col-sm-8">{{ $serviceRequest->desired_start_date->translatedFormat('l j F Y') }}</dd>
                    <dt class="col-sm-4 text-secondary">Offers so far</dt><dd class="col-sm-8">{{ $serviceRequest->offers_count }}</dd>
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
            <div class="card h-100 bg-primary-soft border-0"><div class="card-body p-4">
                <h2 class="h6 text-uppercase fw-bold text-secondary" style="letter-spacing:.08em">Why you see this request</h2>
                <p class="small mb-2">The service <strong>{{ $serviceRequest->service->name }}</strong> requires one of these skills:</p>
                <div class="d-flex flex-wrap gap-1">
                    @php($mySkillIds = $user->handymanProfile->skills()->pluck('skills.id'))
                    @foreach ($serviceRequest->service->skills as $skill)
                        <span class="chip {{ $mySkillIds->contains($skill->id) ? '' : 'chip-muted' }}">@if ($mySkillIds->contains($skill->id))<i class="bi bi-check-circle-fill"></i>@endif{{ $skill->name }}</span>
                    @endforeach
                </div>
            </div></div>
        </div>
    </div>

    @foreach ($myOffers as $offer)
        <div class="card mb-3 {{ $offer->status === \App\OfferStatus::Accepted ? 'border-success' : '' }}"><div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                <h2 class="h5 mb-0">Your offer</h2>
                <div class="text-end"><div class="price-tag fs-5">{{ number_format((float) $offer->price, 0, ',', ' ') }}<small>MAD</small></div><x-status-badge :status="$offer->status" /></div>
            </div>
            <p class="mb-2">{{ $offer->description }}</p>
            <div class="small text-secondary mb-2"><i class="bi bi-calendar-range me-1"></i>{{ $offer->start_date->translatedFormat('j M') }} – {{ $offer->end_date->translatedFormat('j M Y') }} · sent {{ $offer->created_at->diffForHumans() }}</div>
            @if ($offer->photos->isNotEmpty())
                <div class="photo-grid mb-2">@foreach ($offer->photos as $photo)<img class="photo-thumb sm" src="{{ route('photos.offer', $photo) }}" alt="{{ $photo->original_name }}">@endforeach</div>
            @endif
            @if ($offer->review)
                <div class="border rounded-3 p-3 bg-body-tertiary mt-3"><x-rating :value="$offer->review->rating" :show-value="false" /><p class="small mb-0 mt-1">{{ $offer->review->comment }}</p></div>
            @endif
            <div class="d-flex flex-wrap gap-2 justify-content-end mt-3">
                @if ($offer->isPending() && $serviceRequest->isOpen())
                    <a class="btn btn-outline-primary" href="{{ route('handyman.offers.edit', $offer) }}"><i class="bi bi-pencil me-1"></i>Edit offer</a>
                @endif
                @if ($offer->isPending())
                    <form method="post" action="{{ route('handyman.offers.withdraw', $offer) }}" data-confirm="Withdraw this offer? The homeowner will no longer be able to accept it." data-confirm-button="Withdraw offer" data-confirm-variant="danger">@csrf
                        <button class="btn btn-outline-danger" type="submit"><i class="bi bi-arrow-counterclockwise me-1"></i>Withdraw</button>
                    </form>
                @endif
                @if ($offer->status === \App\OfferStatus::Accepted && $serviceRequest->status === \App\RequestStatus::Booked)
                    <form method="post" action="{{ route('handyman.offers.complete', $offer) }}" data-confirm="Mark the job as completed? The homeowner will be asked to confirm." data-confirm-button="Mark as completed" data-confirm-variant="success">@csrf
                        <button class="btn btn-success" type="submit"><i class="bi bi-check2-circle me-1"></i>Mark job as completed</button>
                    </form>
                @elseif ($offer->status === \App\OfferStatus::Accepted && $serviceRequest->status === \App\RequestStatus::CompletionPending)
                    <span class="badge text-bg-warning align-self-center">Waiting for the homeowner to confirm completion</span>
                @endif
            </div>
        </div></div>
    @endforeach

    @if ($canOffer)
        <form class="card" method="post" enctype="multipart/form-data" action="{{ route('handyman.offers.store', $serviceRequest) }}" id="offer">
            @csrf
            <div class="card-body p-4">
                <h2 class="h5 mb-1">Send an offer</h2>
                <p class="small text-secondary mb-4">Prices are in Moroccan dirham. The end date cannot be before the start date.</p>
                @include('handyman.offers.fields', ['offer' => new \App\Models\Offer(), 'remainingPhotos' => \App\Models\Offer::MAX_PHOTOS])
            </div>
            <div class="card-footer bg-transparent d-flex justify-content-end"><button class="btn btn-primary" type="submit"><i class="bi bi-send me-1"></i>Send offer</button></div>
        </form>
    @elseif ($myOffers->isEmpty())
        <x-empty-state icon="bi-lock" title="Offers are closed" text="This request is no longer open for new offers." />
    @endif
@endsection
