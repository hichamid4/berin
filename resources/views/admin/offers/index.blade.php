@extends('layouts.admin')

@section('title', 'Offers')
@section('page_title', 'Offers')
@section('page_subtitle', 'Priced proposals submitted by handymen, in Moroccan dirham.')
@section('breadcrumbs')<li class="breadcrumb-item active" aria-current="page">Offers</li>@endsection

@section('content')
<div class="d-flex flex-wrap gap-2 mb-3">
    <a class="btn btn-sm {{ $status === null ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('admin.offers.index') }}">All <span class="badge text-bg-light ms-1">{{ $statusCounts->sum() }}</span></a>
    @foreach (\App\OfferStatus::cases() as $option)
        <a class="btn btn-sm {{ $status === $option ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('admin.offers.index', ['status' => $option->value]) }}">{{ $option->label() }} <span class="badge text-bg-light ms-1">{{ $statusCounts->get($option->value, 0) }}</span></a>
    @endforeach
</div>
<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Handyman</th><th>Request</th><th>Homeowner</th><th>Price</th><th>Dates</th><th>Submitted</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($offers as $offer)
                    <tr>
                        <td><div class="d-flex align-items-center gap-2"><x-avatar :user="$offer->handyman->user" size="avatar-sm" /><strong>{{ $offer->handyman->user->fullName() }}</strong></div></td>
                        <td><span class="d-block text-truncate" style="max-width:16rem">{{ $offer->request->title }}</span><small class="text-secondary">{{ $offer->request->service->name }}</small></td>
                        <td class="small">{{ $offer->request->homeowner->user->fullName() }}</td>
                        <td class="fw-bold text-nowrap">{{ $offer->formattedPrice() }}</td>
                        <td class="small text-nowrap">{{ $offer->start_date->translatedFormat('j M') }} – {{ $offer->end_date->translatedFormat('j M Y') }}</td>
                        <td class="small text-nowrap">{{ $offer->created_at->translatedFormat('j M Y') }}</td>
                        <td><x-status-badge :status="$offer->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-secondary py-4">No offer in this state.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($offers->hasPages())<div class="card-footer">{{ $offers->links() }}</div>@endif
</div>
@endsection
