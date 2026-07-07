@props(['variant' => 'navbar'])

@if ($systemSettings->hasLogo())
    <img
        src="{{ $systemSettings->logoUrl() }}"
        alt="{{ $systemSettings->appName() }}"
        class="system-brand-img system-brand-img--{{ $variant }}"
    >
@else
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $variant === 'login' ? '1.5' : '1.6' }}" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 3h12a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/>
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6M9 11h6M9 15h4"/>
    </svg>
@endif
