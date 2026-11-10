@extends('layouts.admin')

@section('title', 'Contact messages')
@section('page_title', 'Contact messages')
@section('page_subtitle', 'Messages sent by visitors through the public Contact Us form.')
@section('breadcrumbs')<li class="breadcrumb-item active" aria-current="page">Contact messages</li>@endsection

@section('content')
<ul class="nav nav-pills gap-1 mb-3" aria-label="Filter by status">
    <li class="nav-item">
        <a class="nav-link {{ $status === null ? 'active' : '' }}" href="{{ route('admin.contact-messages.index', array_filter(['q' => $search])) }}">All <span class="badge text-bg-light border ms-1">{{ $statusCounts->sum() }}</span></a>
    </li>
    @foreach (\App\ContactMessageStatus::cases() as $option)
        <li class="nav-item">
            <a class="nav-link {{ $status === $option ? 'active' : '' }}" href="{{ route('admin.contact-messages.index', array_filter(['status' => $option->value, 'q' => $search])) }}">{{ $option->label() }} <span class="badge text-bg-light border ms-1">{{ $statusCounts->get($option->value, 0) }}</span></a>
        </li>
    @endforeach
</ul>

<form class="card mb-3" method="get" action="{{ route('admin.contact-messages.index') }}">
    <div class="card-body">
        <div class="row g-2 align-items-center">
            <div class="col-md-7"><label class="visually-hidden" for="q">Search messages</label><input class="form-control" id="q" type="search" name="q" value="{{ $search }}" placeholder="Search by name, email, subject or message"></div>
            <div class="col-md-3">
                <label class="visually-hidden" for="status">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All statuses</option>
                    @foreach (\App\ContactMessageStatus::cases() as $option)<option value="{{ $option->value }}" @selected($status === $option)>{{ $option->label() }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2 d-grid"><button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button></div>
        </div>
    </div>
</form>

<div class="card table-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">{{ $messages->total() }} {{ Str::plural('message', $messages->total()) }}</h3>
        @if ($search !== '' || $status)<a class="small" href="{{ route('admin.contact-messages.index') }}">Clear filters</a>@endif
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Status</th><th>From</th><th>Subject</th><th>Received</th><th></th></tr></thead>
            <tbody>
                @forelse ($messages as $contactMessage)
                    <tr @class(['fw-bold' => $contactMessage->status === \App\ContactMessageStatus::New])>
                        <td><x-status-badge :status="$contactMessage->status" /></td>
                        <td class="small"><span class="d-block">{{ $contactMessage->name }}</span><span class="text-secondary fw-normal">{{ $contactMessage->email }}</span></td>
                        <td class="small" style="max-width:28rem"><span class="d-block text-truncate">{{ $contactMessage->subject }}</span><span class="d-block text-secondary fw-normal text-truncate">{{ Str::limit($contactMessage->message, 110) }}</span></td>
                        <td class="small text-nowrap fw-normal" title="{{ $contactMessage->created_at->translatedFormat('j F Y H:i') }}">{{ $contactMessage->created_at->translatedFormat('j M Y') }}<span class="d-block text-secondary">{{ $contactMessage->created_at->translatedFormat('H:i') }}</span></td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.contact-messages.show', $contactMessage) }}">Open<span class="visually-hidden"> message from {{ $contactMessage->name }}</span></a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-secondary py-4">{{ $search !== '' || $status ? 'No message matches these filters.' : 'No contact message has been received yet.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($messages->hasPages())<div class="card-footer">{{ $messages->links() }}</div>@endif
</div>
@endsection
