<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('settings.index') }}" class="settings-back-link">{{ __('common.back') }}</a>
                <h2 class="page-title">{{ __('settings.qr_code.title') }}</h2>
                <p class="page-subtitle">{{ __('settings.qr_code.subtitle') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        <div class="ref-settings-info">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
            </svg>
            <p>{{ __('settings.qr_code.info') }}</p>
        </div>

        <form method="POST" action="{{ route('settings.qr-code.update') }}" class="ref-form">
            @csrf
            @method('PUT')

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('settings.qr_code.sizes_title') }}</h3>
                </div>
                <div class="card-body">
                    <div class="form-grid form-grid--2">
                        <div class="form-group">
                            <x-input-label for="display_size" :value="__('settings.qr_code.display_size')" />
                            <x-text-input id="display_size" name="display_size" type="number" min="80" max="500" step="1" class="form-control" :value="old('display_size', $settings->display_size)" required />
                            <p class="form-hint">{{ __('settings.qr_code.display_size_desc') }}</p>
                            <x-input-error :messages="$errors->get('display_size')" />
                        </div>

                        <div class="form-group">
                            <x-input-label for="print_size" :value="__('settings.qr_code.print_size')" />
                            <x-text-input id="print_size" name="print_size" type="number" min="80" max="500" step="1" class="form-control" :value="old('print_size', $settings->print_size)" required />
                            <p class="form-hint">{{ __('settings.qr_code.print_size_desc') }}</p>
                            <x-input-error :messages="$errors->get('print_size')" />
                        </div>
                    </div>

                    <p class="form-hint">{{ __('settings.qr_code.size_hint') }}</p>
                </div>
            </div>

            @permission('settings.qr-code.edit')
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">{{ __('settings.qr_code.save') }}</button>
                </div>
            @else
                <p class="form-hint">{{ __('settings.qr_code.readonly') }}</p>
            @endpermission
        </form>
    </div>
</x-app-layout>
