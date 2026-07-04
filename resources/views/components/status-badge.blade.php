@props(['status'])

@php
    use App\Enums\DocumentStatus;

    $documentStatus = $status instanceof DocumentStatus
        ? $status
        : DocumentStatus::from($status);

    $class = match ($documentStatus) {
        DocumentStatus::Active => 'status-badge--active',
        DocumentStatus::Draft => 'status-badge--draft',
        DocumentStatus::Archived => 'status-badge--archived',
    };
@endphp

<span {{ $attributes->merge(['class' => 'status-badge '.$class]) }}>
    {{ $documentStatus->label() }}
</span>
