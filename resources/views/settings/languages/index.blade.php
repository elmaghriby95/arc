<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('settings.index') }}" class="settings-back-link">{{ __('common.back') }}</a>
                <h2 class="page-title">{{ __('languages.title') }}</h2>
                <p class="page-subtitle">{{ __('languages.subtitle') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        @permission('settings.languages.create')
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('languages.add_title') }}</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('settings.languages.store') }}">
                    @csrf
                    <div class="form-grid">
                        <div class="form-group">
                            <x-input-label for="name" :value="__('common.name')" />
                            <x-text-input id="name" name="name" type="text" :value="old('name')" required />
                        </div>
                        <div class="form-group">
                            <x-input-label for="native_name" :value="__('languages.native_name')" />
                            <x-text-input id="native_name" name="native_name" type="text" :value="old('native_name')" required />
                        </div>
                        <div class="form-group">
                            <x-input-label for="code" :value="__('languages.code')" />
                            <x-text-input id="code" name="code" type="text" :value="old('code')" required placeholder="ar" />
                        </div>
                        <div class="form-group">
                            <x-input-label for="direction" :value="__('languages.direction')" />
                            <select id="direction" name="direction" class="form-select" required>
                                <option value="rtl" @selected(old('direction') === 'rtl')>{{ __('common.direction_rtl') }}</option>
                                <option value="ltr" @selected(old('direction') === 'ltr')>{{ __('common.direction_ltr') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="form-check form-group">
                            <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', true))>
                            <x-input-label for="is_active" :value="__('languages.is_active')" />
                        </div>
                        <div class="form-check form-group">
                            <input id="is_default" name="is_default" type="checkbox" value="1" @checked(old('is_default'))>
                            <x-input-label for="is_default" :value="__('languages.is_default')" />
                        </div>
                    </div>
                    <x-primary-button>{{ __('languages.add_button') }}</x-primary-button>
                </form>
            </div>
        </div>
        @endpermission

        <div class="languages-grid">
            @forelse ($languages as $language)
                <div class="language-card {{ $language->is_active ? '' : 'language-card--inactive' }}">
                    <div class="language-card-flag">{{ strtoupper($language->code) }}</div>
                    <div class="language-card-body">
                        <div class="language-card-top">
                            <h3 class="language-card-name">{{ $language->native_name }}</h3>
                            @if ($language->is_default)
                                <span class="language-badge language-badge--default">{{ __('languages.default_badge') }}</span>
                            @endif
                        </div>
                        <p class="language-card-sub">{{ $language->name }}</p>
                        <div class="language-card-meta">
                            <span class="settings-badge">{{ $language->directionLabel() }}</span>
                            <span class="settings-badge settings-badge--muted">{{ $language->code }}</span>
                            @unless ($language->is_active)
                                <span class="settings-badge settings-badge--danger">{{ __('common.inactive') }}</span>
                            @endunless
                        </div>
                        @permission('settings.languages.view')
                        <div class="language-card-actions" style="margin-top:0.75rem;">
                            <a href="{{ route('settings.languages.translations', $language) }}" class="btn btn-secondary btn-sm">
                                {{ __('languages.manage_words') }}
                            </a>
                        </div>
                        @endpermission
                    </div>
                    @permission('settings.languages.edit')
                    <details class="language-card-edit">
                        <summary class="ref-type-edit-toggle">{{ __('common.edit') }}</summary>
                        <form method="POST" action="{{ route('settings.languages.update', $language) }}" class="ref-type-edit-form">
                            @csrf
                            @method('PUT')
                            <div class="form-group">
                                <x-input-label :value="__('common.name')" />
                                <x-text-input name="name" type="text" :value="$language->name" required />
                            </div>
                            <div class="form-group">
                                <x-input-label :value="__('languages.native_name')" />
                                <x-text-input name="native_name" type="text" :value="$language->native_name" required />
                            </div>
                            <div class="form-group">
                                <x-input-label :value="__('languages.code')" />
                                <x-text-input name="code" type="text" :value="$language->code" required />
                            </div>
                            <div class="form-group">
                                <x-input-label :value="__('languages.direction')" />
                                <select name="direction" class="form-select" required>
                                    <option value="rtl" @selected($language->direction === 'rtl')>{{ __('common.direction_rtl_short') }}</option>
                                    <option value="ltr" @selected($language->direction === 'ltr')>{{ __('common.direction_ltr_short') }}</option>
                                </select>
                            </div>
                            <div class="form-check form-group">
                                <input name="is_active" type="checkbox" value="1" @checked($language->is_active)>
                                <x-input-label :value="__('languages.is_active')" />
                            </div>
                            <div class="form-check form-group">
                                <input name="is_default" type="checkbox" value="1" @checked($language->is_default)>
                                <x-input-label :value="__('languages.is_default')" />
                            </div>
                            <div class="form-actions">
                                <x-primary-button>{{ __('common.save') }}</x-primary-button>
                                @unless ($language->is_default)
                                    @permission('settings.languages.delete')
                                    <button type="submit" formaction="{{ route('settings.languages.destroy', $language) }}" formmethod="POST" class="btn btn-danger" onclick="this.form.querySelector('[name=_method]').value='DELETE'; return confirm(@json(__('common.confirm_delete')))">
                                        @csrf
                                        @method('DELETE')
                                        {{ __('common.delete') }}
                                    </button>
                                    @endpermission
                                @endunless
                            </div>
                        </form>
                    </details>
                    @endpermission
                </div>
            @empty
                <div class="settings-empty" style="grid-column:1/-1;">
                    <p>{{ __('languages.empty') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
