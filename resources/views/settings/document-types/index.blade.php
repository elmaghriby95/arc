<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('settings.index') }}" class="settings-back-link">← العودة للإعدادات</a>
                <h2 class="page-title">أنواع المستندات</h2>
                <p class="page-subtitle">تصنيف أنواع المستندات والوثائق في النظام</p>
            </div>
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">إضافة نوع مستند</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('settings.document-types.store') }}" class="ref-form">
                    @csrf
                    <div class="form-grid">
                        <div class="form-group">
                            <x-input-label for="name" value="الاسم" />
                            <x-text-input id="name" name="name" type="text" :value="old('name')" required />
                        </div>
                        <div class="form-group">
                            <x-input-label for="code" value="الرمز" />
                            <x-text-input id="code" name="code" type="text" :value="old('code')" required />
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
                    <x-primary-button>إضافة</x-primary-button>
                </form>
            </div>
        </div>

        <div class="ref-types-grid">
            @forelse ($documentTypes as $type)
                <div class="ref-type-card {{ $type->is_active ? '' : 'ref-type-card--inactive' }}">
                    <div class="ref-type-card-icon ref-type-card-icon--doc">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                    </div>
                    <div class="ref-type-card-body">
                        <div class="ref-type-card-top">
                            <h3 class="ref-type-card-title">{{ $type->name }}</h3>
                            <span class="org-tree-code">{{ $type->code }}</span>
                        </div>
                        @if ($type->description)
                            <p class="ref-type-card-desc">{{ $type->description }}</p>
                        @endif
                        <div class="ref-type-card-meta">
                            <span class="settings-badge">ترتيب {{ $type->sort_order }}</span>
                            @unless ($type->is_active)
                                <span class="settings-badge settings-badge--danger">غير نشط</span>
                            @endunless
                        </div>
                    </div>
                    <details class="ref-type-edit">
                        <summary class="ref-type-edit-toggle">تعديل</summary>
                        <form method="POST" action="{{ route('settings.document-types.update', $type) }}" class="ref-type-edit-form">
                            @csrf
                            @method('PUT')
                            <div class="form-group">
                                <x-input-label value="الاسم" />
                                <x-text-input name="name" type="text" :value="old('name', $type->name)" required />
                            </div>
                            <div class="form-group">
                                <x-input-label value="الرمز" />
                                <x-text-input name="code" type="text" :value="old('code', $type->code)" required />
                            </div>
                            <div class="form-group">
                                <x-input-label value="الوصف" />
                                <textarea name="description" rows="2" class="form-control">{{ old('description', $type->description) }}</textarea>
                            </div>
                            <div class="form-group">
                                <x-input-label value="الترتيب" />
                                <x-text-input name="sort_order" type="number" :value="old('sort_order', $type->sort_order)" min="0" />
                            </div>
                            <div class="form-check form-group">
                                <input name="is_active" type="checkbox" value="1" @checked(old('is_active', $type->is_active))>
                                <x-input-label value="نشط" />
                            </div>
                            <div class="form-actions">
                                <x-primary-button>حفظ</x-primary-button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('settings.document-types.destroy', $type) }}" class="ref-type-delete-form" onsubmit="return confirm('هل أنت متأكد من الحذف؟')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">حذف</button>
                        </form>
                    </details>
                </div>
            @empty
                <div class="settings-empty" style="grid-column:1/-1;">
                    <p>لا توجد أنواع مستندات بعد.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
