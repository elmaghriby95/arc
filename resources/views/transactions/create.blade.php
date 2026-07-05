<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('transactions.index') }}" class="settings-back-link">{{ __('common.back_to_transactions') }}</a>
                <h2 class="page-title">{{ __('transactions.create_title') }}</h2>
            </div>
        </div>
    </x-slot>

    @once
        @php($txCreateCss = resource_path('css/transaction-create.css'))
        @if (is_readable($txCreateCss))
            <style>{!! file_get_contents($txCreateCss) !!}</style>
        @endif
    @endonce

    <div class="txnw-page">
        <div class="txnw-wrap">
            @if (! $initialStatus)
                <div class="txnw-alert txnw-alert--warn txnw-alert--center">
                    {!! __('transactions.statuses_required_html', ['url' => route('settings.transaction-statuses.index')]) !!}
                </div>
            @else
                <div class="txnw-hero">
                    <div class="txnw-hero-top">
                        <div>
                            <span class="txnw-hero-tag">{{ __('transactions.create_tag') }}</span>
                            <h3 class="txnw-hero-title">{{ __('transactions.create_hero_title') }}</h3>
                            <p class="txnw-hero-desc">{{ __('transactions.create_hero_desc') }}</p>
                        </div>
                        <div class="txnw-hero-status">
                            <span class="txnw-hero-status-label">{{ __('transactions.initial_status') }}</span>
                            <x-transaction-status-badge :status="$initialStatus" />
                        </div>
                    </div>

                    @if ($workflow->isNotEmpty())
                        <div class="txnw-workflow" aria-label="{{ __('transactions.workflow_path') }}">
                            <span class="txnw-workflow-title">{{ __('transactions.workflow_after_create') }}</span>
                            <div class="txnw-workflow-track">
                                @foreach ($workflow as $step)
                                    <div class="txnw-workflow-step {{ $step->id === $initialStatus->id ? 'is-current' : '' }} {{ $step->sort_order < ($initialStatus->sort_order ?? 0) ? 'is-past' : '' }}">
                                        <span class="txnw-workflow-dot" style="--step-color: {{ $step->color ?? '#6366f1' }}"></span>
                                        <span class="txnw-workflow-name">{{ $step->name }}</span>
                                    </div>
                                    @unless ($loop->last)
                                        <span class="txnw-workflow-connector" aria-hidden="true"></span>
                                    @endunless
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                @if ($errors->any())
                    <div class="txnw-alert txnw-alert--error" role="alert">
                        <strong>{{ __('transactions.validation_heading') }}</strong>
                        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif

                <form method="POST"
                      action="{{ route('transactions.store') }}"
                      enctype="multipart/form-data"
                      class="txnw-form"
                      data-txn-create-form
                      data-scan-agent-url="{{ config('scan.agent_url') }}"
                      data-scan-failed="{{ __('transactions.scan_failed') }}"
                      data-scan-failed-detail="{{ __('transactions.scan_failed_detail', ['message' => ':message']) }}"
                      data-ref-config='@json($referenceFormConfig)'
                      data-txn-i18n='@json($txnCreateI18n)'
                      data-initial-step="{{ $errors->has('folder_id') ? 2 : 1 }}">
                    @csrf

                    <div class="txnw-card">
                        <nav class="txnw-steps-bar" aria-label="{{ __('transactions.create_steps') }}">
                            <button type="button" class="txnw-step-pill is-active" data-go-step="1">
                                <span class="txnw-step-pill-num">1</span>
                                <span class="txnw-step-pill-text">{{ __('transactions.step_basic') }}</span>
                            </button>
                            <span class="txnw-step-connector" aria-hidden="true"></span>
                            <button type="button" class="txnw-step-pill" data-go-step="2">
                                <span class="txnw-step-pill-num">2</span>
                                <span class="txnw-step-pill-text">{{ __('transactions.step_folder') }}</span>
                            </button>
                            <span class="txnw-step-connector" aria-hidden="true"></span>
                            <button type="button" class="txnw-step-pill" data-go-step="3">
                                <span class="txnw-step-pill-num">3</span>
                                <span class="txnw-step-pill-text">{{ __('transactions.step_documents') }}</span>
                            </button>
                        </nav>

                        <div class="txnw-card-body">
                            <section class="txnw-step is-visible" data-step="1">
                                <header class="txnw-step-head">
                                    <h3>{{ __('transactions.step_basic') }}</h3>
                                    <p>{{ __('transactions.step_basic_desc') }}</p>
                                </header>

                                <div class="txnw-box">
                                    <div class="txnw-field txnw-field--full">
                                        <label for="title">{{ __('transactions.title_label') }} <span class="txnw-req">*</span></label>
                                        <input type="text" id="title" name="title" class="txnw-input" value="{{ old('title') }}" required autofocus placeholder="{{ __('transactions.title_placeholder') }}">
                                    </div>
                                    <div class="txnw-row txnw-row--3">
                                        <div class="txnw-field">
                                            <label for="department_id">{{ __('common.org_unit') }} <span class="txnw-req">*</span></label>
                                            @include('settings.partials.org-unit-select', [
                                                'orgUnits' => $orgUnits,
                                                'selected' => old('department_id', $defaultDepartmentId ?? null),
                                                'selectClass' => 'txnw-input txnw-select',
                                                'showHint' => false,
                                                'required' => true,
                                                'placeholder' => __('common.choose_org_unit'),
                                            ])
                                        </div>
                                        <div class="txnw-field">
                                            <label for="transaction_type_id">{{ __('common.transaction_type') }}</label>
                                            <select id="transaction_type_id" name="transaction_type_id" class="txnw-input txnw-select">
                                                <option value="">{{ __('common.optional_dash') }}</option>
                                                @foreach ($transactionTypes as $type)
                                                    <option value="{{ $type->id }}" data-type-code="{{ $type->code }}" @selected(old('transaction_type_id') == $type->id)>{{ $type->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="txnw-field">
                                            <label for="transaction_date">{{ __('transactions.date') }}</label>
                                            <input type="date" id="transaction_date" name="transaction_date" class="txnw-input" value="{{ old('transaction_date', now()->format('Y-m-d')) }}">
                                        </div>
                                    </div>
                                </div>

                                <footer class="txnw-step-foot">
                                    <span></span>
                                    <button type="button" class="txnw-btn txnw-btn--primary txnw-btn--lg" data-next-step>{{ __('common.next') }}</button>
                                </footer>
                            </section>

                            <section class="txnw-step" data-step="2">
                                <header class="txnw-step-head">
                                    <h3>{{ __('transactions.step_folder') }}</h3>
                                    <p>{{ __('transactions.step_folder_desc') }}</p>
                                </header>

                                <div class="txnw-box {{ $errors->has('folder_id') ? 'txnw-box--error' : '' }}">
                                    <div class="txnw-field txnw-field--full">
                                        <label>{{ __('common.folder') }} <span class="txnw-req">*</span></label>
                                        <div class="txnw-folder-selected is-empty" data-folder-selected>
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h5l2 2h9a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                                            <span data-folder-selected-name>{{ __('transactions.folder_not_selected') }}</span>
                                        </div>
                                        @include('transactions.partials.folder-tree-picker', [
                                            'folders' => $folders,
                                            'folderTree' => $folderTree,
                                            'selected' => old('folder_id'),
                                            'departmentBreadcrumbs' => $departmentBreadcrumbs ?? [],
                                        ])
                                    </div>
                                    <div class="txnw-row">
                                        <div class="txnw-field">
                                            <label for="description">{{ __('common.description') }}</label>
                                            <textarea id="description" name="description" class="txnw-input txnw-textarea" rows="3" placeholder="{{ __('transactions.description_placeholder') }}">{{ old('description') }}</textarea>
                                        </div>
                                        <div class="txnw-field">
                                            <label for="notes">{{ __('common.notes') }}</label>
                                            <textarea id="notes" name="notes" class="txnw-input txnw-textarea" rows="3" placeholder="{{ __('transactions.notes_placeholder') }}">{{ old('notes') }}</textarea>
                                        </div>
                                    </div>
                                </div>

                                <footer class="txnw-step-foot">
                                    <button type="button" class="txnw-btn txnw-btn--ghost txnw-btn--lg" data-prev-step>{{ __('common.previous') }}</button>
                                    <button type="button" class="txnw-btn txnw-btn--primary txnw-btn--lg" data-next-step>{{ __('common.next') }}</button>
                                </footer>
                            </section>

                            <section class="txnw-step" data-step="3">
                                <header class="txnw-step-head">
                                    <h3>{{ __('transactions.step_documents') }}</h3>
                                    <p>{{ __('transactions.step_documents_desc') }}</p>
                                </header>

                                <div class="txnw-box">
                                    @if ($referenceSettings->operational_number_enabled && $referenceSettings->operational_number_disclaimer)
                                        <p class="txnw-hint txnw-hint--warn">{{ $referenceSettings->operational_number_disclaimer }}</p>
                                    @endif

                                    <div class="txnw-upload" data-txn-create-dropzone>
                                        <input type="file" name="files[]" class="txnw-upload-input" data-txn-file-input multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp,.bmp">
                                        <div class="txnw-upload-empty" data-txn-dropzone-content>
                                            <div class="txnw-upload-icon">
                                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                                            </div>
                                            <span class="txnw-upload-title">{{ __('transactions.upload_drag') }}</span>
                                            <span class="txnw-upload-sub">{{ __('common.or') }}</span>
                                            <div class="txnw-upload-actions">
                                                <button type="button" class="txnw-btn txnw-btn--outline" data-txn-browse>{{ __('transactions.upload_browse') }}</button>
                                                <button type="button"
                                                        class="txnw-btn txnw-btn--primary"
                                                        data-txn-scan
                                                        data-scan-idle-label="{{ __('transactions.scan_direct') }}"
                                                        data-scanning-label="{{ __('transactions.scanning') }}">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m2 0v12a2 2 0 01-2 2H9a2 2 0 01-2-2V7h10z"/><path stroke-linecap="round" d="M9 12h6M9 16h4"/></svg>
                                                    {{ __('transactions.scan_direct') }}
                                                </button>
                                            </div>
                                            <small>{{ __('transactions.upload_formats') }} · {{ __('transactions.scan_agent_hint') }}</small>
                                        </div>
                                        <div class="txnw-upload-queue is-hidden" data-txn-queue>
                                            <ul class="txnw-upload-list" data-txn-file-list></ul>
                                        </div>
                                    </div>
                                    <p class="txnw-hint">{{ __('transactions.upload_draft_hint') }}</p>
                                </div>

                                <footer class="txnw-step-foot">
                                    <button type="button" class="txnw-btn txnw-btn--ghost txnw-btn--lg" data-prev-step>{{ __('common.previous') }}</button>
                                    <button type="submit" class="txnw-btn txnw-btn--success txnw-btn--lg txnw-btn--submit" {{ $folders->isEmpty() ? 'disabled' : '' }}>
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" d="M5 13l4 4L19 7"/></svg>
                                        {{ __('transactions.create_submit') }}
                                    </button>
                                </footer>
                            </section>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>

    @once
        @php($arcScanJs = resource_path('js/arc-scan.js'))
        @if (is_readable($arcScanJs))
            <script>{!! file_get_contents($arcScanJs) !!}</script>
        @endif
        @php($txCreateJs = resource_path('js/transaction-create.js'))
        @if (is_readable($txCreateJs))
            <script>{!! file_get_contents($txCreateJs) !!}</script>
        @endif
    @endonce
</x-app-layout>
