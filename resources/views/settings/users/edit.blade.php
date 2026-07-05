<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('settings.users.index') }}" class="settings-back-link">← العودة للمستخدمين</a>
                <h2 class="page-title">تعديل المستخدم: {{ $user->name }}</h2>
                <p class="page-subtitle">تحديد الدور والموقع في الهيكل التنظيمي</p>
            </div>
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">بيانات المستخدم</h3>
            </div>
            <div class="card-body">
                <div class="user-edit-summary">
                    <span class="user-avatar user-avatar--{{ $user->role?->slug ?? 'user' }}">{{ mb_substr($user->name, 0, 1) }}</span>
                    <div>
                        <strong>{{ $user->name }}</strong>
                        <p class="text-muted">{{ $user->email }}</p>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('settings.users.update', $user) }}" class="card">
            @csrf
            @method('PUT')
            <div class="card-header">
                <h3 class="card-title">الدور والموقع التنظيمي</h3>
            </div>
            <div class="card-body">
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <x-input-label for="role_id" value="الدور" />
                        <select id="role_id" name="role_id" class="form-select" required>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" @selected(old('role_id', $user->role_id) == $role->id)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                        @error('role_id')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-group">
                        <x-input-label for="department_id" value="الموقع في الهيكل التنظيمي" />
                        @include('settings.partials.org-unit-select', [
                            'orgUnits' => $orgUnits,
                            'selected' => $user->department_id,
                        ])
                        @error('department_id')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                @if ($user->department_id && isset($breadcrumbs[$user->department_id]))
                    <div class="org-scope-preview">
                        <span class="org-scope-preview-label">المسار الحالي:</span>
                        <span class="org-path">{{ $breadcrumbs[$user->department_id] }}</span>
                    </div>
                @endif

                <div class="org-scope-info">
                    <strong>كيف يعمل النطاق؟</strong>
                    <ul>
                        <li>المستخدم يرى الوثائق والبيانات المرتبطة بوحدته فقط، والوحدات التابعة لها.</li>
                        <li>مثال: إذا عُيِّن على <em>إدارة</em> يرى كل الأقسام تحتها، وليس الإدارات الأخرى.</li>
                        <li>مدير النظام يرى كل المنظومة بغض النظر عن الموقع التنظيمي.</li>
                    </ul>
                </div>
            </div>
            <div class="card-footer">
                <x-primary-button>حفظ التغييرات</x-primary-button>
                <a href="{{ route('settings.users.index') }}" class="btn btn-secondary">إلغاء</a>
            </div>
        </form>
    </div>
</x-app-layout>
