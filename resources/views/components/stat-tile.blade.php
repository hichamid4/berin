@props(['icon', 'label', 'value', 'tone' => 'icon-tone-1'])
<div {{ $attributes->merge(['class' => 'stat-tile']) }}>
    <span class="icon-tile {{ $tone }}"><i class="bi {{ $icon }}"></i></span>
    <div>
        <strong>{{ $value }}</strong>
        <span>{{ $label }}</span>
    </div>
</div>
