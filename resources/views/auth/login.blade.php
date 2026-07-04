<x-login-layout>
    <div class="login-form-header">
        <h2 class="login-form-title">مرحباً بعودتك</h2>
        <p class="login-form-desc">سجّل دخولك للوصول إلى لوحة التحكم وإدارة الأرشيف</p>
    </div>

    <x-auth-session-status :status="session('status')" />

    @if ($errors->any())
        <div class="login-alert login-alert-error">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="login-form">
        @csrf

        <div class="login-field">
            <label for="email" class="login-label">{{ __('Email') }}</label>
            <div class="login-input-wrap">
                <span class="login-input-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 0 0 2.22 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z"/>
                    </svg>
                </span>
                <input
                    id="email"
                    type="email"
                    name="email"
                    class="login-input"
                    value="{{ old('email') }}"
                    placeholder="example@domain.com"
                    required
                    autofocus
                    autocomplete="username"
                >
            </div>
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="login-field">
            <label for="password" class="login-label">{{ __('Password') }}</label>
            <div class="login-input-wrap">
                <span class="login-input-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2zm10-10V7a4 4 0 0 0-8 0v4h8z"/>
                    </svg>
                </span>
                <input
                    id="password"
                    type="password"
                    name="password"
                    class="login-input"
                    placeholder="••••••••"
                    required
                    autocomplete="current-password"
                    data-password-input
                >
                <button type="button" class="login-password-toggle" data-password-toggle aria-label="إظهار كلمة المرور">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" data-icon-show>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" data-icon-hide style="display:none;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0 1 12 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 0 1 1.563-3.029m5.858 3.293a3 3 0 1 0 4.243 4.243m-4.243-4.243L3 3m3.878 3.878L21 21"/>
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="login-options">
            <label class="login-remember">
                <input id="remember_me" type="checkbox" name="remember">
                <span>{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="login-forgot">{{ __('Forgot your password?') }}</a>
            @endif
        </div>

        <button type="submit" class="login-submit">
            <span>{{ __('Log in') }}</span>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
            </svg>
        </button>
    </form>

    @if (Route::has('register'))
        <p class="login-register">
            ليس لديك حساب؟
            <a href="{{ route('register') }}">{{ __('Register') }}</a>
        </p>
    @endif
</x-login-layout>
