<x-login-layout>
    <div class="login-form-header">
        <h2 class="login-form-title">{{ __('auth.password_change_title') }}</h2>
        <p class="login-form-desc">
            {{ $reason === 'expired' ? __('auth.password_change_expired') : __('auth.password_change_first') }}
        </p>
    </div>

    @if ($errors->any())
        <div class="login-alert login-alert-error">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.change.store') }}" class="login-form">
        @csrf

        @foreach ([
            ['name' => 'current_password', 'label' => __('auth.current_password'), 'autocomplete' => 'current-password'],
            ['name' => 'password', 'label' => __('auth.new_password'), 'autocomplete' => 'new-password'],
            ['name' => 'password_confirmation', 'label' => __('auth.confirm_password'), 'autocomplete' => 'new-password'],
        ] as $field)
            <div class="login-field">
                <label for="{{ $field['name'] }}" class="login-label">{{ $field['label'] }}</label>
                <div class="login-input-wrap">
                    <span class="login-input-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2zm10-10V7a4 4 0 0 0-8 0v4h8z"/>
                        </svg>
                    </span>
                    <input
                        id="{{ $field['name'] }}"
                        type="password"
                        name="{{ $field['name'] }}"
                        class="login-input"
                        required
                        autocomplete="{{ $field['autocomplete'] }}"
                        data-password-input
                        @if ($loop->first) autofocus @endif
                    >
                    <button type="button" class="login-password-toggle" data-password-toggle aria-label="{{ __('auth.show_password') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" data-icon-show>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" data-icon-hide style="display:none;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0 1 12 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 0 1 1.563-3.029m5.858 3.293a3 3 0 1 0 4.243 4.243m-4.243-4.243L3 3m3.878 3.878L21 21"/>
                        </svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->get($field['name'])" />
            </div>
        @endforeach

        <button type="submit" class="login-submit">
            <span>{{ __('auth.password_change_submit') }}</span>
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="login-logout">
        @csrf
        <button type="submit">{{ __('auth.logout') }}</button>
    </form>
</x-login-layout>
