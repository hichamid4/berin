@extends('layouts.admin')

@section('title', 'Requests')
@section('page_title', 'Service requests')
@section('page_subtitle', 'Every request posted by homeowners, with its lifecycle state.')
@section('breadcrumbs')<li class="breadcrumb-item active" aria-current="page">Requests</li>@endsection

@section('content')
<div class="d-flex flex-wrap gap-2 mb-3">
    <a class="btn btn-sm {{ $status === null ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('admin.requests.index') }}">All <span class="badge text-bg-light ms-1">{{ $statusCounts->sum() }}</span></a>
    @foreach (\App\RequestStatus::cases() as $option)
        <a class="btn btn-sm {{ $status === $option ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('admin.requests.index', ['status' => $option->value]) }}">{{ $option->label() }} <span class="badge text-bg-light ms-1">{{ $statusCounts->get($option->value, 0) }}</span></a>
    @endforeach
</div>
<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Request</th><th>Homeowner</th><th>Service</th><th>Start</th><th>Offers</th><th>Accepted handyman</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($requests as $request)
                    <tr>
                        <td><strong class="d-block text-truncate" style="max-width:18rem">{{ $request->title }}</strong><small class="text-secondary"><i class="bi bi-geo-alt me-1"></i>{{ $request->service_city }} · posted {{ $request->created_at->translatedFormat('j M Y') }}</small></td>
                        <td class="small">{{ $request->homeowner->user->fullName() }}</td>
                        <td class="small"><i class="bi {{ $request->service->category->icon() }} text-primary me-1"></i>{{ $request->service->name }}</td>
                        <td class="small text-nowrap">{{ $request->desired_start_date->translatedFormat('j M Y') }}</td>
                        <td class="fw-bold">{{ $request->offers_count }}</td>
                        <td class="small">@if ($request->acceptedOffer){{ $request->acceptedOffer->handyman->user->fullName() }}<span class="text-secondary d-block">{{ $request->acceptedOffer->formattedPrice() }}</span>@else<span class="text-secondary">—</span>@endif</td>
                        <td><x-status-badge :status="$request->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-secondary py-4">No request in this state.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($requests->hasPages())<div class="card-footer">{{ $requests->links() }}</div>@endif
</div>
@endsection
