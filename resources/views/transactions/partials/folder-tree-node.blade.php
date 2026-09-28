@props([
    'folder',
    'depth' => 0,
    'selected' => null,
    'departmentBreadcrumbs' => [],
])

@php
    $isSelected = (int) $selected === $folder->id;
    $hasChildren = $folder->children->isNotEmpty();
    $childrenId = 'folder-children-'.$folder->id;
@endphp

<div class="txnw-folder-node" data-folder-node data-folder-id="{{ $folder->id }}" data-folder-name="{{ $folder->name }}" data-department="{{ $folder->department_id }}">
    <div class="txnw-folder-row">
        @if ($hasChildren)
            <button type="button"
                    class="txnw-folder-toggle"
                    data-folder-toggle
                    aria-expanded="false"
                    aria-controls="{{ $childrenId }}"
                    aria-label="{{ __('transactions.show_subfolders') }}"
                    title="{{ __('transactions.show_subfolders') }}">
                <svg class="txnw-folder-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/>
                </svg>
            </button>
        @else
            <span class="txnw-folder-toggle-spacer" aria-hidden="true"></span>
        @endif

        <button type="button"
                class="txnw-folder-item {{ $isSelected ? 'is-selected' : '' }}"
                data-folder-select
                aria-pressed="{{ $isSelected ? 'true' : 'false' }}">
            <span class="txnw-folder-radio" aria-hidden="true"></span>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="{{ $folder->color ?? '#6366f1' }}" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h5l2 2h9a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
            <span class="txnw-folder-name">{{ $folder->name }}</span>
            @if ($folder->department_id && isset($departmentBreadcrumbs[$folder->department_id]))
                <span class="txnw-folder-unit">{{ $departmentBreadcrumbs[$folder->department_id] }}</span>
            @endif
        </button>
    </div>

    @if ($hasChildren)
        <div class="txnw-folder-children is-collapsed" id="{{ $childrenId }}" data-folder-children>
            @foreach ($folder->children as $child)
                @include('transactions.partials.folder-tree-node', [
                    'folder' => $child,
                    'depth' => $depth + 1,
                    'selected' => $selected,
                    'departmentBreadcrumbs' => $departmentBreadcrumbs,
                ])
            @endforeach
        </div>
    @endif
</div>
