@props(['request', 'showStatus' => true])
<article {{ $attributes->merge(['class' => 'card card-hover request-card h-100']) }}>
    <div class="card-body d-flex flex-column">
        <div class="d-flex justify-content-between align-items-start gap-2">
            <span class="service-category text-primary small fw-bold text-uppercase"><i class="bi {{ $request->service->category->icon() }} me-1"></i>{{ $request->service->name }}</span>
            @if ($showStatus)<x-status-badge :status="$request->status" />@endif
        </div>
        <h3 class="request-title">{{ $request->title }}</h3>
        <p class="request-excerpt mb-3">{{ $request->description }}</p>
        <div class="card-meta mt-auto pt-3 border-top">
            <span><i class="bi bi-geo-alt-fill"></i> {{ $request->service_city }}</span>
            <span><i class="bi bi-calendar-event"></i> {{ $request->desired_start_date->translatedFormat('j M Y') }}</span>
            @isset($request->offers_count)
                <span><i class="bi bi-chat-square-text"></i> {{ $request->offers_count }} {{ Str::plural('offer', $request->offers_count) }}</span>
            @endisset
        </div>
        {{ $slot }}
    </div>
</article>
