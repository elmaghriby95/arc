<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('settings.languages.index') }}" class="settings-back-link">{{ __('translations.back_to_languages') }}</a>
                <h2 class="page-title">{{ __('translations.title') }} — {{ $language->native_name }}</h2>
                <p class="page-subtitle">{{ __('translations.subtitle') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        @permission('settings.languages.edit')
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('translations.add_word') }}</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('settings.languages.translations.keys.store', $language) }}">
                    @csrf
                    <div class="form-grid">
                        <div class="form-group">
                            <x-input-label for="group" :value="__('translations.group')" />
                            <x-text-input id="group" name="group" type="text" :value="old('group', 'nav')" required placeholder="nav" />
                            <small class="form-hint">{{ __('translations.group_hint') }}</small>
                        </div>
                        <div class="form-group">
                            <x-input-label for="key" :value="__('translations.key')" />
                            <x-text-input id="key" name="key" type="text" :value="old('key')" required placeholder="documents" />
                            <small class="form-hint">{{ __('translations.key_hint') }}</small>
                        </div>
                        <div class="form-group">
                            <x-input-label for="value" :value="__('translations.value')" />
                            <x-text-input id="value" name="value" type="text" :value="old('value')" required />
                        </div>
                        <div class="form-group">
                            <x-input-label for="description" :value="__('translations.description')" />
                            <x-text-input id="description" name="description" type="text" :value="old('description')" />
                        </div>
                    </div>
                    <p class="form-hint" style="margin-bottom:1rem;">{{ __('translations.usage_hint') }}</p>
                    <x-primary-button>{{ __('common.add') }}</x-primary-button>
                </form>
            </div>
        </div>
        @endpermission

        <form method="POST" action="{{ route('settings.languages.translations.update', $language) }}">
            @csrf
            @forelse ($groupedKeys as $group => $keys)
                <div class="card" style="margin-top:1.5rem;">
                    <div class="card-header">
                        <h3 class="card-title">{{ $group }}</h3>
                        <span class="settings-badge">{{ $keys->count() }}</span>
                    </div>
                    <div class="card-body">
                        <div class="translations-grid">
                            @foreach ($keys as $translationKey)
                                @php
                                    $currentValue = $translationKey->translations->first()?->value ?? '';
                                @endphp
                                <div class="translation-row">
                                    <div class="translation-row-meta">
                                        <code class="translation-code">{{ $translationKey->group }}.{{ $translationKey->key }}</code>
                                        @if ($translationKey->description)
                                            <small class="text-muted">{{ $translationKey->description }}</small>
                                        @endif
                                    </div>
                                    <div class="translation-row-input">
                                        <input
                                            type="text"
                                            name="translations[{{ $translationKey->id }}]"
                                            value="{{ old('translations.'.$translationKey->id, $currentValue) }}"
                                            class="form-input"
                                            @disabled(! auth()->user()->hasPermission('settings.languages.edit'))
                                        >
                                    </div>
                                    @permission('settings.languages.edit')
                                    <div class="translation-row-actions">
                                        <form method="POST" action="{{ route('settings.languages.translations.keys.destroy', [$language, $translationKey]) }}" onsubmit="return confirm(@json(__('common.confirm_delete')))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">{{ __('common.delete') }}</button>
                                        </form>
                                    </div>
                                    @endpermission
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @empty
                <div class="settings-empty" style="margin-top:1.5rem;">
                    <p>{{ __('translations.empty') }}</p>
                </div>
            @endforelse

            @if ($groupedKeys->isNotEmpty())
                @permission('settings.languages.edit')
                <div class="form-actions" style="margin-top:1.5rem;">
                    <x-primary-button>{{ __('translations.save_all') }}</x-primary-button>
                </div>
                @endpermission
            @endif
        </form>
    </div>
</x-app-layout>
