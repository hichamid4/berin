@props(['handyman'])
<article {{ $attributes->merge(['class' => 'card card-hover handyman-card h-100']) }}>
    <div class="card-body d-flex flex-column">
        <div class="d-flex align-items-center gap-3 mb-3">
            <x-avatar :user="$handyman->user" size="avatar-lg" :photo-url="$handyman->photo_path ? route('handymen.photo', $handyman) : null" />
            <div class="min-w-0">
                <h3 class="h5 mb-0 stretched-title text-truncate"><a href="{{ route('handymen.show', $handyman) }}" class="stretched-link">{{ $handyman->user->fullName() }}</a></h3>
                <div class="card-meta mt-1">
                    @if ($handyman->city)<span><i class="bi bi-geo-alt-fill"></i> {{ $handyman->city }}</span>@endif
                </div>
            </div>
        </div>
        <x-rating :value="$handyman->reviews_avg_rating" :count="$handyman->reviews_count ?? null" class="mb-3" />
        <div class="skills mb-3">
            @foreach ($handyman->skills->take(3) as $skill)
                <span class="chip">{{ $skill->name }}</span>
            @endforeach
            @if ($handyman->skills->count() > 3)
                <span class="chip chip-muted">+{{ $handyman->skills->count() - 3 }}</span>
            @endif
        </div>
        <div class="card-meta mt-auto pt-3 border-top">
            @isset($handyman->completed_jobs_count)
                <span><i class="bi bi-check2-circle"></i> {{ $handyman->completed_jobs_count }} {{ Str::plural('job', $handyman->completed_jobs_count) }} completed</span>
            @endisset
            @isset($handyman->favorited_by_count)
                <span><i class="bi bi-heart-fill"></i> {{ $handyman->favorited_by_count }} {{ Str::plural('favorite', $handyman->favorited_by_count) }}</span>
            @endisset
        </div>
    </div>
</article>
