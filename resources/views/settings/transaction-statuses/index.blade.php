<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('settings.index') }}" class="settings-back-link">← العودة للإعدادات</a>
                <h2 class="page-title">حالات المعاملات</h2>
                <p class="page-subtitle">تسلسل سير عمل المعاملات من الإنشاء حتى الأرشفة</p>
            </div>
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        @if ($statuses->isNotEmpty())
            <div class="card txn-workflow-preview">
                <div class="card-header">
                    <h3 class="card-title">مسار سير العمل</h3>
                    <p class="card-subtitle">تتبع المعاملات هذا التسلسل بالترتيب</p>
                </div>
                <div class="card-body">
                    <div class="txn-workflow-steps">
                        @foreach ($statuses->where('is_active', true)->sortBy('sort_order') as $index => $status)
                            <div class="txn-workflow-step">
                                <span class="txn-workflow-step-num">{{ $index + 1 }}</span>
                                <span class="txn-status-badge" style="--txn-status-color: {{ $status->color ?? '#64748b' }}">{{ $status->name }}</span>
                                @if ($status->is_initial)
                                    <span class="settings-badge">ابتدائية</span>
                                @endif
                                @if ($status->is_final)
                                    <span class="settings-badge settings-badge--success">نهائية</span>
                                @endif
                            </div>
                            @if (! $loop->last)
                                <span class="txn-workflow-arrow" aria-hidden="true">←</span>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @permission('settings.transaction-statuses.create')
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">إضافة حالة</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('settings.transaction-statuses.store') }}" class="ref-form">
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
                            <x-input-label for="sort_order" value="الترتيب في التسلسل" />
                            <x-text-input id="sort_order" name="sort_order" type="number" :value="old('sort_order', ($statuses->max('sort_order') ?? 0) + 1)" min="0" />
                        </div>
                        <div class="form-group">
                            <x-input-label for="color" value="اللون" />
                            <x-text-input id="color" name="color" type="text" :value="old('color', '#64748b')" placeholder="#64748b" />
                        </div>
                    </div>
                    <div class="form-group">
                        <x-input-label for="description" value="الوصف" />
                        <textarea id="description" name="description" rows="2" class="form-control">{{ old('description') }}</textarea>
                    </div>
                    <div class="form-group" data-status-permission-field>
                        <x-input-label for="required_permission" value="صلاحية المعالجة في هذه المرحلة (اعتماد / رفض)" />
                        <select id="required_permission" name="required_permission" class="form-select" @required(! old('is_initial'))>
                            <option value="" @selected(! old('required_permission')) disabled hidden>— اختر صلاحية —</option>
                            @foreach ($permissions as $group => $groupPermissions)
                                <optgroup label="{{ $group }}">
                                    @foreach ($groupPermissions as $permission)
                                        <option value="{{ $permission->value }}" @selected(old('required_permission') === $permission->value)>{{ $permission->label() }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        @error('required_permission')<p class="form-error">{{ $message }}</p>@enderror
                        <small class="form-hint" data-status-permission-hint-required>مطلوبة لكل مرحلة غير ابتدائية — يتحقق النظام منها قبل أي اعتماد أو رفض.</small>
                        <small class="form-hint" data-status-permission-hint-initial hidden>الحالة الابتدائية تعتمد على صلاحية «إنشاء معاملة» ولا تحتاج صلاحية هنا.</small>
                    </div>
                    <div class="form-grid form-grid--checks">
                        <div class="form-check">
                            <input id="is_initial" name="is_initial" type="checkbox" value="1" @checked(old('is_initial')) data-status-initial-toggle>
                            <x-input-label for="is_initial" value="حالة ابتدائية (مسودة)" />
                        </div>
                        <div class="form-check">
                            <input id="is_final" name="is_final" type="checkbox" value="1" @checked(old('is_final'))>
                            <x-input-label for="is_final" value="حالة نهائية" />
                        </div>
                        <div class="form-check">
                            <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', true))>
                            <x-input-label for="is_active" value="نشط" />
                        </div>
                    </div>
                    <x-primary-button>إضافة</x-primary-button>
                </form>
            </div>
        </div>
        @endpermission

        <div class="ref-types-grid">
            @forelse ($statuses as $status)
                <div class="ref-type-card {{ $status->is_active ? '' : 'ref-type-card--inactive' }}">
                    <div class="ref-type-card-icon ref-type-card-icon--status" style="background: {{ $status->color ?? '#64748b' }}20; color: {{ $status->color ?? '#64748b' }}">
                        <span class="txn-status-order">{{ $status->sort_order }}</span>
                    </div>
                    <div class="ref-type-card-body">
                        <div class="ref-type-card-top">
                            <h3 class="ref-type-card-title">{{ $status->name }}</h3>
                            <span class="org-tree-code">{{ $status->code }}</span>
                        </div>
                        @if ($status->description)
                            <p class="ref-type-card-desc">{{ $status->description }}</p>
                        @endif
                        <div class="ref-type-card-meta">
                            @if ($status->is_initial)
                                <span class="settings-badge">ابتدائية</span>
                            @endif
                            @if ($status->is_final)
                                <span class="settings-badge settings-badge--success">نهائية</span>
                            @endif
                            @if ($status->is_initial)
                                <span class="settings-badge settings-badge--muted">إنشاء معاملة</span>
                            @elseif ($status->required_permission)
                                <span class="settings-badge settings-badge--muted" title="{{ $status->required_permission }}">{{ $status->permissionLabel() }}</span>
                            @else
                                <span class="settings-badge settings-badge--danger">بدون صلاحية — عدّل المرحلة</span>
                            @endif
                            @unless ($status->is_active)
                                <span class="settings-badge settings-badge--danger">غير نشط</span>
                            @endunless
                        </div>
                    </div>
                    @permission('settings.transaction-statuses.edit')
                    <details class="ref-type-edit">
                        <summary class="ref-type-edit-toggle">تعديل</summary>
                        <form method="POST" action="{{ route('settings.transaction-statuses.update', $status) }}" class="ref-type-edit-form">
                            @csrf
                            @method('PUT')
                            <div class="form-group">
                                <x-input-label value="الاسم" />
                                <x-text-input name="name" type="text" :value="old('name', $status->name)" required />
                            </div>
                            <div class="form-group">
                                <x-input-label value="الرمز" />
                                <x-text-input name="code" type="text" :value="old('code', $status->code)" required />
                            </div>
                            <div class="form-group">
                                <x-input-label value="الترتيب" />
                                <x-text-input name="sort_order" type="number" :value="old('sort_order', $status->sort_order)" min="0" />
                            </div>
                            <div class="form-group">
                                <x-input-label value="اللون" />
                                <x-text-input name="color" type="text" :value="old('color', $status->color)" />
                            </div>
                            <div class="form-group">
                                <x-input-label value="الوصف" />
                                <textarea name="description" rows="2" class="form-control">{{ old('description', $status->description) }}</textarea>
                            </div>
                            <div class="form-group" data-status-permission-field @if ($status->is_initial) hidden @endif>
                                <x-input-label value="صلاحية الانتقال" />
                                <select name="required_permission" class="form-select" @required(! $status->is_initial) @disabled($status->is_initial)>
                                    <option value="" @selected(! old('required_permission', $status->required_permission)) disabled hidden>— اختر صلاحية —</option>
                                    @foreach ($permissions as $group => $groupPermissions)
                                        <optgroup label="{{ $group }}">
                                            @foreach ($groupPermissions as $permission)
                                                <option value="{{ $permission->value }}" @selected(old('required_permission', $status->required_permission) === $permission->value)>{{ $permission->label() }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                @error('required_permission')<p class="form-error">{{ $message }}</p>@enderror
                                <small class="form-hint" data-status-permission-hint-required @if ($status->is_initial) hidden @endif>مطلوبة لكل مرحلة غير ابتدائية.</small>
                                <small class="form-hint" data-status-permission-hint-initial @unless ($status->is_initial) hidden @endunless>الحالة الابتدائية تعتمد على صلاحية «إنشاء معاملة».</small>
                            </div>
                            <div class="form-check form-group">
                                <input name="is_initial" type="checkbox" value="1" @checked(old('is_initial', $status->is_initial)) data-status-initial-toggle>
                                <x-input-label value="حالة ابتدائية" />
                            </div>
                            <div class="form-check form-group">
                                <input name="is_final" type="checkbox" value="1" @checked(old('is_final', $status->is_final))>
                                <x-input-label value="حالة نهائية" />
                            </div>
                            <div class="form-check form-group">
                                <input name="is_active" type="checkbox" value="1" @checked(old('is_active', $status->is_active))>
                                <x-input-label value="نشط" />
                            </div>
                            <div class="form-actions">
                                <x-primary-button>حفظ</x-primary-button>
                            </div>
                        </form>
                        @permission('settings.transaction-statuses.delete')
                        <form method="POST" action="{{ route('settings.transaction-statuses.destroy', $status) }}" class="ref-type-delete-form" onsubmit="return confirm('هل أنت متأكد من الحذف؟')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">حذف</button>
                        </form>
                        @endpermission
                    </details>
                    @endpermission
                </div>
            @empty
                <div class="settings-empty" style="grid-column:1/-1;">
                    <p>لا توجد حالات معاملات. أضف الحالة الابتدائية (مسودة) أولاً.</p>
                </div>
            @endforelse
        </div>
    </div>

    @push('scripts')
        <script>
            document.querySelectorAll('[data-status-initial-toggle]').forEach((checkbox) => {
                const form = checkbox.closest('form');
                const permissionField = form?.querySelector('[data-status-permission-field]');
                const permissionSelect = form?.querySelector('[name="required_permission"]');
                const hintRequired = form?.querySelector('[data-status-permission-hint-required]');
                const hintInitial = form?.querySelector('[data-status-permission-hint-initial]');

                if (! permissionSelect) {
                    return;
                }

                const sync = () => {
                    const isInitial = checkbox.checked;
                    permissionSelect.disabled = isInitial;
                    permissionSelect.required = ! isInitial;

                    if (isInitial) {
                        permissionSelect.value = '';
                    }

                    if (hintRequired) {
                        hintRequired.hidden = isInitial;
                    }

                    if (hintInitial) {
                        hintInitial.hidden = ! isInitial;
                    }

                    if (permissionField) {
                        permissionField.hidden = isInitial;
                    }
                };

                checkbox.addEventListener('change', sync);
                sync();
            });
        </script>
    @endpush
</x-app-layout>
