<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('settings.roles.index') }}" class="settings-back-link">{{ __('common.back_to_roles') }}</a>
                <h2 class="page-title">{{ __('settings.roles.create_title') }}</h2>
                <p class="page-subtitle">{{ __('settings.roles.create_subtitle') }}</p>
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
                        <x-input-label for="name" :value="__('settings.roles.name')" />
                        <x-text-input id="name" name="name" type="text" :value="old('name')" required :placeholder="__('settings.roles.name_placeholder')" />
                        @error('name')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <x-input-label for="description" :value="__('settings.roles.description_optional')" />
                        <x-text-input id="description" name="description" type="text" :value="old('description')" :placeholder="__('settings.roles.description_placeholder')" />
                        @error('description')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                @include('settings.roles.partials.permissions-form', [
                    'permissionGroups' => $permissionGroups,
                    'selected' => old('permissions', []),
                ])
            </div>
            <div class="card-footer form-actions">
                <a href="{{ route('settings.roles.index') }}" class="btn btn-secondary">{{ __('common.cancel') }}</a>
                <x-primary-button>{{ __('settings.roles.save_button') }}</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
