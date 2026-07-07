@props(['status'])

@php
    $statusEnum = $status instanceof \App\Enums\LendingRequestStatus
        ? $status
        : ($status ? \App\Enums\LendingRequestStatus::tryFrom((string) $status) : null);
@endphp

@if ($statusEnum)
    <span class="lending-status-badge" style="background-color: {{ $statusEnum->color() }};">
        {{ $statusEnum->label() }}
    </span>
@else
    —
@endif
