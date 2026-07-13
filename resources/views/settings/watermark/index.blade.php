<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('settings.index') }}" class="settings-back-link">{{ __('common.back') }}</a>
                <h2 class="page-title">{{ __('settings.watermark.title') }}</h2>
                <p class="page-subtitle">{{ __('settings.watermark.subtitle') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        <div class="ref-settings-info">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
            </svg>
            <p>{{ __('settings.watermark.info') }}</p>
        </div>

        <form method="POST" action="{{ route('settings.watermark.update') }}" class="ref-form">
            @csrf
            @method('PUT')

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('settings.watermark.general_title') }}</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="is_enabled" value="1" @checked(old('is_enabled', $settings->is_enabled))>
                            <span>{{ __('settings.watermark.is_enabled') }}</span>
                        </label>
                    </div>

                    <div class="form-grid form-grid--2">
                        <div class="form-group">
                            <x-input-label for="opacity" :value="__('settings.watermark.opacity')" />
                            <x-text-input id="opacity" name="opacity" type="number" min="5" max="60" step="1" class="form-control" :value="old('opacity', $settings->opacity)" required />
                            <p class="form-hint">{{ __('settings.watermark.opacity_desc') }}</p>
                            <x-input-error :messages="$errors->get('opacity')" />
                        </div>
                        <div class="form-group">
                            <x-input-label for="font_size" :value="__('settings.watermark.font_size')" />
                            <x-text-input id="font_size" name="font_size" type="number" min="10" max="72" step="1" class="form-control" :value="old('font_size', $settings->font_size)" required />
                            <x-input-error :messages="$errors->get('font_size')" />
                        </div>
                        <div class="form-group">
                            <x-input-label for="angle" :value="__('settings.watermark.angle')" />
                            <x-text-input id="angle" name="angle" type="number" min="-90" max="90" step="1" class="form-control" :value="old('angle', $settings->angle)" required />
                            <p class="form-hint">{{ __('settings.watermark.angle_desc') }}</p>
                            <x-input-error :messages="$errors->get('angle')" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('settings.watermark.elements_title') }}</h3>
                </div>
                <div class="card-body">
                    <div class="form-grid form-grid--2">
                        @foreach ([
                            'show_center_text' => __('settings.watermark.show_center_text'),
                            'show_footer' => __('settings.watermark.show_footer'),
                            'show_qr_code' => __('settings.watermark.show_qr_code'),
                        ] as $field => $label)
                            <div class="form-group">
                                <label class="inline-flex items-center gap-2">
                                    <input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $settings->{$field}))>
                                    <span>{{ $label }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('settings.watermark.fields_title') }}</h3>
                </div>
                <div class="card-body">
                    <div class="form-grid form-grid--2">
                        @foreach ([
                            'show_user_name' => __('settings.watermark.show_user_name'),
                            'show_user_id' => __('settings.watermark.show_user_id'),
                            'show_department' => __('settings.watermark.show_department'),
                            'show_datetime' => __('settings.watermark.show_datetime'),
                            'show_action_type' => __('settings.watermark.show_action_type'),
                            'show_transaction_id' => __('settings.watermark.show_transaction_id'),
                        ] as $field => $label)
                            <div class="form-group">
                                <label class="inline-flex items-center gap-2">
                                    <input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $settings->{$field}))>
                                    <span>{{ $label }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('settings.watermark.apply_title') }}</h3>
                </div>
                <div class="card-body">
                    <div class="form-grid form-grid--2">
                        @foreach ([
                            'apply_on_view' => __('settings.watermark.apply_on_view'),
                            'apply_on_download' => __('settings.watermark.apply_on_download'),
                            'apply_on_print' => __('settings.watermark.apply_on_print'),
                        ] as $field => $label)
                            <div class="form-group">
                                <label class="inline-flex items-center gap-2">
                                    <input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $settings->{$field}))>
                                    <span>{{ $label }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            @permission('settings.watermark.edit')
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">{{ __('settings.watermark.save') }}</button>
                </div>
            @else
                <p class="form-hint">{{ __('settings.watermark.readonly') }}</p>
            @endpermission
        </form>

        <div class="card" style="margin-top: 1.5rem;">
            <div class="card-header">
                <h3 class="card-title">{{ __('settings.watermark.lookup_title') }}</h3>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('settings.watermark.index') }}" class="form-grid form-grid--2">
                    <div class="form-group">
                        <x-input-label for="transaction_id" :value="__('settings.watermark.transaction_id')" />
                        <x-text-input id="transaction_id" name="transaction_id" type="text" class="form-control" :value="old('transaction_id', $transactionIdQuery)" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" />
                    </div>
                    <div class="form-group" style="display:flex;align-items:flex-end;">
                        <button type="submit" class="btn btn-secondary">{{ __('settings.watermark.lookup_search') }}</button>
                    </div>
                </form>

                @if ($transactionIdQuery !== '')
                    @if ($lookup)
                        <div class="table-wrapper" style="margin-top: 1rem;">
                            <table class="table">
                                <tbody>
                                    <tr><th>{{ __('settings.watermark.transaction_id') }}</th><td><code>{{ $lookup->transaction_id }}</code></td></tr>
                                    <tr><th>{{ __('settings.watermark.audit_user') }}</th><td>{{ $lookup->user?->name ?? '—' }} (ID: {{ $lookup->user_id }})</td></tr>
                                    <tr><th>{{ __('settings.watermark.audit_department') }}</th><td>{{ $lookup->user?->department?->name ?? '—' }}</td></tr>
                                    <tr><th>{{ __('settings.watermark.audit_document') }}</th><td>{{ $lookup->attachment?->displayName() ?? ('#'.$lookup->attachment_id) }}</td></tr>
                                    <tr><th>{{ __('settings.watermark.audit_version') }}</th><td>{{ $lookup->document_version ?? '—' }}</td></tr>
                                    <tr><th>{{ __('settings.watermark.audit_action') }}</th><td>{{ strtoupper($lookup->action_type) }}</td></tr>
                                    <tr><th>{{ __('settings.watermark.audit_status') }}</th><td>{{ $lookup->status }}{{ $lookup->failure_reason ? ' — '.$lookup->failure_reason : '' }}</td></tr>
                                    <tr><th>{{ __('settings.watermark.audit_watermark') }}</th><td>{{ $lookup->watermark_applied ? __('common.yes') : __('common.no') }}</td></tr>
                                    <tr><th>{{ __('settings.watermark.audit_ip') }}</th><td>{{ $lookup->ip_address ?? '—' }}</td></tr>
                                    <tr><th>{{ __('settings.watermark.audit_ua') }}</th><td style="word-break:break-all;">{{ $lookup->user_agent ?? '—' }}</td></tr>
                                    <tr><th>{{ __('settings.watermark.audit_session') }}</th><td><code>{{ $lookup->session_id ?? '—' }}</code></td></tr>
                                    <tr><th>{{ __('settings.watermark.audit_at') }}</th><td>{{ $lookup->created_at?->format('Y-m-d H:i:s') }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="form-hint" style="margin-top: 1rem;">{{ __('settings.watermark.lookup_not_found') }}</p>
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
