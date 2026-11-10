@extends('layouts.admin')

@section('title', 'My account')
@section('page_title', 'My account')
@section('page_subtitle', 'Administrator accounts are provisioned and closed through the trusted command-line process.')
@section('breadcrumbs')<li class="breadcrumb-item active" aria-current="page">My account</li>@endsection

@section('content')
<div style="max-width:52rem">
    @include('account.partials.profile-form', ['user' => $user, 'profile' => null])
</div>
@endsection
