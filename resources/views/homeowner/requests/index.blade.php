@extends('layouts.dashboard')

@section('title', 'My requests')
@section('heading', 'My requests')
@section('subtitle', 'Follow every request from posting to the finished job.')
@section('hero_actions')<a class="btn btn-accent" href="{{ route('homeowner.requests.create') }}"><i class="bi bi-plus-circle me-1"></i>New request</a>@endsection

@section('dashboard')
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a class="btn btn-sm {{ $status === null ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('homeowner.requests.index') }}">All</a>
        @foreach (\App\RequestStatus::cases() as $option)
            <a class="btn btn-sm {{ $status === $option ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('homeowner.requests.index', ['status' => $option->value]) }}">{{ $option->label() }}</a>
        @endforeach
    </div>
    @if ($requests->isEmpty())
        <x-empty-state icon="bi-clipboard-plus" title="No request here yet" text="Describe the work you need and qualified handymen will send you offers.">
            <a class="btn btn-primary mt-3" href="{{ route('homeowner.requests.create') }}">Post a request</a>
        </x-empty-state>
    @else
        <div class="row g-3">
            @foreach ($requests as $request)
                <div class="col-md-6"><x-request-card :request="$request" :url="route('homeowner.requests.show', $request)">
                    <div class="d-flex justify-content-between align-items-center small mt-3">
                        @if ($request->pending_offers_count)<span class="badge text-bg-warning">{{ $request->pending_offers_count }} new {{ Str::plural('offer', $request->pending_offers_count) }}</span>@else<span></span>@endif
                        @if ($request->acceptedOffer)<span class="text-secondary">with <strong>{{ $request->acceptedOffer->handyman->user->fullName() }}</strong> · {{ $request->acceptedOffer->formattedPrice() }}</span>@endif
                    </div>
                </x-request-card></div>
            @endforeach
        </div>
        <div class="mt-4">{{ $requests->links() }}</div>
    @endif
@endsection
