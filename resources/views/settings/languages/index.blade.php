<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('settings.index') }}" class="settings-back-link">← العودة للإعدادات</a>
                <h2 class="page-title">اللغات</h2>
                <p class="page-subtitle">إدارة لغات واجهة النظام والمحتوى</p>
            </div>
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">إضافة لغة</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('settings.languages.store') }}">
                    @csrf
                    <div class="form-grid">
                        <div class="form-group">
                            <x-input-label for="name" value="الاسم" />
                            <x-text-input id="name" name="name" type="text" :value="old('name')" required />
                        </div>
                        <div class="form-group">
                            <x-input-label for="native_name" value="الاسم الأصلي" />
                            <x-text-input id="native_name" name="native_name" type="text" :value="old('native_name')" required />
                        </div>
                        <div class="form-group">
                            <x-input-label for="code" value="رمز اللغة" />
                            <x-text-input id="code" name="code" type="text" :value="old('code')" required placeholder="ar" />
                        </div>
                        <div class="form-group">
                            <x-input-label for="direction" value="الاتجاه" />
                            <select id="direction" name="direction" class="form-select" required>
                                <option value="rtl" @selected(old('direction') === 'rtl')>من اليمين لليسار (RTL)</option>
                                <option value="ltr" @selected(old('direction') === 'ltr')>من اليسار لليمين (LTR)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="form-check form-group">
                            <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', true))>
                            <x-input-label for="is_active" value="نشطة" />
                        </div>
                        <div class="form-check form-group">
                            <input id="is_default" name="is_default" type="checkbox" value="1" @checked(old('is_default'))>
                            <x-input-label for="is_default" value="اللغة الافتراضية" />
                        </div>
                    </div>
                    <x-primary-button>إضافة لغة</x-primary-button>
                </form>
            </div>
        </div>

        <div class="languages-grid">
            @forelse ($languages as $language)
                <div class="language-card {{ $language->is_active ? '' : 'language-card--inactive' }}">
                    <div class="language-card-flag">{{ strtoupper($language->code) }}</div>
                    <div class="language-card-body">
                        <div class="language-card-top">
                            <h3 class="language-card-name">{{ $language->native_name }}</h3>
                            @if ($language->is_default)
                                <span class="language-badge language-badge--default">افتراضية</span>
                            @endif
                        </div>
                        <p class="language-card-sub">{{ $language->name }}</p>
                        <div class="language-card-meta">
                            <span class="settings-badge">{{ $language->directionLabel() }}</span>
                            <span class="settings-badge settings-badge--muted">{{ $language->code }}</span>
                            @unless ($language->is_active)
                                <span class="settings-badge settings-badge--danger">غير نشطة</span>
                            @endunless
                        </div>
                    </div>
                    <details class="language-card-edit">
                        <summary class="ref-type-edit-toggle">تعديل</summary>
                        <form method="POST" action="{{ route('settings.languages.update', $language) }}" class="ref-type-edit-form">
                            @csrf
                            @method('PUT')
                            <div class="form-group">
                                <x-input-label value="الاسم" />
                                <x-text-input name="name" type="text" :value="$language->name" required />
                            </div>
                            <div class="form-group">
                                <x-input-label value="الاسم الأصلي" />
                                <x-text-input name="native_name" type="text" :value="$language->native_name" required />
                            </div>
                            <div class="form-group">
                                <x-input-label value="رمز اللغة" />
                                <x-text-input name="code" type="text" :value="$language->code" required />
                            </div>
                            <div class="form-group">
                                <x-input-label value="الاتجاه" />
                                <select name="direction" class="form-select" required>
                                    <option value="rtl" @selected($language->direction === 'rtl')>RTL</option>
                                    <option value="ltr" @selected($language->direction === 'ltr')>LTR</option>
                                </select>
                            </div>
                            <div class="form-check form-group">
                                <input name="is_active" type="checkbox" value="1" @checked($language->is_active)>
                                <x-input-label value="نشطة" />
                            </div>
                            <div class="form-check form-group">
                                <input name="is_default" type="checkbox" value="1" @checked($language->is_default)>
                                <x-input-label value="افتراضية" />
                            </div>
                            <div class="form-actions">
                                <x-primary-button>حفظ</x-primary-button>
                                @unless ($language->is_default)
                                    <button type="submit" formaction="{{ route('settings.languages.destroy', $language) }}" formmethod="POST" class="btn btn-danger" onclick="this.form.querySelector('[name=_method]').value='DELETE'; return confirm('هل أنت متأكد؟')">
                                        @csrf
                                        @method('DELETE')
                                        حذف
                                    </button>
                                @endunless
                            </div>
                        </form>
                    </details>
                </div>
            @empty
                <div class="settings-empty" style="grid-column:1/-1;">
                    <p>لا توجد لغات مضافة بعد.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
