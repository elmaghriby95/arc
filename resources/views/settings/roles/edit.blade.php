<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('settings.roles.index') }}" class="settings-back-link">← العودة للأدوار</a>
                <h2 class="page-title">تعديل الدور: {{ $role->name }}</h2>
                <p class="page-subtitle">تحديث الصلاحيات والإعدادات</p>
            </div>
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        <form method="POST" action="{{ route('settings.roles.update', $role) }}" class="card">
            @csrf
            @method('PUT')
            <div class="card-body">
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <x-input-label for="name" value="اسم الدور" />
                        <x-text-input id="name" name="name" type="text" :value="old('name', $role->name)" required />
                        @error('name')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <x-input-label for="description" value="الوصف (اختياري)" />
                        <x-text-input id="description" name="description" type="text" :value="old('description', $role->description)" />
                        @error('description')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                @if ($role->is_system)
                    <div class="alert alert-info">هذا دور أساسي في النظام — يمكن تعديل صلاحياته لكن لا يمكن حذفه.</div>
                @endif

                @include('settings.roles.partials.permissions-form', [
                    'permissionGroups' => $permissionGroups,
                    'selected' => old('permissions', $role->permissions ?? []),
                ])
            </div>
            <div class="card-footer form-actions">
                <a href="{{ route('settings.roles.index') }}" class="btn btn-secondary">إلغاء</a>
                <x-primary-button>حفظ التعديلات</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
