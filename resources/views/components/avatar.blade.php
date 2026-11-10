@props(['user', 'size' => '', 'photoUrl' => null])
@php($tone = 'tone-'.(($user->id % 6) + 1))
@if ($photoUrl)
    <img {{ $attributes->merge(['class' => trim("avatar {$size}"), 'src' => $photoUrl, 'alt' => $user->fullName(), 'loading' => 'lazy']) }}>
@else
    <span {{ $attributes->merge(['class' => trim("avatar {$size} {$tone}"), 'title' => $user->fullName()]) }}>{{ $user->initials() }}</span>
@endif
