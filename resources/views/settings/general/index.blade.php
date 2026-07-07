<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('settings.index') }}" class="settings-back-link">{{ __('common.back') }}</a>
                <h2 class="page-title">{{ __('settings.general.title') }}</h2>
                <p class="page-subtitle">{{ __('settings.general.subtitle') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        <div class="ref-settings-info">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
            </svg>
            <p>{{ __('settings.general.info') }}</p>
        </div>

        <form method="POST" action="{{ route('settings.general.update') }}" enctype="multipart/form-data" class="ref-form">
            @csrf
            @method('PUT')

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('settings.general.identity_title') }}</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <x-input-label for="app_name" :value="__('settings.general.app_name')" />
                        <x-text-input id="app_name" name="app_name" type="text" class="form-control" :value="old('app_name', $settings->app_name)" maxlength="191" />
                        <p class="form-hint">{{ __('settings.general.app_name_desc') }}</p>
                        <p class="form-hint">{{ __('settings.general.app_name_placeholder') }}: <strong>{{ config('app.name') }}</strong></p>
                        <x-input-error :messages="$errors->get('app_name')" />
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('settings.general.branding_title') }}</h3>
                </div>
                <div class="card-body">
                    <div class="form-grid form-grid--2">
                        <div class="form-group">
                            <x-input-label for="logo" :value="__('settings.general.logo')" />
                            @if ($settings->hasLogo())
                                <div class="general-brand-preview">
                                    <img src="{{ $settings->logoUrl() }}" alt="{{ $settings->appName() }}" class="general-brand-preview-img">
                                </div>
                                <label class="form-check">
                                    <input type="checkbox" name="remove_logo" value="1" class="form-check-input" {{ old('remove_logo') ? 'checked' : '' }}>
                                    <span>{{ __('settings.general.remove_logo') }}</span>
                                </label>
                            @endif
                            <input id="logo" name="logo" type="file" class="form-control" accept="image/jpeg,image/png,image/webp,image/svg+xml">
                            <p class="form-hint">{{ __('settings.general.logo_desc') }}</p>
                            <x-input-error :messages="$errors->get('logo')" />
                        </div>

                        <div class="form-group">
                            <x-input-label for="favicon" :value="__('settings.general.favicon')" />
                            @if ($settings->hasFavicon())
                                <div class="general-brand-preview general-brand-preview--favicon">
                                    <img src="{{ $settings->faviconUrl() }}" alt="" class="general-brand-preview-favicon">
                                </div>
                                <label class="form-check">
                                    <input type="checkbox" name="remove_favicon" value="1" class="form-check-input" {{ old('remove_favicon') ? 'checked' : '' }}>
                                    <span>{{ __('settings.general.remove_favicon') }}</span>
                                </label>
                            @endif
                            <input id="favicon" name="favicon" type="file" class="form-control" accept="image/jpeg,image/png,image/webp,image/x-icon,.ico">
                            <p class="form-hint">{{ __('settings.general.favicon_desc') }}</p>
                            <x-input-error :messages="$errors->get('favicon')" />
                        </div>
                    </div>

                    <h4 class="general-logo-sizes-title">{{ __('settings.general.logo_sizes_title') }}</h4>
                    <div class="form-grid form-grid--2 general-logo-sizes">
                        <div class="form-group">
                            <x-input-label for="logo_navbar_height" :value="__('settings.general.logo_navbar_height')" />
                            <x-text-input id="logo_navbar_height" name="logo_navbar_height" type="number" min="20" max="80" class="form-control" :value="old('logo_navbar_height', $settings->logo_navbar_height ?? 28)" required />
                            <p class="form-hint">{{ __('settings.general.logo_navbar_height_desc') }}</p>
                            <x-input-error :messages="$errors->get('logo_navbar_height')" />
                        </div>

                        <div class="form-group">
                            <x-input-label for="logo_navbar_max_width" :value="__('settings.general.logo_navbar_max_width')" />
                            <x-text-input id="logo_navbar_max_width" name="logo_navbar_max_width" type="number" min="40" max="240" class="form-control" :value="old('logo_navbar_max_width', $settings->logo_navbar_max_width ?? 100)" required />
                            <p class="form-hint">{{ __('settings.general.logo_navbar_max_width_desc') }}</p>
                            <x-input-error :messages="$errors->get('logo_navbar_max_width')" />
                        </div>

                        <div class="form-group">
                            <x-input-label for="logo_login_height" :value="__('settings.general.logo_login_height')" />
                            <x-text-input id="logo_login_height" name="logo_login_height" type="number" min="24" max="120" class="form-control" :value="old('logo_login_height', $settings->logo_login_height ?? 40)" required />
                            <p class="form-hint">{{ __('settings.general.logo_login_height_desc') }}</p>
                            <x-input-error :messages="$errors->get('logo_login_height')" />
                        </div>

                        <div class="form-group">
                            <x-input-label for="logo_login_max_width" :value="__('settings.general.logo_login_max_width')" />
                            <x-text-input id="logo_login_max_width" name="logo_login_max_width" type="number" min="60" max="320" class="form-control" :value="old('logo_login_max_width', $settings->logo_login_max_width ?? 120)" required />
                            <p class="form-hint">{{ __('settings.general.logo_login_max_width_desc') }}</p>
                            <x-input-error :messages="$errors->get('logo_login_max_width')" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('settings.general.contact_title') }}</h3>
                </div>
                <div class="card-body">
                    <div class="form-grid form-grid--2">
                        <div class="form-group">
                            <x-input-label for="support_email" :value="__('settings.general.support_email')" />
                            <x-text-input id="support_email" name="support_email" type="email" class="form-control" :value="old('support_email', $settings->support_email)" />
                            <x-input-error :messages="$errors->get('support_email')" />
                        </div>

                        <div class="form-group">
                            <x-input-label for="support_phone" :value="__('settings.general.support_phone')" />
                            <x-text-input id="support_phone" name="support_phone" type="text" class="form-control" :value="old('support_phone', $settings->support_phone)" />
                            <x-input-error :messages="$errors->get('support_phone')" />
                        </div>
                    </div>
                </div>
            </div>

            @permission('settings.general.edit')
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">{{ __('settings.general.save') }}</button>
                </div>
            @else
                <p class="form-hint">{{ __('settings.general.readonly') }}</p>
            @endpermission
        </form>
    </div>
</x-app-layout>
