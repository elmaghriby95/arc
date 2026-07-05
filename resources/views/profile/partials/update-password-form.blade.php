<section>
    <header class="profile-form-header">
        <h2 class="card-title">{{ __('profile.password_title') }}</h2>
        <p class="text-muted">{{ __('profile.password_desc') }}</p>
    </header>

    <form method="post" action="{{ route('password.update') }}">
        @csrf
        @method('put')

        <div class="form-group">
            <x-input-label for="update_password_current_password" :value="__('profile.current_password')" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" />
        </div>

        <div class="form-grid form-grid-2">
            <div class="form-group">
                <x-input-label for="update_password_password" :value="__('profile.new_password')" />
                <x-text-input id="update_password_password" name="password" type="password" autocomplete="new-password" />
                <x-input-error :messages="$errors->updatePassword->get('password')" />
            </div>

            <div class="form-group">
                <x-input-label for="update_password_password_confirmation" :value="__('profile.confirm_password')" />
                <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" />
                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" />
            </div>
        </div>

        <div class="form-actions">
            <x-primary-button>{{ __('profile.save') }}</x-primary-button>
            @if (session('status') === 'password-updated')
                <span class="text-success profile-save-notice">{{ __('profile.password_saved') }}</span>
            @endif
        </div>
    </form>
</section>
