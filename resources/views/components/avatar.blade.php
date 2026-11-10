@props(['user', 'size' => ''])
@php($tone = 'tone-'.(($user->id % 6) + 1))
<span {{ $attributes->merge(['class' => trim("avatar {$size} {$tone}"), 'title' => $user->fullName()]) }}>{{ $user->initials() }}</span>
