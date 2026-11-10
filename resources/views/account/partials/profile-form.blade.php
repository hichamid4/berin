{{-- Shared profile and password forms for every role. Expects $user and optional $profile. --}}
<form class="card mb-4" method="post" action="{{ route('account.profile.update') }}" enctype="multipart/form-data">
    @csrf @method('PUT')
    <div class="card-body p-4">
        <h2 class="h5 mb-1">Profile</h2>
        <p class="text-secondary small mb-4">Changing your email address requires verifying the new address.</p>
        <div class="row g-3">
            <div class="col-sm-6"><label class="form-label" for="first_name">First name</label><input class="form-control @error('first_name') is-invalid @enderror" id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" required autocomplete="given-name">@error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-sm-6"><label class="form-label" for="last_name">Last name</label><input class="form-control @error('last_name') is-invalid @enderror" id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" required autocomplete="family-name">@error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-sm-6"><label class="form-label" for="username">Username</label><input class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username', $user->username) }}" required autocomplete="username">@error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-sm-6"><label class="form-label" for="email">Email address</label><input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="email">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-sm-6"><label class="form-label" for="phone_e164">Phone {{ $user->isHandyman() ? '(WhatsApp)' : '' }}</label><input class="form-control @error('phone_e164') is-invalid @enderror" id="phone_e164" name="phone_e164" type="tel" value="{{ old('phone_e164', $user->phone_e164) }}" placeholder="+212612345678" autocomplete="tel">@error('phone_e164')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @if ($user->isHandyman())<div class="form-text">Homeowners who accept your offer contact you on WhatsApp with this number.</div>@endif
            </div>
            @if ($profile ?? null)
                <div class="col-sm-6"><label class="form-label" for="city">City</label><input class="form-control @error('city') is-invalid @enderror" id="city" name="city" value="{{ old('city', $profile->city) }}" autocomplete="address-level2">@error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12"><label class="form-label" for="address">Profile address</label><input class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address', $profile->address) }}" autocomplete="street-address">@error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror<div class="form-text">Your profile address is never shown publicly. Each request has its own private service address.</div></div>
            @endif
            @if ($user->isHandyman())
                <div class="col-12">
                    <label class="form-label" for="photo">Profile photo <span class="text-secondary fw-normal">(optional, JPG/PNG/WebP, max 2 MB)</span></label>
                    <div class="d-flex align-items-center gap-3">
                        <x-avatar :user="$user" size="avatar-lg" :photo-url="$profile?->photo_path ? route('handymen.photo', $profile) : null" />
                        <input class="form-control @error('photo') is-invalid @enderror" id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp">
                    </div>
                    @error('photo')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    @if ($profile?->photo_path)
                        <div class="form-check mt-2"><input class="form-check-input" type="checkbox" id="remove_photo" name="remove_photo" value="1"><label class="form-check-label small" for="remove_photo">Remove my current photo</label></div>
                    @endif
                </div>
            @endif
        </div>
    </div>
    <div class="card-footer bg-transparent d-flex justify-content-end"><button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1"></i>Save profile</button></div>
</form>

<form class="card mb-4" method="post" action="{{ route('account.password.update') }}">
    @csrf @method('PUT')
    <div class="card-body p-4">
        <h2 class="h5 mb-1">Password</h2>
        <p class="text-secondary small mb-4">Use at least 12 characters. Other devices are signed out after the change.</p>
        <div class="row g-3">
            <div class="col-sm-4"><label class="form-label" for="current_password">Current password</label><input class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" type="password" required autocomplete="current-password">@error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-sm-4"><label class="form-label" for="password">New password</label><input class="form-control @error('password') is-invalid @enderror" id="password" name="password" type="password" minlength="12" required autocomplete="new-password">@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-sm-4"><label class="form-label" for="password_confirmation">Confirm new password</label><input class="form-control" id="password_confirmation" name="password_confirmation" type="password" minlength="12" required autocomplete="new-password"></div>
        </div>
    </div>
    <div class="card-footer bg-transparent d-flex justify-content-end"><button class="btn btn-primary" type="submit"><i class="bi bi-key me-1"></i>Change password</button></div>
</form>
