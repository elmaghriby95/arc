@props([
    'folders',
    'folderTree',
    'selected' => null,
    'departmentBreadcrumbs' => [],
])

<div class="txnw-folder-picker" data-folder-picker>
    <input type="hidden" name="folder_id" id="folder_id" value="{{ old('folder_id', $selected) }}" data-folder-input required>

    @if ($folders->isEmpty())
        <div class="txnw-alert txnw-alert--warn">
            {!! __('transactions.no_folders_html', ['url' => route('settings.folders.index')]) !!}
        </div>
    @else
        <div class="txnw-search">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
            <input type="search" class="txnw-input" placeholder="{{ __('common.search_placeholder') }}" data-folder-search autocomplete="off">
        </div>
        <div class="txnw-folder-list" data-folder-tree>
            @foreach ($folderTree as $folder)
                @include('transactions.partials.folder-tree-node', [
                    'folder' => $folder,
                    'depth' => 0,
                    'selected' => old('folder_id', $selected),
                    'departmentBreadcrumbs' => $departmentBreadcrumbs,
                ])
            @endforeach
        </div>
    @endif
</div>
