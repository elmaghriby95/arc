@props([
    'folder',
    'depth' => 0,
    'selected' => null,
])

@php
    $isSelected = (int) $selected === $folder->id;
@endphp

<div class="txn-folder-node" data-folder-node data-folder-id="{{ $folder->id }}" data-folder-name="{{ $folder->name }}" data-department="{{ $folder->department_id }}" style="--folder-depth: {{ $depth }}">
    <button type="button"
            class="txn-folder-node-btn {{ $isSelected ? 'is-selected' : '' }}"
            data-folder-select
            role="treeitem"
            aria-selected="{{ $isSelected ? 'true' : 'false' }}">
        <span class="txn-folder-node-icon" @if($folder->color) style="color: {{ $folder->color }};" @endif aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h5l2 2h9a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
        </span>
        <span class="txn-folder-node-label">{{ $folder->name }}</span>
    </button>

    @if ($folder->children->isNotEmpty())
        <div class="txn-folder-children" role="group">
            @foreach ($folder->children as $child)
                @include('transactions.partials.folder-tree-node', ['folder' => $child, 'depth' => $depth + 1, 'selected' => $selected])
            @endforeach
        </div>
    @endif
</div>
