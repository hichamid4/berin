@extends('layouts.admin')

@use('App\ContactMessageStatus')
@section('title', $contactMessage->subject)
@section('page_title', 'Contact message')
@section('page_subtitle', 'Received '.$contactMessage->created_at->translatedFormat('l j F Y \a\t H:i'))
@section('breadcrumbs')<li class="breadcrumb-item"><a href="{{ route('admin.contact-messages.index') }}">Contact messages</a></li><li class="breadcrumb-item active" aria-current="page">Details</li>@endsection

@section('content')
<div class="row g-3">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center gap-2">
                <h3 class="card-title mb-0 text-break">{{ $contactMessage->subject }}</h3>
                <x-status-badge :status="$contactMessage->status" />
            </div>
            <div class="card-body">
                <p class="mb-0 text-break" style="white-space: pre-wrap">{{ $contactMessage->message }}</p>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title mb-0">Sender</h3></div>
            <ul class="list-group list-group-flush small">
                <li class="list-group-item d-flex justify-content-between gap-3"><span class="text-secondary">Name</span><span class="text-end text-break">{{ $contactMessage->name }}</span></li>
                <li class="list-group-item d-flex justify-content-between gap-3"><span class="text-secondary">Email</span><span class="text-end text-break">{{ $contactMessage->email }}</span></li>
                <li class="list-group-item d-flex justify-content-between gap-3"><span class="text-secondary">Received</span><span>{{ $contactMessage->created_at->translatedFormat('j M Y H:i') }}</span></li>
                <li class="list-group-item d-flex justify-content-between gap-3"><span class="text-secondary">Read</span><span>{{ $contactMessage->read_at?->translatedFormat('j M Y H:i') ?? '—' }}</span></li>
                <li class="list-group-item d-flex justify-content-between gap-3"><span class="text-secondary">Resolved</span><span>{{ $contactMessage->resolved_at?->translatedFormat('j M Y H:i') ?? '—' }}</span></li>
                @if ($contactMessage->handledBy)
                    <li class="list-group-item d-flex justify-content-between gap-3"><span class="text-secondary">Last updated by</span><span class="text-end">{{ $contactMessage->handledBy->fullName() }}</span></li>
                @endif
            </ul>
            <div class="card-body pt-0 mt-3">
                <a class="btn btn-outline-primary w-100" href="mailto:{{ $contactMessage->email }}?subject={{ rawurlencode('Re: '.$contactMessage->subject) }}"><i class="bi bi-reply me-1"></i>Reply from your email app</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">Handling</h3></div>
            <div class="card-body d-grid gap-2">
                @if ($contactMessage->status === ContactMessageStatus::New)
                    <form method="post" action="{{ route('admin.contact-messages.status', $contactMessage) }}">@csrf @method('PATCH')
                        <input type="hidden" name="status" value="{{ ContactMessageStatus::Read->value }}">
                        <button class="btn btn-outline-info w-100" type="submit"><i class="bi bi-envelope-open me-1"></i>Mark as read</button>
                    </form>
                @endif
                @if ($contactMessage->status !== ContactMessageStatus::Resolved)
                    <form method="post" action="{{ route('admin.contact-messages.status', $contactMessage) }}">@csrf @method('PATCH')
                        <input type="hidden" name="status" value="{{ ContactMessageStatus::Resolved->value }}">
                        <button class="btn btn-success w-100" type="submit"><i class="bi bi-check2-circle me-1"></i>Mark as resolved</button>
                    </form>
                @else
                    <form method="post" action="{{ route('admin.contact-messages.status', $contactMessage) }}">@csrf @method('PATCH')
                        <input type="hidden" name="status" value="{{ ContactMessageStatus::Read->value }}">
                        <button class="btn btn-outline-secondary w-100" type="submit"><i class="bi bi-arrow-counterclockwise me-1"></i>Reopen</button>
                    </form>
                @endif
                @if ($contactMessage->status === ContactMessageStatus::Read)
                    <form method="post" action="{{ route('admin.contact-messages.status', $contactMessage) }}">@csrf @method('PATCH')
                        <input type="hidden" name="status" value="{{ ContactMessageStatus::New->value }}">
                        <button class="btn btn-outline-secondary w-100" type="submit"><i class="bi bi-envelope me-1"></i>Mark as unread</button>
                    </form>
                @endif
                <p class="small text-secondary mb-0">Replies are sent from your own email app; BreIn does not send emails for contact messages.</p>
            </div>
        </div>
    </div>
</div>
@endsection
