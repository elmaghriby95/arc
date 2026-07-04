<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('settings.index') }}" class="settings-back-link">← العودة للإعدادات</a>
                <h2 class="page-title">أنواع المعاملات</h2>
                <p class="page-subtitle">تصنيف أنواع المعاملات الإدارية والرسمية</p>
            </div>
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">إضافة نوع معاملة</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('settings.transaction-types.store') }}" class="ref-form">
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
            @forelse ($transactionTypes as $type)
                <div class="ref-type-card {{ $type->is_active ? '' : 'ref-type-card--inactive' }}">
                    <div class="ref-type-card-icon ref-type-card-icon--txn">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
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
                        <form method="POST" action="{{ route('settings.transaction-types.update', $type) }}" class="ref-type-edit-form">
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
                        <form method="POST" action="{{ route('settings.transaction-types.destroy', $type) }}" class="ref-type-delete-form" onsubmit="return confirm('هل أنت متأكد من الحذف؟')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">حذف</button>
                        </form>
                    </details>
                </div>
            @empty
                <div class="settings-empty" style="grid-column:1/-1;">
                    <p>لا توجد أنواع معاملات بعد.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
