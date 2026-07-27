<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('settings.index') }}" class="settings-back-link">{{ __('common.back') }}</a>
                <h2 class="page-title">{{ __('settings.database_clean.title') }}</h2>
                <p class="page-subtitle">{{ __('settings.database_clean.subtitle') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        <div class="ref-settings-info ref-settings-info--danger">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
            <div>
                <p class="mb-2">{{ __('settings.database_clean.warning') }}</p>
                <p class="mb-0">{{ __('settings.database_clean.kept') }}</p>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">{{ __('settings.database_clean.preview_title') }}</h3>
            </div>
            <div class="card-body">
                <div class="db-clean-counts">
                    @foreach ([
                        'transactions' => __('settings.database_clean.count_transactions'),
                        'transaction_attachments' => __('settings.database_clean.count_attachments'),
                        'documents' => __('settings.database_clean.count_documents'),
                        'folders' => __('settings.database_clean.count_folders'),
                        'departments' => __('settings.database_clean.count_departments'),
                        'document_types' => __('settings.database_clean.count_document_types'),
                        'transaction_types' => __('settings.database_clean.count_transaction_types'),
                        'lending_requests' => __('settings.database_clean.count_lending'),
                        'audit_logs' => __('settings.database_clean.count_audits'),
                        'notifications' => __('settings.database_clean.count_notifications'),
                    ] as $table => $label)
                        <div class="db-clean-count">
                            <span class="db-clean-count-value">{{ number_format($counts[$table] ?? 0) }}</span>
                            <span class="db-clean-count-label">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('settings.database-clean.destroy') }}" class="card card--danger" onsubmit="return confirm(@js(__('settings.database_clean.js_confirm')));">
            @csrf
            @method('DELETE')

            <div class="card-header">
                <h3 class="card-title">{{ __('settings.database_clean.confirm_title') }}</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <x-input-label for="confirmation" :value="__('settings.database_clean.confirmation_label', ['phrase' => $confirmationPhrase])" />
                    <x-text-input id="confirmation" name="confirmation" type="text" class="form-control" :value="old('confirmation')" autocomplete="off" required />
                    <p class="form-hint">{{ __('settings.database_clean.confirmation_hint', ['phrase' => $confirmationPhrase]) }}</p>
                    <x-input-error :messages="$errors->get('confirmation')" />
                </div>

                <div class="form-group">
                    <x-input-label for="password" :value="__('settings.database_clean.password_label')" />
                    <x-text-input id="password" name="password" type="password" class="form-control" autocomplete="current-password" required />
                    <x-input-error :messages="$errors->get('password')" />
                </div>
            </div>
            <div class="card-footer form-actions">
                <a href="{{ route('settings.index') }}" class="btn btn-secondary">{{ __('common.cancel') }}</a>
                <button type="submit" class="btn btn-danger">{{ __('settings.database_clean.submit') }}</button>
            </div>
        </form>
    </div>
</x-app-layout>
