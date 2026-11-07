@extends('layouts.app')
@section('title', 'Administrator dashboard')
@section('content')
<div class="row justify-content-center"><div class="col-lg-8"><div class="card border-0 shadow-sm"><div class="card-body p-4 p-lg-5">
    <p class="text-success fw-semibold mb-1">Private control panel</p><h1 class="h3">Welcome, {{ $user->first_name }}</h1>
    <p class="text-secondary mb-0">You are signed in as a platform administrator.</p>
</div></div></div></div>
@endsection
