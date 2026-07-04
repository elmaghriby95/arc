@props(['status'])

<span class="txn-status-badge" style="--txn-status-color: {{ $status->color ?? '#64748b' }}">
    {{ $status->name }}
</span>
