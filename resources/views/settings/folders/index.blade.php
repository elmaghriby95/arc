<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('settings.index') }}" class="settings-back-link">← العودة للإعدادات</a>
                <h2 class="page-title">شجرة المجلدات</h2>
                <p class="page-subtitle">تنظيم هيكل المجلدات لتصنيف الوثائق — يظهر لكل مستخدم مجلدات وحدته التنظيمية والوحدات التابعة لها فقط</p>
            </div>
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        <div class="org-stats">
            <div class="org-stat">
                <span class="org-stat-value">{{ $totalFolders }}</span>
                <span class="org-stat-label">إجمالي المجلدات</span>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">إضافة مجلد</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('settings.folders.store') }}">
                    @csrf
                    <div class="form-grid">
                        <div class="form-group">
                            <x-input-label for="name" value="اسم المجلد" />
                            <x-text-input id="name" name="name" type="text" :value="old('name')" required />
                        </div>
                        <div class="form-group">
                            <x-input-label for="parent_id" value="المجلد الأب" />
                            <select id="parent_id" name="parent_id" class="form-select">
                                <option value="">— مجلد جذري —</option>
                                @foreach ($parents as $parent)
                                    <option value="{{ $parent->id }}" @selected(old('parent_id') == $parent->id)>{{ $parent->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <x-input-label for="department_id" value="الوحدة التنظيمية" />
                            @include('settings.partials.org-unit-select', [
                                'orgUnits' => $orgUnits,
                                'selected' => old('department_id'),
                                'placeholder' => '— اختر الوحدة التنظيمية —',
                                'showHint' => false,
                                'required' => true,
                            ])
                            <p class="form-hint">يجب أن تطابق الوحدة المختارة هنا الوحدة التي ستُحدَّد عند إنشاء المعاملة.</p>
                            @error('department_id')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-group">
                            <x-input-label for="color" value="اللون" />
                            <x-text-input id="color" name="color" type="text" :value="old('color')" placeholder="#4338ca" />
                        </div>
                        <div class="form-group">
                            <x-input-label for="sort_order" value="الترتيب" />
                            <x-text-input id="sort_order" name="sort_order" type="number" :value="old('sort_order', 0)" min="0" />
                        </div>
                    </div>
                    <div class="form-group">
                        <x-input-label for="description" value="الوصف" />
                        <textarea id="description" name="description" rows="2" class="form-control">{{ old('description') }}</textarea>
                    </div>
                    <div class="form-check form-group">
                        <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', true))>
                        <x-input-label for="is_active" value="نشط" />
                    </div>
                    <x-primary-button>إضافة مجلد</x-primary-button>
                </form>
            </div>
        </div>

        <div class="card org-tree-card-wrapper">
            <div class="card-header">
                <h3 class="card-title">شجرة المجلدات</h3>
                <button type="button" class="btn btn-secondary" data-org-expand-all>توسيع الكل</button>
            </div>
            <div class="card-body">
                @if ($folders->isEmpty())
                    <div class="settings-empty">
                        <div class="settings-empty-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                            </svg>
                        </div>
                        <p>لا توجد مجلدات بعد.</p>
                    </div>
                @else
                    <ul class="org-tree" data-org-tree>
                        @foreach ($folders as $folder)
                            @include('settings.partials.folder-tree-node', ['folder' => $folder, 'depth' => 0, 'breadcrumbs' => $breadcrumbs, 'orgUnits' => $orgUnits])
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
