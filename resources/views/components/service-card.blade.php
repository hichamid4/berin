@props(['service'])
<article {{ $attributes->merge(['class' => 'card card-hover service-card h-100']) }}>
    <div class="card-body d-flex flex-column">
        <div class="d-flex justify-content-between align-items-start gap-2">
            <span class="service-category"><i class="bi {{ $service->category->icon() }} me-1"></i>{{ $service->category->name }}</span>
            @if ($service->requests_count ?? false)
                <span class="chip chip-muted"><i class="bi bi-clipboard-check"></i>{{ $service->requests_count }} {{ Str::plural('request', $service->requests_count) }}</span>
            @endif
        </div>
        <h3 class="card-title stretched-title"><a href="{{ route('services.show', $service) }}" class="stretched-link">{{ $service->name }}</a></h3>
        <p class="card-text">{{ $service->description }}</p>
        <div class="d-flex flex-wrap gap-1 mt-auto pt-3">
            @foreach ($service->skills->take(3) as $skill)
                <span class="chip">{{ $skill->name }}</span>
            @endforeach
            @if ($service->skills->count() > 3)
                <span class="chip chip-muted">+{{ $service->skills->count() - 3 }}</span>
            @endif
        </div>
    </div>
</article>
