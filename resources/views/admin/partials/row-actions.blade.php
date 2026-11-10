{{-- Edit / activate-deactivate / dependency-checked delete actions for a catalog row. --}}
<div class="d-flex justify-content-end gap-1 flex-nowrap">
    <a class="btn btn-sm btn-outline-secondary" href="{{ $editUrl }}" title="Edit"><i class="bi bi-pencil"></i><span class="visually-hidden">Edit {{ $name }}</span></a>
    <form method="post" action="{{ $toggleUrl }}">@csrf @method('PATCH')
        <button class="btn btn-sm {{ $isActive ? 'btn-outline-warning' : 'btn-outline-success' }}" type="submit" title="{{ $isActive ? 'Deactivate' : 'Activate' }}">
            <i class="bi {{ $isActive ? 'bi-eye-slash' : 'bi-eye' }}"></i><span class="visually-hidden">{{ $isActive ? 'Deactivate' : 'Activate' }} {{ $name }}</span>
        </button>
    </form>
    <a class="btn btn-sm btn-outline-danger" href="{{ $deleteUrl }}" data-dependency-url="{{ $deleteUrl }}" title="Delete"><i class="bi bi-trash3"></i><span class="visually-hidden">Delete {{ $name }}</span></a>
</div>
