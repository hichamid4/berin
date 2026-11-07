@extends('layouts.app')
@section('title', 'Homeowner dashboard')
@section('content')
<div class="row justify-content-center"><div class="col-lg-8"><div class="card border-0 shadow-sm"><div class="card-body p-4 p-lg-5">
    <p class="text-success fw-semibold mb-1">Homeowner</p><h1 class="h3">Welcome, {{ $user->first_name }}</h1>
    <p class="text-secondary mb-0">Your email is verified and your account is ready.</p>
</div></div></div></div>
@endsection
