<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('settings.index') }}" class="settings-back-link">{{ __('common.back') }}</a>
                <h2 class="page-title">{{ __('settings.ref_numbers.title') }}</h2>
                <p class="page-subtitle">{{ __('settings.ref_numbers.subtitle') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        <div class="ref-settings-info">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
            </svg>
            <p>{{ __('settings.ref_numbers.info') }}</p>
        </div>

        <form method="POST" action="{{ route('settings.reference-numbers.update') }}" class="ref-form">
            @csrf
            @method('PUT')

            <div class="ref-settings-grid">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('settings.ref_numbers.fields_title') }}</h3>
                    </div>
                    <div class="card-body ref-settings-toggles">
                        <label class="ref-settings-toggle">
                            <input type="checkbox" name="auto_assign_department" value="1" @checked(old('auto_assign_department', $settings->auto_assign_department))>
                            <span class="ref-settings-toggle-body">
                                <strong>{{ __('settings.ref_numbers.auto_assign_department') }}</strong>
                                <small>{{ __('settings.ref_numbers.auto_assign_department_desc') }}</small>
                            </span>
                        </label>

                        <label class="ref-settings-toggle">
                            <input type="checkbox" name="allow_previous_years" value="1" @checked(old('allow_previous_years', $settings->allow_previous_years))>
                            <span class="ref-settings-toggle-body">
                                <strong>{{ __('settings.ref_numbers.allow_previous_years') }}</strong>
                                <small>{{ __('settings.ref_numbers.allow_previous_years_desc') }}</small>
                            </span>
                        </label>

                        <label class="ref-settings-toggle">
                            <input type="checkbox" name="month_optional" value="1" @checked(old('month_optional', $settings->month_optional))>
                            <span class="ref-settings-toggle-body">
                                <strong>{{ __('settings.ref_numbers.month_optional') }}</strong>
                                <small>{{ __('settings.ref_numbers.month_optional_desc') }}</small>
                            </span>
                        </label>

                        <label class="ref-settings-toggle">
                            <input type="checkbox" name="original_document_number_optional" value="1" @checked(old('original_document_number_optional', $settings->original_document_number_optional))>
                            <span class="ref-settings-toggle-body">
                                <strong>{{ __('settings.ref_numbers.original_doc_optional') }}</strong>
                                <small>{{ __('settings.ref_numbers.original_doc_optional_desc') }}</small>
                            </span>
                        </label>

                        <label class="ref-settings-toggle">
                            <input type="checkbox" name="allow_free_format_reference" value="1" @checked(old('allow_free_format_reference', $settings->allow_free_format_reference))>
                            <span class="ref-settings-toggle-body">
                                <strong>{{ __('settings.ref_numbers.free_format') }}</strong>
                                <small>{{ __('settings.ref_numbers.free_format_desc') }}</small>
                            </span>
                        </label>

                        <label class="ref-settings-toggle">
                            <input type="checkbox" name="support_multilingual_characters" value="1" @checked(old('support_multilingual_characters', $settings->support_multilingual_characters))>
                            <span class="ref-settings-toggle-body">
                                <strong>{{ __('settings.ref_numbers.multilingual') }}</strong>
                                <small>{{ __('settings.ref_numbers.multilingual_desc') }}</small>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('settings.ref_numbers.operational_title') }}</h3>
                    </div>
                    <div class="card-body">
                        <label class="ref-settings-toggle ref-settings-toggle--block">
                            <input type="checkbox" name="operational_number_enabled" value="1" @checked(old('operational_number_enabled', $settings->operational_number_enabled))>
                            <span class="ref-settings-toggle-body">
                                <strong>{{ __('settings.ref_numbers.operational_enabled') }}</strong>
                                <small>{{ __('settings.ref_numbers.operational_enabled_desc') }}</small>
                            </span>
                        </label>

                        <div class="form-grid">
                            <div class="form-group">
                                <x-input-label for="operational_number_separator" :value="__('settings.ref_numbers.separator')" />
                                <x-text-input id="operational_number_separator" name="operational_number_separator" type="text" :value="old('operational_number_separator', $settings->operational_number_separator)" maxlength="5" required />
                                <p class="form-hint">{{ __('settings.ref_numbers.separator_hint') }}</p>
                            </div>
                            <div class="form-group">
                                <x-input-label for="operational_number_format" :value="__('settings.ref_numbers.format')" />
                                <x-text-input id="operational_number_format" name="operational_number_format" type="text" :value="old('operational_number_format', $settings->operational_number_format)" required />
                                <p class="form-hint">{{ __('settings.ref_numbers.format_hint') }}</p>
                            </div>
                        </div>

                        <div class="ref-settings-preview">
                            <span class="ref-settings-preview-label">{{ __('settings.ref_numbers.preview') }}</span>
                            <code>{{ $settings->formatPreview() }}</code>
                        </div>

                        <div class="form-group">
                            <x-input-label for="operational_number_disclaimer" :value="__('settings.ref_numbers.disclaimer')" />
                            <textarea id="operational_number_disclaimer" name="operational_number_disclaimer" rows="2" class="form-control">{{ old('operational_number_disclaimer', $settings->operational_number_disclaimer) }}</textarea>
                            <p class="form-hint">{{ __('settings.ref_numbers.disclaimer_hint') }}</p>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('settings.ref_numbers.control_title') }}</h3>
                    </div>
                    <div class="card-body ref-settings-toggles">
                        <label class="ref-settings-toggle">
                            <input type="checkbox" name="prevent_duplicate_numbers" value="1" @checked(old('prevent_duplicate_numbers', $settings->prevent_duplicate_numbers))>
                            <span class="ref-settings-toggle-body">
                                <strong>{{ __('settings.ref_numbers.prevent_duplicate') }}</strong>
                                <small>{{ __('settings.ref_numbers.prevent_duplicate_desc') }}</small>
                            </span>
                        </label>

                        <label class="ref-settings-toggle">
                            <input type="checkbox" name="audit_number_changes" value="1" @checked(old('audit_number_changes', $settings->audit_number_changes))>
                            <span class="ref-settings-toggle-body">
                                <strong>{{ __('settings.ref_numbers.audit_changes') }}</strong>
                                <small>{{ __('settings.ref_numbers.audit_changes_desc') }}</small>
                            </span>
                        </label>

                        <div class="ref-settings-note">
                            {!! __('settings.ref_numbers.duplicate_override_note') !!}
                        </div>
                    </div>
                </div>
            </div>

            @permission('settings.reference-numbers.edit')
            <div class="ref-settings-actions">
                <x-primary-button>{{ __('settings.ref_numbers.save') }}</x-primary-button>
            </div>
            @else
            <div class="ref-settings-note ref-settings-note--readonly">
                {{ __('settings.ref_numbers.readonly_notice') }}
            </div>
            @endpermission
        </form>
    </div>
</x-app-layout>
