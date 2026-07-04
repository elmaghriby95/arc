@props([
    'folders',
    'folderTree',
    'selected' => null,
])

<div class="txn-folder-picker" data-folder-picker>
    <input type="hidden" name="folder_id" id="folder_id" value="{{ old('folder_id', $selected) }}" data-folder-input required>

    @if ($folders->isEmpty())
        <div class="txn-folder-empty">
            <p>لا توجد مجلدات متاحة لوحدتك.</p>
            <a href="{{ route('settings.folders.index') }}" class="btn btn-secondary btn-sm">إعداد شجرة المجلدات</a>
        </div>
    @else
        <div class="txn-folder-picker-search">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
            <input type="search" class="form-control form-control--compact" placeholder="بحث في المجلدات..." data-folder-search autocomplete="off">
        </div>
        <div class="txn-folder-tree" data-folder-tree role="tree" aria-label="اختيار المجلد">
            @foreach ($folderTree as $folder)
                @include('transactions.partials.folder-tree-node', ['folder' => $folder, 'depth' => 0, 'selected' => old('folder_id', $selected)])
            @endforeach
        </div>
    @endif
</div>
