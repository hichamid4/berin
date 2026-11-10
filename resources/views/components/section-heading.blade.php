@props(['eyebrow' => null, 'title', 'lead' => null, 'align' => 'start'])
<div {{ $attributes->merge(['class' => 'mb-4 mb-lg-5 text-'.$align]) }}>
    @if ($eyebrow)<span class="section-eyebrow">{{ $eyebrow }}</span>@endif
    <h2 class="section-title">{{ $title }}</h2>
    @if ($lead)<p class="section-lead {{ $align === 'center' ? 'mx-auto' : '' }}">{{ $lead }}</p>@endif
    {{ $slot }}
</div>
