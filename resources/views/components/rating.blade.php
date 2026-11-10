@props(['value' => null, 'count' => null, 'showValue' => true])
@php
    $rating = (float) ($value ?? 0);
    $rounded = round($rating * 2) / 2;
@endphp
<span {{ $attributes->merge(['class' => 'd-inline-flex align-items-center gap-2']) }}>
    <span class="rating" aria-label="{{ $rating > 0 ? number_format($rating, 1).' out of 5' : 'No rating yet' }}">
        @for ($star = 1; $star <= 5; $star++)
            @if ($rounded >= $star)
                <i class="bi bi-star-fill"></i>
            @elseif ($rounded >= $star - 0.5)
                <i class="bi bi-star-half"></i>
            @else
                <i class="bi bi-star"></i>
            @endif
        @endfor
    </span>
    @if ($showValue)
        <small class="text-muted-strong">
            @if ($rating > 0)
                {{ number_format($rating, 1) }}@if ($count !== null) <span class="fw-normal">({{ $count }})</span>@endif
            @else
                New
            @endif
        </small>
    @endif
</span>
