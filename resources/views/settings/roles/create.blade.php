<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('settings.roles.index') }}" class="settings-back-link">← العودة للأدوار</a>
                <h2 class="page-title">إنشاء دور جديد</h2>
                <p class="page-subtitle">حدّد اسم الدور واختر كل الصلاحيات المطلوبة</p>
            </div>
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        <form method="POST" action="{{ route('settings.roles.store') }}" class="card">
            @csrf
            <div class="card-body">
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <x-input-label for="name" value="اسم الدور" />
                        <x-text-input id="name" name="name" type="text" :value="old('name')" required placeholder="مثال: موظف أرشيف" />
                        @error('name')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <x-input-label for="description" value="الوصف (اختياري)" />
                        <x-text-input id="description" name="description" type="text" :value="old('description')" placeholder="وصف مختصر للدور" />
                        @error('description')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                @include('settings.roles.partials.permissions-form', [
                    'permissionGroups' => $permissionGroups,
                    'selected' => old('permissions', []),
                ])
            </div>
            <div class="card-footer form-actions">
                <a href="{{ route('settings.roles.index') }}" class="btn btn-secondary">إلغاء</a>
                <x-primary-button>حفظ الدور</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
