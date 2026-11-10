@extends('layouts.admin')

@section('title', 'Security')
@section('page_title', 'Security')
@section('page_subtitle', 'Two-factor authentication for your administrator account.')
@section('breadcrumbs')<li class="breadcrumb-item active" aria-current="page">Security</li>@endsection

@section('content')
<div class="row g-3">
    @if ($recoveryCodes)
        <div class="col-12">
            <div class="card border-warning">
                <div class="card-header bg-warning-subtle"><h3 class="card-title mb-0"><i class="bi bi-key me-1"></i>Your recovery codes — save them now</h3></div>
                <div class="card-body">
                    <p class="mb-3">These codes are shown <strong>only once</strong>. Each one signs you in a single time if you lose access to your authenticator app. Store them in a password manager or print them and keep them somewhere safe.</p>
                    <div class="row g-2 mb-1" data-recovery-codes>
                        @foreach ($recoveryCodes as $code)
                            <div class="col-6 col-md-3"><code class="d-block text-center fs-6 border rounded py-2 user-select-all">{{ $code }}</code></div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0">Authenticator app</h3></div>
            <ul class="list-group list-group-flush small">
                <li class="list-group-item d-flex justify-content-between"><span class="text-secondary">Status</span><span class="badge text-bg-success">Active</span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-secondary">Enabled</span><span>{{ $credential->confirmed_at->translatedFormat('j M Y H:i') }}</span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-secondary">Recovery codes left</span><span @class(['fw-bold', 'text-danger' => $credential->remainingRecoveryCodes() <= 2])>{{ $credential->remainingRecoveryCodes() }}</span></li>
            </ul>
            <div class="card-body small text-secondary">Two-factor authentication is mandatory for administrators and cannot be switched off here. To move to a new phone, use the reset command from a trusted terminal (see the README) and enrol again at the next sign-in.</div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0">Generate new recovery codes</h3></div>
            <div class="card-body">
                <p class="small text-secondary">New codes replace all existing ones immediately.</p>
                <form method="post" action="{{ route('admin.security.recovery-codes') }}">
                    @csrf
                    <label class="form-label" for="password">Confirm your password</label>
                    <input class="form-control mb-3 @error('password') is-invalid @enderror" id="password" name="password" type="password" required autocomplete="current-password">
                    <button class="btn btn-outline-primary" type="submit"><i class="bi bi-arrow-repeat me-1"></i>Generate new codes</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
