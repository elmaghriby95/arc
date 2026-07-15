<section>
    <header class="profile-form-header">
        <h2 class="card-title">{{ __('profile.info_title') }}</h2>
        <p class="text-muted">{{ __('profile.info_desc') }}</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}">
        @csrf
        @method('patch')

        <div class="form-grid form-grid-2">
            <div class="form-group">
                <x-input-label for="name" :value="__('profile.name')" />
                <x-text-input id="name" name="name" type="text" :value="old('name', $user->name)" required autofocus autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" />
            </div>

            <div class="form-group">
                <x-input-label for="email" :value="__('profile.email')" />
                <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)" required autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" />
            </div>
        </div>

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="profile-verify-notice">
                <p class="text-muted">
                    {{ __('profile.email_unverified_notice') }}
                    <button form="send-verification" class="btn btn-link">{{ __('profile.resend_verification') }}</button>
                </p>

                @if (session('status') === 'verification-link-sent')
                    <p class="text-success">{{ __('profile.verification_sent') }}</p>
                @endif
            </div>
        @endif

        <div class="form-actions">
            @permission('profile.edit')
                <x-primary-button>{{ __('profile.save') }}</x-primary-button>
            @endpermission
            @if (session('status') === 'profile-updated')
                <span class="text-success profile-save-notice">{{ __('profile.saved') }}</span>
            @endif
        </div>
    </form>
</section>
