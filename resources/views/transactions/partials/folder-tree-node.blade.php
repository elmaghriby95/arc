@props([
    'folder',
    'depth' => 0,
    'selected' => null,
])

@php
    $isSelected = (int) $selected === $folder->id;
@endphp

<div class="txnw-folder-node" data-folder-node data-folder-id="{{ $folder->id }}" data-folder-name="{{ $folder->name }}" data-department="{{ $folder->department_id }}" style="padding-right: {{ $depth * 14 }}px">
    <button type="button"
            class="txnw-folder-item {{ $isSelected ? 'is-selected' : '' }}"
            data-folder-select
            aria-pressed="{{ $isSelected ? 'true' : 'false' }}">
        <span class="txnw-folder-radio" aria-hidden="true"></span>
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="{{ $folder->color ?? '#6366f1' }}" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h5l2 2h9a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
        <span class="txnw-folder-name">{{ $folder->name }}</span>
    </button>

    @foreach ($folder->children as $child)
        @include('transactions.partials.folder-tree-node', ['folder' => $child, 'depth' => $depth + 1, 'selected' => $selected])
    @endforeach
</div>
