<section>
    <header style="margin-bottom:1rem;">
        <h2 class="card-title">{{ __('Delete Account') }}</h2>
        <p class="text-muted">{{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}</p>
    </header>

    <button type="button" class="btn btn-danger" data-modal-open="confirm-user-deletion">{{ __('Delete Account') }}</button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()">
        <form method="post" action="{{ route('profile.destroy') }}">
            @csrf
            @method('delete')

            <h2 class="card-title">{{ __('Are you sure you want to delete your account?') }}</h2>
            <p class="text-muted">{{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}</p>

            <div class="form-group">
                <x-input-label for="password" value="{{ __('Password') }}" class="sr-only" />
                <x-text-input id="password" name="password" type="password" placeholder="{{ __('Password') }}" />
                <x-input-error :messages="$errors->userDeletion->get('password')" />
            </div>

            <div class="form-actions form-actions-end">
                <x-secondary-button type="button" data-modal-close>{{ __('Cancel') }}</x-secondary-button>
                <x-danger-button>{{ __('Delete Account') }}</x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
