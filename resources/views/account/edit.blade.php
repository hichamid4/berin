@extends('layouts.dashboard')

@section('title', 'Account settings')
@section('heading', 'Account settings')
@section('subtitle', 'Manage your profile, password'.($user->isHandyman() ? ', skills' : '').' and account.')

@section('dashboard')
    @include('account.partials.profile-form')

    @if ($user->isHandyman())
        @php($selected = array_map('intval', old('skill_ids', $user->handymanProfile->skills->pluck('id')->all())))
        <form class="card mb-4" method="post" action="{{ route('account.skills.update') }}" id="skills">
            @csrf @method('PUT')
            <div class="card-body p-4">
                <h2 class="h5 mb-1">My skills</h2>
                <p class="text-secondary small mb-3">Select the skills you actually have. You will see open requests for every service that requires at least one of them.</p>
                @error('skill_ids')<div class="alert alert-danger py-2 small">{{ $message }}</div>@enderror
                <div class="row g-2">
                    @foreach ($skillCatalog as $skill)
                        <div class="col-md-6">
                            <label class="d-flex gap-2 border rounded-3 p-3 h-100 {{ in_array($skill->id, $selected, true) ? 'border-primary bg-primary-soft' : '' }}">
                                <input class="form-check-input mt-1 flex-shrink-0" type="checkbox" name="skill_ids[]" value="{{ $skill->id }}" @checked(in_array($skill->id, $selected, true))>
                                <span>
                                    <strong class="d-block">{{ $skill->name }} @unless ($skill->is_active)<span class="badge text-bg-secondary">retired</span>@endunless</strong>
                                    <small class="text-secondary d-block">{{ $skill->description }}</small>
                                    @if ($skill->services->isNotEmpty())
                                        <small class="d-block mt-1 text-primary-emphasis"><i class="bi bi-briefcase me-1"></i>{{ $skill->services->pluck('name')->take(3)->join(', ') }}{{ $skill->services->count() > 3 ? ' +'.($skill->services->count() - 3) : '' }}</small>
                                    @endif
                                </span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="card-footer bg-transparent d-flex justify-content-end"><button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1"></i>Save skills</button></div>
        </form>
    @endif

    <div class="card border-danger-subtle" id="close-account">
        <div class="card-body p-4">
            <h2 class="h5 text-danger-emphasis mb-1">Close my account</h2>
            <p class="small text-secondary">Closing your account permanently removes your name, username, email, phone and addresses. Completed jobs and reviews stay in the marketplace history as “Former member”.
                @if ($user->isHomeowner()) Your open requests are cancelled and favorites removed. @else Your pending offers are withdrawn. @endif
            </p>
            @if ($closureBlocker)
                <div class="alert alert-warning small mb-0"><i class="bi bi-shield-exclamation me-1"></i><strong>You cannot close your account yet.</strong> {{ $closureBlocker }}</div>
            @else
                <form method="post" action="{{ route('account.destroy') }}" class="row g-2 align-items-end" data-confirm="This cannot be undone. Your personal data will be erased and you will be signed out." data-confirm-title="Close your BreIn account?" data-confirm-button="Close my account" data-confirm-variant="danger">
                    @csrf @method('DELETE')
                    <div class="col-sm-5"><label class="form-label" for="close_password">Current password</label><input class="form-control @error('close_password') is-invalid @enderror" id="close_password" name="close_password" type="password" required autocomplete="current-password">@error('close_password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-sm-4"><label class="form-label" for="confirmation">Type CLOSE</label><input class="form-control @error('confirmation') is-invalid @enderror" id="confirmation" name="confirmation" autocomplete="off" required>@error('confirmation')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-sm-3 d-grid"><button class="btn btn-danger" type="submit">Close account</button></div>
                </form>
            @endif
        </div>
    </div>
@endsection
