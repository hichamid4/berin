@extends('layouts.admin')

@section('title', 'Skills')
@section('page_title', 'Skills')
@section('page_subtitle', 'Skills connect handymen to services. A skill can serve many services, and a handyman can hold many skills.')
@section('breadcrumbs')<li class="breadcrumb-item active" aria-current="page">Skills</li>@endsection

@section('content')
<div class="d-flex justify-content-end mb-3"><a class="btn btn-primary" href="{{ route('admin.skills.create') }}"><i class="bi bi-plus-lg me-1"></i>New skill</a></div>
<div class="card table-card">
    <div class="card-header"><h3 class="card-title mb-0">{{ $skills->count() }} {{ Str::plural('skill', $skills->count()) }}</h3></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Skill</th><th>Used by services</th><th>Handymen</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @forelse ($skills as $skill)
                    <tr class="{{ $skill->is_active ? '' : 'opacity-75' }}">
                        <td><strong class="d-block">{{ $skill->name }}</strong><small class="text-secondary">{{ $skill->description }}</small></td>
                        <td><div class="d-flex flex-wrap gap-1">@forelse ($skill->services->take(3) as $service)<span class="chip">{{ $service->name }}</span>@empty<span class="text-secondary small">—</span>@endforelse @if ($skill->services_count > 3)<span class="chip chip-muted">+{{ $skill->services_count - 3 }}</span>@endif</div></td>
                        <td class="fw-bold">{{ $skill->handymen_count }}</td>
                        <td><span class="badge {{ $skill->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $skill->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td>@include('admin.partials.row-actions', [
                            'name' => $skill->name,
                            'isActive' => $skill->is_active,
                            'editUrl' => route('admin.skills.edit', $skill),
                            'toggleUrl' => route('admin.skills.toggle', $skill),
                            'deleteUrl' => route('admin.skills.delete', $skill),
                        ])</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-secondary py-4">No skill yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
