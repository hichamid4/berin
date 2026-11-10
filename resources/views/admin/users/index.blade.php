@extends('layouts.admin')

@section('title', 'Users')
@section('page_title', 'Users')
@section('page_subtitle', 'Homeowners, handymen and administrators registered on the platform.')
@section('breadcrumbs')<li class="breadcrumb-item active" aria-current="page">Users</li>@endsection

@section('content')
<form class="card mb-3" method="get" action="{{ route('admin.users.index') }}">
    <div class="card-body">
        <div class="row g-2 align-items-center">
            <div class="col-md-5"><input class="form-control" type="search" name="q" value="{{ $search }}" placeholder="Search by name, username or email"></div>
            <div class="col-md-3">
                <select class="form-select" name="role">
                    <option value="">All roles</option>
                    @foreach (\App\UserRole::cases() as $option)<option value="{{ $option->value }}" @selected($role === $option)>{{ ucfirst($option->name) }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="status">
                    <option value="">All statuses</option>
                    @foreach (['active', 'blocked', 'anonymized'] as $option)<option value="{{ $option }}" @selected($status === $option)>{{ ucfirst($option) }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2 d-grid"><button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button></div>
        </div>
    </div>
</form>

<div class="card table-card">
    <div class="card-header d-flex justify-content-between align-items-center"><h3 class="card-title mb-0">{{ $users->total() }} {{ Str::plural('user', $users->total()) }}</h3><span class="small text-secondary">Block/unblock actions arrive with the user management module</span></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>User</th><th>Role</th><th>City</th><th>Email</th><th>Verified</th><th>Joined</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($users as $member)
                    <tr>
                        <td><div class="d-flex align-items-center gap-2"><x-avatar :user="$member" size="avatar-sm" /><div><strong class="d-block">{{ $member->fullName() }}</strong><small class="text-secondary">{{ $member->username ? '@'.$member->username : 'anonymized' }}</small></div></div></td>
                        <td class="small text-capitalize">{{ $member->role->value }}</td>
                        <td class="small">{{ $member->handymanProfile?->city ?? $member->homeownerProfile?->city ?? '—' }}</td>
                        <td class="small">{{ $member->email ?? '—' }}</td>
                        <td>@if ($member->email_verified_at)<i class="bi bi-patch-check-fill text-success"></i>@else<i class="bi bi-dash-circle text-secondary"></i>@endif</td>
                        <td class="small text-nowrap">{{ $member->created_at->translatedFormat('j M Y') }}</td>
                        <td><span class="badge {{ match($member->status) { 'active' => 'text-bg-success', 'blocked' => 'text-bg-danger', default => 'text-bg-secondary' } }}">{{ ucfirst($member->status) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-secondary py-4">No user matches these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($users->hasPages())<div class="card-footer">{{ $users->links() }}</div>@endif
</div>
@endsection
