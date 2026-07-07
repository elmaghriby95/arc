<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                @if (request('from') === 'transaction' && auth()->user()?->hasPermission('transactions.view'))
                    <a href="{{ route('transactions.show', $attachment->transaction) }}" class="settings-back-link">{{ __('common.back_to_transaction') }}</a>
                @else
                    <a href="{{ route('documents.index') }}" class="settings-back-link">{{ __('common.back_to_documents') }}</a>
                @endif
                <h2 class="page-title">{{ $attachment->displayName() }}</h2>
            </div>
            <div class="form-actions">
                @permission('documents.download')
                    <a href="{{ route('documents.download', $attachment) }}" class="btn btn-secondary">{{ __('common.download') }}</a>
                @endpermission
                @permission('transactions.view')
                    <a href="{{ route('transactions.show', $attachment->transaction) }}" class="btn btn-primary">{{ __('documents.view_transaction') }}</a>
                @endpermission
                @include('lending-requests.partials.request-modal', [
                    'transaction' => $attachment->transaction,
                    'canShowLendingButton' => $canShowLendingButton ?? false,
                    'canRequestLending' => $canRequestLending ?? false,
                    'lendingRequestBlockReason' => $lendingRequestBlockReason ?? null,
                ])
            </div>
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        @if ($attachment->fileExists())
            <div class="card doc-preview-card" data-doc-preview>
                <div class="card-header doc-preview-header">
                    <h3 class="card-title">{{ __('documents.preview_title') }}</h3>
                    @if ($attachment->isImage() || $attachment->fileKind() === 'pdf')
                        <div class="doc-preview-controls">
                            <label class="doc-preview-control">
                                <span class="doc-preview-control-label">{{ __('documents.preview_width') }}</span>
                                <input type="range" class="doc-preview-range" data-doc-preview-width min="40" max="100" value="100" step="1">
                                <output class="doc-preview-control-value" data-doc-preview-width-value>100%</output>
                            </label>
                            <label class="doc-preview-control">
                                <span class="doc-preview-control-label">{{ __('documents.preview_height') }}</span>
                                <input type="range" class="doc-preview-range" data-doc-preview-height min="400" max="1200" value="850" step="10">
                                <output class="doc-preview-control-value" data-doc-preview-height-value>850px</output>
                            </label>
                            <button type="button" class="btn btn-secondary btn-sm" data-doc-preview-reset>{{ __('documents.preview_reset') }}</button>
                        </div>
                    @endif
                </div>
                <div class="card-body doc-preview-body">
                    @if ($attachment->isImage())
                        <div class="doc-preview-viewport" data-doc-preview-viewport>
                            <img src="{{ route('documents.preview', $attachment) }}" alt="{{ $attachment->displayName() }}" class="doc-preview-image">
                        </div>
                    @elseif ($attachment->fileKind() === 'pdf')
                        <div class="doc-preview-viewport" data-doc-preview-viewport>
                            <iframe src="{{ route('documents.preview', $attachment) }}" title="{{ $attachment->displayName() }}" class="doc-preview-frame"></iframe>
                        </div>
                    @else
                        <div class="doc-preview-fallback">
                            <x-transaction-file-icon :kind="$attachment->fileKind()" />
                            <p>{{ __('documents.preview_unavailable') }}</p>
                            @permission('documents.download')
                                <a href="{{ route('documents.download', $attachment) }}" class="btn btn-secondary btn-sm">{{ __('documents.download_file') }}</a>
                            @endpermission
                        </div>
                    @endif
                    @if ($attachment->isImage() || $attachment->fileKind() === 'pdf')
                        <p class="doc-preview-hint">{{ __('documents.preview_resize_hint') }}</p>
                    @endif
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('documents.details_title') }}</h3>
            </div>
            <div class="card-body">
                <dl class="dl-grid">
                    <div><dt>{{ __('documents.file_name') }}</dt><dd>{{ $attachment->effectiveFileName() ?? '—' }}</dd></div>
                    <div><dt>{{ __('common.reference_number') }}</dt><dd><code class="txn-ref">{{ $attachment->reference_number ?? $attachment->transaction->reference_number }}</code></dd></div>
                    <div><dt>{{ __('documents.file_kind') }}</dt><dd>{{ strtoupper($attachment->fileKind()) }}</dd></div>
                    <div><dt>{{ __('documents.file_size') }}</dt><dd>{{ $attachment->formattedSize() }}</dd></div>
                    <div><dt>{{ __('documents.uploaded_by') }}</dt><dd>{{ $attachment->uploader?->name ?? '—' }}</dd></div>
                    <div><dt>{{ __('documents.added_at') }}</dt><dd>{{ $attachment->created_at->format('Y-m-d H:i') }}</dd></div>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('documents.linked_transaction') }}</h3>
            </div>
            <div class="card-body">
                <dl class="dl-grid">
                    <div><dt>{{ __('common.reference_number') }}</dt><dd><code class="txn-ref">{{ $attachment->transaction->reference_number }}</code></dd></div>
                    <div><dt>{{ __('transactions.title_label') }}</dt><dd>{{ $attachment->transaction->title }}</dd></div>
                    <div><dt>{{ __('common.department') }}</dt><dd>{{ $attachment->transaction->department?->name ?? '—' }}</dd></div>
                    <div><dt>{{ __('common.transaction_type') }}</dt><dd>{{ $attachment->transaction->transactionType?->name ?? '—' }}</dd></div>
                    <div><dt>{{ __('transactions.status') }}</dt><dd><x-transaction-status-badge :status="$attachment->transaction->status" /></dd></div>
                    <div><dt>{{ __('transactions.date') }}</dt><dd>{{ $attachment->transaction->transaction_date?->format('Y-m-d') ?? '—' }}</dd></div>
                </dl>
            </div>
        </div>
    </div>

    @if ($attachment->fileExists() && ($attachment->isImage() || $attachment->fileKind() === 'pdf'))
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const root = document.querySelector('[data-doc-preview]');
                    const viewport = root?.querySelector('[data-doc-preview-viewport]');
                    const widthInput = root?.querySelector('[data-doc-preview-width]');
                    const heightInput = root?.querySelector('[data-doc-preview-height]');
                    const widthOutput = root?.querySelector('[data-doc-preview-width-value]');
                    const heightOutput = root?.querySelector('[data-doc-preview-height-value]');
                    const resetButton = root?.querySelector('[data-doc-preview-reset]');

                    if (!root || !viewport || !widthInput || !heightInput) {
                        return;
                    }

                    const storageKey = 'doc-preview-size';
                    const defaults = { width: 100, height: 850 };
                    let syncingFromResize = false;

                    const clamp = (value, min, max) => Math.min(max, Math.max(min, value));

                    const applySize = (widthPercent, heightPx) => {
                        const width = clamp(widthPercent, Number(widthInput.min), Number(widthInput.max));
                        const height = clamp(heightPx, Number(heightInput.min), Number(heightInput.max));

                        viewport.style.width = `${width}%`;
                        viewport.style.height = `${height}px`;
                        widthInput.value = String(Math.round(width));
                        heightInput.value = String(Math.round(height));
                        widthOutput.textContent = `${Math.round(width)}%`;
                        heightOutput.textContent = `${Math.round(height)}px`;
                    };

                    const saveSize = () => {
                        localStorage.setItem(storageKey, JSON.stringify({
                            width: Number(widthInput.value),
                            height: Number(heightInput.value),
                        }));
                    };

                    const loadSize = () => {
                        try {
                            const saved = JSON.parse(localStorage.getItem(storageKey) || 'null');
                            if (saved && typeof saved.width === 'number' && typeof saved.height === 'number') {
                                applySize(saved.width, saved.height);
                                return;
                            }
                        } catch (error) {
                            // Ignore invalid saved values.
                        }

                        applySize(defaults.width, defaults.height);
                    };

                    widthInput.addEventListener('input', () => {
                        applySize(Number(widthInput.value), Number(heightInput.value));
                        saveSize();
                    });

                    heightInput.addEventListener('input', () => {
                        applySize(Number(widthInput.value), Number(heightInput.value));
                        saveSize();
                    });

                    resetButton?.addEventListener('click', () => {
                        applySize(defaults.width, defaults.height);
                        saveSize();
                    });

                    if (typeof ResizeObserver !== 'undefined') {
                        const observer = new ResizeObserver(() => {
                            if (syncingFromResize) {
                                return;
                            }

                            syncingFromResize = true;
                            const parentWidth = viewport.parentElement?.clientWidth || viewport.offsetWidth;
                            const widthPercent = parentWidth > 0
                                ? clamp(Math.round((viewport.offsetWidth / parentWidth) * 100), Number(widthInput.min), Number(widthInput.max))
                                : Number(widthInput.value);
                            const heightPx = clamp(viewport.offsetHeight, Number(heightInput.min), Number(heightInput.max));

                            widthInput.value = String(widthPercent);
                            heightInput.value = String(heightPx);
                            widthOutput.textContent = `${widthPercent}%`;
                            heightOutput.textContent = `${heightPx}px`;
                            saveSize();
                            syncingFromResize = false;
                        });

                        observer.observe(viewport);
                    }

                    loadSize();
                });
            </script>
        @endpush
    @endif
</x-app-layout>
