@props(['status'])

@if ($status)
    <span class="badge" style="background-color: {{ $status->color() }}; color: #fff;">
        {{ $status->label() }}
    </span>
@else
    —
@endif
