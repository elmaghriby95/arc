@props(['tone' => 'dark'])

<div {{ $attributes->merge(['class' => 'arkeon-lockup arkeon-lockup--'.$tone]) }}>
    @if ($systemSettings->hasLogo())
        <img
            src="{{ $systemSettings->logoUrl() }}"
            alt=""
            class="arkeon-lockup-logo"
            decoding="async"
        >
    @else
        <span class="arkeon-lockup-mark">
            @include('layouts.partials.arkeon-mark')
        </span>
    @endif
    <span class="arkeon-lockup-copy">
        <span class="arkeon-lockup-en">ARKÉON</span>
        <span class="arkeon-lockup-ar">أركيون</span>
    </span>
</div>
