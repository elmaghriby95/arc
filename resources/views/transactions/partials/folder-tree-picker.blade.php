@props([
    'folders',
    'folderTree',
    'selected' => null,
])

<div class="txn-folder-picker" data-folder-picker>
    <input type="hidden" name="folder_id" id="folder_id" value="{{ old('folder_id', $selected) }}" data-folder-input required>

    @if ($folders->isEmpty())
        <p class="form-hint alert alert-warning">
            لا توجد مجلدات متاحة. أنشئ مجلداً من
            <a href="{{ route('settings.folders.index') }}">شجرة المجلدات</a> أولاً.
        </p>
    @else
        <div class="txn-folder-picker-search">
            <input type="search" class="form-control form-control--compact" placeholder="بحث في المجلدات..." data-folder-search autocomplete="off">
        </div>
        <div class="txn-folder-tree" data-folder-tree role="tree" aria-label="اختيار المجلد">
            @foreach ($folderTree as $folder)
                @include('transactions.partials.folder-tree-node', ['folder' => $folder, 'depth' => 0, 'selected' => old('folder_id', $selected)])
            @endforeach
        </div>
        <p class="form-hint txn-folder-picker-hint" data-folder-hint>
            @if (old('folder_id', $selected))
                @php $picked = $folders->firstWhere('id', (int) old('folder_id', $selected)); @endphp
                المحدد: <strong>{{ $picked?->name ?? '—' }}</strong>
            @else
                اختر مجلداً لحفظ المعاملة ومستنداتها.
            @endif
        </p>
    @endif
</div>
