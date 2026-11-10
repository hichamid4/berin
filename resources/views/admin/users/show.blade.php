@extends('layouts.admin')

@php($profile = $member->homeownerProfile ?? $member->handymanProfile)
@section('title', $member->fullName())
@section('page_title', $member->fullName())
@section('page_subtitle', ucfirst($member->role->value).' account · joined '.$member->created_at->translatedFormat('j F Y'))
@section('breadcrumbs')<li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li><li class="breadcrumb-item active" aria-current="page">Details</li>@endsection

@section('content')
<div class="row g-3">
    <div class="col-xl-4">
        <div class="card mb-3">
            <div class="card-body text-center p-4">
                <x-avatar :user="$member" size="avatar-xl" class="mb-3" />
                <h3 class="h5 mb-0">{{ $member->fullName() }}</h3>
                <div class="text-secondary small mb-2">{{ $member->username ? '@'.$member->username : 'anonymized account' }}</div>
                <span class="badge {{ match($member->status) { 'active' => 'text-bg-success', 'blocked' => 'text-bg-danger', default => 'text-bg-secondary' } }}">{{ ucfirst($member->status) }}</span>
                <span class="badge text-bg-light border text-capitalize">{{ $member->role->value }}</span>
            </div>
            <ul class="list-group list-group-flush small">
                <li class="list-group-item d-flex justify-content-between"><span class="text-secondary">Email</span><span>{{ $member->email ?? '—' }}</span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-secondary">Verified</span><span>{{ $member->email_verified_at?->translatedFormat('j M Y') ?? 'No' }}</span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-secondary">Phone</span><span>{{ $member->phone_e164 ?? '—' }}</span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-secondary">City</span><span>{{ $profile?->city ?? '—' }}</span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-secondary">Address</span><span class="text-end">{{ $profile?->address ?? '—' }}</span></li>
                @if ($member->status === 'blocked')
                    <li class="list-group-item d-flex justify-content-between"><span class="text-secondary">Blocked</span><span>{{ $member->blocked_at->translatedFormat('j M Y H:i') }} by {{ $member->blockedBy?->fullName() }}</span></li>
                @endif
            </ul>
        </div>

        @if (! $member->isAdministrator() && $member->status !== 'anonymized')
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Account actions</h3></div>
                <div class="card-body d-grid gap-2">
                    <a class="btn btn-outline-primary" href="{{ route('admin.users.edit', $member) }}"><i class="bi bi-pencil me-1"></i>Edit profile</a>
                    @if ($member->status === 'active')
                        <form method="post" action="{{ route('admin.users.block', $member) }}" data-confirm="Block {{ $member->fullName() }}? They will be signed out immediately and cannot use protected marketplace actions until unblocked." data-confirm-button="Block account" data-confirm-variant="danger">@csrf
                            <button class="btn btn-outline-danger w-100" type="submit"><i class="bi bi-slash-circle me-1"></i>Block account</button>
                        </form>
                    @else
                        <form method="post" action="{{ route('admin.users.unblock', $member) }}">@csrf
                            <button class="btn btn-success w-100" type="submit"><i class="bi bi-check-circle me-1"></i>Unblock account</button>
                        </form>
                    @endif
                    <hr class="my-2">
                    @if ($closureBlocker)
                        <div class="alert alert-warning small mb-0"><i class="bi bi-shield-exclamation me-1"></i><strong>Anonymization unavailable.</strong> {{ $closureBlocker }}</div>
                    @else
                        <button class="btn btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#anonymizeForm"><i class="bi bi-person-x me-1"></i>Anonymize account…</button>
                        <form class="collapse border rounded-3 p-3 small" id="anonymizeForm" method="post" action="{{ route('admin.users.anonymize', $member) }}">@csrf
                            <p class="mb-2">This permanently removes the name, username, email, phone and addresses. Open requests are cancelled, pending offers withdrawn and favorites removed. Completed jobs and reviews stay as history under “Former member”.</p>
                            <label class="form-label" for="confirmation">Type <code>ANONYMIZE</code> to confirm</label>
                            <input class="form-control form-control-sm mb-2 @error('confirmation') is-invalid @enderror" id="confirmation" name="confirmation" autocomplete="off" required>
                            @error('confirmation')<div class="invalid-feedback mb-2">{{ $message }}</div>@enderror
                            <button class="btn btn-danger btn-sm w-100" type="submit">Anonymize permanently</button>
                        </form>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <div class="col-xl-8">
        @if ($member->isHandyman())
            <div class="row g-3 mb-3">
                <div class="col-sm-4"><div class="small-box text-bg-primary mb-0"><div class="inner"><h3>{{ $offers->count() }}</h3><p>Offers sent</p></div><i class="small-box-icon bi bi-cash-stack"></i></div></div>
                <div class="col-sm-4"><div class="small-box text-bg-success mb-0"><div class="inner"><h3>{{ $offers->where('status', \App\OfferStatus::Accepted)->count() }}</h3><p>Accepted</p></div><i class="small-box-icon bi bi-check2-circle"></i></div></div>
                <div class="col-sm-4"><div class="small-box text-bg-warning mb-0"><div class="inner"><h3>{{ $reviewStats?->total ? number_format($reviewStats->average, 1) : '–' }}</h3><p>{{ $reviewStats?->total ?? 0 }} reviews</p></div><i class="small-box-icon bi bi-star"></i></div></div>
            </div>
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Skills</h3></div>
                <div class="card-body d-flex flex-wrap gap-1">@forelse ($member->handymanProfile?->skills ?? [] as $skill)<span class="chip">{{ $skill->name }}</span>@empty<span class="text-secondary small">No skill declared.</span>@endforelse</div>
            </div>
            <div class="card table-card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Offers</h3></div>
                <div class="table-responsive"><table class="table table-hover mb-0">
                    <thead><tr><th>Request</th><th>Price</th><th>Offer</th><th>Request status</th></tr></thead>
                    <tbody>
                        @forelse ($offers as $offer)
                            <tr><td><span class="d-block text-truncate" style="max-width:20rem">{{ $offer->request->title }}</span><small class="text-secondary">{{ $offer->request->service->name }}</small></td><td class="fw-bold text-nowrap">{{ $offer->formattedPrice() }}</td><td><x-status-badge :status="$offer->status" /></td><td><x-status-badge :status="$offer->request->status" /></td></tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-secondary py-3">No offer yet.</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </div>
        @elseif ($member->isHomeowner())
            <div class="card table-card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Service requests ({{ $requests->count() }})</h3></div>
                <div class="table-responsive"><table class="table table-hover mb-0">
                    <thead><tr><th>Request</th><th>Service</th><th>Offers</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($requests as $request)
                            <tr><td><span class="d-block text-truncate" style="max-width:20rem">{{ $request->title }}</span><small class="text-secondary">{{ $request->service_city }} · {{ $request->created_at->translatedFormat('j M Y') }}</small></td><td class="small">{{ $request->service->name }}</td><td class="fw-bold">{{ $request->offers_count }}</td><td><x-status-badge :status="$request->status" /></td></tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-secondary py-3">No request yet.</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </div>
        @endif

        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">Recent activity</h3></div>
            <div class="card-body">
                @if ($activity->isEmpty())
                    <p class="text-secondary small mb-0">No recorded activity.</p>
                @else
                    <ul class="activity-timeline">
                        @foreach ($activity as $event)
                            <li><div class="small"><strong>{{ $event->actor?->fullName() ?? 'System' }}</strong> <span class="text-secondary">{{ $event->action }}</span></div><small class="text-secondary">{{ $event->created_at->translatedFormat('j M Y H:i') }}</small></li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
