<x-guest-layout>
    <p class="text-muted">{{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}</p>

    <x-auth-session-status :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="form-group">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" />
        </div>
        <div class="form-actions form-actions-end">
            <x-primary-button>{{ __('Email Password Reset Link') }}</x-primary-button>
        </div>
    </form>
</x-guest-layout>
