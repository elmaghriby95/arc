@props(['status'])

@php
    $statusEnum = $status instanceof \App\Enums\LendingRequestStatus
        ? $status
        : ($status ? \App\Enums\LendingRequestStatus::tryFrom((string) $status) : null);
@endphp

@if ($statusEnum)
    <span class="badge" style="background-color: {{ $statusEnum->color() }}; color: #fff;">
        {{ $statusEnum->label() }}
    </span>
@else
    —
@endif
