@props([
    'folders',
    'selected' => null,
])

<div class="form-group">
    <x-input-label for="folder_id" value="المجلد" />
    @if ($folders->isEmpty())
        <p class="form-hint alert alert-warning" style="margin-top:0.5rem;">
            لا توجد مجلدات متاحة لوحدتك التنظيمية. أنشئ مجلداً من
            <a href="{{ route('settings.folders.index') }}">شجرة المجلدات</a> أولاً.
        </p>
    @else
        <select id="folder_id" name="folder_id" class="form-select" required>
            <option value="" disabled @selected(! old('folder_id', $selected))>— اختر مجلداً —</option>
            @foreach ($folders as $folder)
                <option
                    value="{{ $folder->id }}"
                    data-department="{{ $folder->department_id }}"
                    @selected(old('folder_id', $selected) == $folder->id)
                >{{ $folder->name }}</option>
            @endforeach
        </select>
        <p class="form-hint">يجب حفظ المعاملة في مجلد من المجلدات المتاحة لوحدتك التنظيمية حسب الهيكل الوظيفي.</p>
    @endif
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const departmentSelect = document.getElementById('department_id');
                const folderSelect = document.getElementById('folder_id');

                if (! departmentSelect || ! folderSelect) {
                    return;
                }

                const filterFolders = () => {
                    const departmentId = departmentSelect.value;

                    Array.from(folderSelect.options).forEach((option) => {
                        if (! option.value) {
                            option.hidden = false;
                            return;
                        }

                        option.hidden = ! departmentId || option.dataset.department !== departmentId;
                    });

                    const selected = folderSelect.options[folderSelect.selectedIndex];

                    if (selected?.hidden) {
                        folderSelect.value = '';
                    }
                };

                departmentSelect.addEventListener('change', filterFolders);
                filterFolders();
            });
        </script>
    @endpush
@endonce
