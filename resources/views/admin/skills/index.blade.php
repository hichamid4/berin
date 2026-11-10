@extends('layouts.admin')

@section('title', 'Skills')
@section('page_title', 'Skills')
@section('page_subtitle', 'Skills link handymen to the services they can offer.')
@section('breadcrumbs')<li class="breadcrumb-item active" aria-current="page">Skills</li>@endsection

@section('content')
<div class="card table-card">
    <div class="card-header"><h3 class="card-title mb-0">{{ $skills->count() }} {{ Str::plural('skill', $skills->count()) }}</h3></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Skill</th><th>Description</th><th>Services</th><th>Handymen</th><th>Status</th></tr></thead>
            <tbody>
                @foreach ($skills as $skill)
                    <tr>
                        <td><strong>{{ $skill->name }}</strong></td>
                        <td class="small text-secondary">{{ $skill->description }}</td>
                        <td class="fw-bold">{{ $skill->services_count }}</td>
                        <td class="fw-bold">{{ $skill->handymen_count }}</td>
                        <td><span class="badge {{ $skill->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $skill->is_active ? 'Active' : 'Inactive' }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
