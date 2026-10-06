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

        @php
            $postedTranslations = is_array(old('translations')) ? old('translations') : [];
            $postedJson = old('translations_json');

            if (is_string($postedJson)) {
                $decodedPosted = json_decode($postedJson, true);

                if (is_array($decodedPosted)) {
                    $postedTranslations = $decodedPosted;
                }
            }
        @endphp

        <form method="POST" action="{{ route('settings.languages.translations.update', $language) }}" id="translations-form">
            @csrf
            <input type="hidden" name="translations_json" id="translations-json" value="">
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
                                    $postedKey = array_key_exists($translationKey->id, $postedTranslations)
                                        || array_key_exists((string) $translationKey->id, $postedTranslations);
                                    $currentValue = $postedKey
                                        ? (string) ($postedTranslations[$translationKey->id] ?? $postedTranslations[(string) $translationKey->id])
                                        : ($translationKey->translations->first()?->value ?? '');
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
                                            data-translation-id="{{ $translationKey->id }}"
                                            value="{{ $currentValue }}"
                                            class="form-control"
                                            aria-label="{{ $translationKey->group }}.{{ $translationKey->key }}"
                                            @disabled(! auth()->user()->hasPermission('settings.languages.edit'))
                                        >
                                    </div>
                                    @permission('settings.languages.edit')
                                    <div class="translation-row-actions">
                                        <button
                                            type="submit"
                                            class="btn btn-danger btn-sm"
                                            form="delete-translation-{{ $translationKey->id }}"
                                            onclick="return confirm(@json(__('common.confirm_delete')))"
                                        >{{ __('common.delete') }}</button>
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

        @permission('settings.languages.edit')
            @foreach ($groupedKeys as $keys)
                @foreach ($keys as $translationKey)
                    <form id="delete-translation-{{ $translationKey->id }}" method="POST" action="{{ route('settings.languages.translations.keys.destroy', [$language, $translationKey]) }}" hidden>
                        @csrf
                        @method('DELETE')
                    </form>
                @endforeach
            @endforeach
        @endpermission

        @permission('settings.languages.edit')
            <script>
                document.getElementById('translations-form')?.addEventListener('submit', function () {
                    const payload = {};

                    this.querySelectorAll('[data-translation-id]:not(:disabled)').forEach(function (input) {
                        payload[input.getAttribute('data-translation-id')] = input.value;
                    });

                    const field = document.getElementById('translations-json');

                    if (field) {
                        field.value = JSON.stringify(payload);
                    }
                });
            </script>
        @endpermission
    </div>
</x-app-layout>
