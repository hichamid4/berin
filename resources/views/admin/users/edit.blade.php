@extends('layouts.admin')

@php($profile = $member->homeownerProfile ?? $member->handymanProfile)
@section('title', 'Edit '.$member->fullName())
@section('page_title', 'Edit '.$member->fullName())
@section('page_subtitle', 'Email and password stay under the member\'s control; they can change them from their account page.')
@section('breadcrumbs')<li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li><li class="breadcrumb-item"><a href="{{ route('admin.users.show', $member) }}">Details</a></li><li class="breadcrumb-item active" aria-current="page">Edit</li>@endsection

@section('content')
<form class="card" style="max-width:44rem" method="post" action="{{ route('admin.users.update', $member) }}">
    @csrf @method('PUT')
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-sm-6"><label class="form-label" for="first_name">First name</label><input class="form-control @error('first_name') is-invalid @enderror" id="first_name" name="first_name" value="{{ old('first_name', $member->first_name) }}" required>@error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-sm-6"><label class="form-label" for="last_name">Last name</label><input class="form-control @error('last_name') is-invalid @enderror" id="last_name" name="last_name" value="{{ old('last_name', $member->last_name) }}" required>@error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-sm-6"><label class="form-label" for="username">Username</label><input class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username', $member->username) }}" required>@error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-sm-6"><label class="form-label" for="phone_e164">Phone {{ $member->isAdministrator() ? '(optional)' : '(WhatsApp)' }}</label><input class="form-control @error('phone_e164') is-invalid @enderror" id="phone_e164" name="phone_e164" type="tel" value="{{ old('phone_e164', $member->phone_e164) }}" placeholder="+212 6 12 34 56 78" @required(! $member->isAdministrator())>@error('phone_e164')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            @if ($profile)
                <div class="col-sm-6"><label class="form-label" for="city">City</label><input class="form-control @error('city') is-invalid @enderror" id="city" name="city" value="{{ old('city', $profile->city) }}">@error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-sm-6"><label class="form-label" for="address">Address</label><input class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address', $profile->address) }}">@error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            @endif
        </div>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('admin.users.show', $member) }}">Cancel</a>
        <button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1"></i>Save changes</button>
    </div>
</form>
@endsection
