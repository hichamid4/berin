@props(['icon' => 'bi-inbox', 'title', 'text' => null])
<div {{ $attributes->merge(['class' => 'empty-state']) }}>
    <i class="bi {{ $icon }} d-block mb-2"></i>
    <p class="fw-bold mb-1 text-body">{{ $title }}</p>
    @if ($text)<p class="small mb-0">{{ $text }}</p>@endif
    {{ $slot }}
</div>
