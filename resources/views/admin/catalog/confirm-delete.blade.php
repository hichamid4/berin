@extends('layouts.admin')

@section('title', 'Delete '.$report->subjectType)
@section('page_title', 'Delete '.$report->subjectType)
@section('breadcrumbs')<li class="breadcrumb-item"><a href="{{ $backUrl }}">{{ Str::plural(ucfirst($report->subjectType)) }}</a></li><li class="breadcrumb-item active" aria-current="page">Delete</li>@endsection

@section('content')
<div class="card" style="max-width:48rem"><div class="card-body p-4">
@fragment('report')
<div class="dependency-report">
    <div class="d-flex align-items-start gap-3 mb-3">
        <span class="icon-tile {{ $report->canDelete() ? 'icon-tone-2' : 'icon-tone-6' }} d-grid rounded-3 flex-shrink-0" style="width:3rem;height:3rem;place-items:center;font-size:1.4rem">
            <i class="bi {{ $report->canDelete() ? 'bi-trash3' : 'bi-shield-lock' }}"></i>
        </span>
        <div>
            <h3 class="h5 mb-1">{{ ucfirst($report->subjectType) }} “{{ $report->subjectName }}”</h3>
            @if ($report->canDelete())
                <p class="mb-0 text-secondary">The server checked every relationship. This {{ $report->subjectType }} can be deleted safely.</p>
            @else
                <p class="mb-0 text-danger-emphasis">This {{ $report->subjectType }} cannot be physically deleted. The dependencies below were found on the server.</p>
            @endif
        </div>
    </div>

    @foreach ($report->blockers as $blocker)
        <div class="border border-danger-subtle bg-danger-subtle rounded-3 p-3 mb-2">
            <div class="fw-bold text-danger-emphasis"><i class="bi bi-x-octagon me-1"></i>{{ $blocker['title'] }}</div>
            <div class="small mb-2">{{ $blocker['detail'] }}</div>
            <div class="d-flex flex-wrap gap-1">
                @foreach ($blocker['items'] as $item)<span class="badge text-bg-light border fw-semibold">{{ $item }}</span>@endforeach
                @if ($blocker['total'] > count($blocker['items']) && count($blocker['items']) === 10)<span class="badge text-bg-secondary">+{{ $blocker['total'] - 10 }} more</span>@endif
            </div>
        </div>
    @endforeach

    @foreach ($report->effects as $effect)
        <div class="border rounded-3 p-3 mb-2 {{ $report->canDelete() ? 'bg-warning-subtle border-warning-subtle' : 'bg-body-tertiary' }}">
            <div class="fw-bold"><i class="bi bi-info-circle me-1"></i>{{ $report->canDelete() ? 'On deletion: ' : 'If it were deleted: ' }}{{ lcfirst($effect['title']) }}</div>
            <div class="small mb-2">{{ $effect['detail'] }}</div>
            <div class="d-flex flex-wrap gap-1">@foreach ($effect['items'] as $item)<span class="badge text-bg-light border fw-semibold">{{ $item }}</span>@endforeach</div>
        </div>
    @endforeach

    @if ($report->canDelete() && $report->effects === [])
        <div class="border rounded-3 p-3 mb-2 bg-body-tertiary small"><i class="bi bi-check2-circle text-success me-1"></i>No services, handymen or marketplace records depend on it.</div>
    @endif

    @if (! $report->canDelete())
        <div class="alert alert-info small mt-3 mb-0">
            <i class="bi bi-lightbulb me-1"></i>
            @if ($report->isActive)
                <strong>Recommended:</strong> deactivate it. It disappears from the catalog and from new selections, while every existing request, offer and review stays intact.
            @else
                It is already inactive, so it is hidden from the catalog and new selections. Its history is preserved.
            @endif
        </div>
    @endif

    <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
        <a class="btn btn-outline-secondary" href="{{ $backUrl }}" data-bs-dismiss="modal">Cancel</a>
        <form method="post" action="{{ $toggleUrl }}">@csrf @method('PATCH')
            <button class="btn {{ $report->isActive ? 'btn-warning' : 'btn-success' }}" type="submit">
                <i class="bi {{ $report->isActive ? 'bi-eye-slash' : 'bi-eye' }} me-1"></i>{{ $report->isActive ? 'Deactivate instead' : 'Reactivate' }}
            </button>
        </form>
        @if ($report->canDelete())
            <form method="post" action="{{ $destroyUrl }}">@csrf @method('DELETE')
                <button class="btn btn-danger" type="submit"><i class="bi bi-trash3 me-1"></i>Delete permanently</button>
            </form>
        @endif
    </div>
</div>
@endfragment
</div></div>
@endsection
