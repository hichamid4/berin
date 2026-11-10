@extends('layouts.admin')

@section('title', 'Reviews')
@section('page_title', 'Reviews')
@section('page_subtitle', 'Ratings left by homeowners after confirming a completed job.')
@section('breadcrumbs')<li class="breadcrumb-item active" aria-current="page">Reviews</li>@endsection

@section('content')
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card h-100"><div class="card-body text-center">
            <div class="display-5 fw-bold">{{ $averageRating ? number_format($averageRating, 1) : '–' }}</div>
            <x-rating :value="$averageRating" :show-value="false" />
            <div class="small text-secondary mt-1">{{ $reviews->total() }} {{ Str::plural('review', $reviews->total()) }} overall</div>
        </div></div>
    </div>
    <div class="col-md-8">
        <div class="card h-100"><div class="card-body">
            @foreach ([5, 4, 3, 2, 1] as $stars)
                @php($count = $ratingBreakdown->get($stars, 0))
                <div class="d-flex align-items-center gap-2 small mb-2">
                    <span class="text-muted-strong" style="width:3rem">{{ $stars }} <i class="bi bi-star-fill text-accent"></i></span>
                    <div class="progress flex-grow-1 progress-thin" role="progressbar" aria-valuenow="{{ $count }}" aria-valuemin="0" aria-valuemax="{{ max(1, $reviews->total()) }}"><div class="progress-bar bg-primary" style="width: {{ $reviews->total() ? round($count / $reviews->total() * 100) : 0 }}%"></div></div>
                    <span class="text-secondary" style="width:1.5rem">{{ $count }}</span>
                </div>
            @endforeach
        </div></div>
    </div>
</div>
<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Rating</th><th>Comment</th><th>Handyman</th><th>Homeowner</th><th>Service</th><th>Date</th></tr></thead>
            <tbody>
                @forelse ($reviews as $review)
                    <tr>
                        <td><x-rating :value="$review->rating" :show-value="false" /></td>
                        <td class="small" style="max-width:24rem">{{ $review->comment ?? '—' }}</td>
                        <td class="small fw-bold">{{ $review->offer->handyman->user->fullName() }}</td>
                        <td class="small">{{ $review->offer->request->homeowner->user->fullName() }}</td>
                        <td class="small">{{ $review->offer->request->service->name }}</td>
                        <td class="small text-nowrap">{{ $review->created_at->translatedFormat('j M Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-secondary py-4">No review yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($reviews->hasPages())<div class="card-footer">{{ $reviews->links() }}</div>@endif
</div>
@endsection
