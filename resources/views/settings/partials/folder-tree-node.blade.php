@props(['folder', 'depth' => 0])

<li class="org-tree-node" data-org-node>
    <div class="org-tree-item" style="--depth: {{ $depth }}">
        @if ($folder->children->isNotEmpty())
            <button type="button" class="org-tree-toggle" data-org-toggle aria-expanded="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                </svg>
            </button>
        @else
            <span class="org-tree-spacer"></span>
        @endif

        <div class="org-tree-card folder-tree-card {{ $folder->is_active ? '' : 'org-tree-card--inactive' }}" @if($folder->color) style="border-right-color: {{ $folder->color }};" @endif>
            <div class="org-tree-card-icon folder-tree-icon" @if($folder->color) style="background: {{ $folder->color }}20; color: {{ $folder->color }};" @endif>
                @if ($depth === 0)
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                    </svg>
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                    </svg>
                @endif
            </div>
            <div class="org-tree-card-body">
                <div class="org-tree-card-top">
                    <strong class="org-tree-card-name">{{ $folder->name }}</strong>
                    @if ($folder->children->isNotEmpty())
                        <span class="settings-badge settings-badge--muted">{{ $folder->children->count() }} مجلد فرعي</span>
                    @endif
                </div>
                @if ($folder->description)
                    <p class="org-tree-card-desc">{{ $folder->description }}</p>
                @endif
                <div class="org-tree-card-meta">
                    @if ($folder->department_id && isset($breadcrumbs[$folder->department_id]))
                        <span class="settings-badge settings-badge--muted">{{ $breadcrumbs[$folder->department_id] }}</span>
                    @endif
                    <span class="settings-badge">ترتيب {{ $folder->sort_order }}</span>
                    @unless ($folder->is_active)
                        <span class="settings-badge settings-badge--danger">غير نشط</span>
                    @endunless
                </div>
            </div>
            <details class="folder-tree-edit">
                <summary class="org-tree-edit" title="تعديل">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                    </svg>
                </summary>
                <form method="POST" action="{{ route('settings.folders.update', $folder) }}" class="folder-edit-form">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <x-input-label value="الاسم" />
                        <x-text-input name="name" type="text" :value="$folder->name" required />
                    </div>
                    <div class="form-group">
                        <x-input-label value="الوصف" />
                        <textarea name="description" rows="2" class="form-control">{{ $folder->description }}</textarea>
                    </div>
                    <div class="form-group">
                        <x-input-label value="الوحدة التنظيمية" />
                        @include('settings.partials.org-unit-select', [
                            'name' => 'department_id',
                            'id' => 'department_id_'.$folder->id,
                            'orgUnits' => $orgUnits,
                            'selected' => $folder->department_id,
                            'placeholder' => '— اختر الوحدة التنظيمية —',
                            'showHint' => false,
                        ])
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <x-input-label value="اللون" />
                            <x-text-input name="color" type="text" :value="$folder->color" placeholder="#4338ca" />
                        </div>
                        <div class="form-group">
                            <x-input-label value="الترتيب" />
                            <x-text-input name="sort_order" type="number" :value="$folder->sort_order" min="0" />
                        </div>
                    </div>
                    <div class="form-check form-group">
                        <input name="is_active" type="checkbox" value="1" @checked($folder->is_active)>
                        <x-input-label value="نشط" />
                    </div>
                    <div class="form-actions">
                        <x-primary-button>حفظ</x-primary-button>
                        <button type="submit" formaction="{{ route('settings.folders.destroy', $folder) }}" formmethod="POST" class="btn btn-danger" onclick="this.form.querySelector('[name=_method]').value='DELETE'; return confirm('هل أنت متأكد؟')">
                            @csrf
                            @method('DELETE')
                            حذف
                        </button>
                    </div>
                </form>
            </details>
        </div>
    </div>

    @if ($folder->children->isNotEmpty())
        <ul class="org-tree-children" data-org-children>
            @foreach ($folder->children as $child)
                @include('settings.partials.folder-tree-node', ['folder' => $child, 'depth' => $depth + 1, 'breadcrumbs' => $breadcrumbs, 'orgUnits' => $orgUnits])
            @endforeach
        </ul>
    @endif
</li>
