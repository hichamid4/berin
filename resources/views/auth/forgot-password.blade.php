@extends('layouts.app')

@section('title', 'Forgot password')

@section('content')
<div class="row justify-content-center"><div class="col-md-7 col-lg-5"><div class="card border-0 shadow-sm"><div class="card-body p-4 p-lg-5">
    <h1 class="h3">Reset your password</h1>
    <p class="text-secondary">Enter the email address for your account. If it matches an eligible account, we will send a reset link.</p>
    <form method="post" action="{{ route('password.email') }}">@csrf
        <label class="form-label" for="email">Email address</label>
        <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <button class="btn btn-success w-100 mt-4" type="submit">Send reset link</button>
    </form>
</div></div></div></div>
@endsection
