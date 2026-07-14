<x-app-layout>
    @push('styles')
        <x-inline-css file="document-preview.css" />
    @endpush

    <x-slot name="header">
        <div class="page-header">
            <div>
                @if (request('from') === 'lending' && request()->filled('lending_request'))
                    <a href="{{ route('lending-requests.show', request('lending_request')) }}" class="settings-back-link">{{ __('lending_requests.back_to_lending_request') }}</a>
                @elseif (request('from') === 'transaction' && auth()->user()?->hasPermission('transactions.view'))
                    <a href="{{ route('transactions.show', $attachment->transaction) }}" class="settings-back-link">{{ __('common.back_to_transaction') }}</a>
                @elseif (auth()->user()?->hasPermission('documents.view'))
                    <a href="{{ route('documents.index') }}" class="settings-back-link">{{ __('common.back_to_documents') }}</a>
                @else
                    <a href="{{ route('lending-requests.index') }}" class="settings-back-link">{{ __('lending_requests.title') }}</a>
                @endif
                <h2 class="page-title">{{ $attachment->displayName() }}</h2>
            </div>
            <div class="form-actions">
                @permission('documents.download')
                    <a href="{{ route('documents.download', $attachment) }}" class="btn btn-secondary">{{ __('common.download') }}</a>
                @endpermission
                @if ($attachment->fileExists() && ($attachment->isImage() || $attachment->fileKind() === 'pdf'))
                    <a href="{{ route('documents.print', $attachment) }}" target="_blank" rel="noopener" class="btn btn-secondary">{{ __('documents.print') }}</a>
                @endif
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
                                <span class="doc-preview-control-label">{{ __('documents.preview_zoom') }}</span>
                                <input type="range" class="doc-preview-range" data-doc-preview-zoom min="50" max="300" value="100" step="5">
                                <output class="doc-preview-control-value" data-doc-preview-zoom-value>100%</output>
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
                            <div class="doc-preview-content" data-doc-preview-content>
                                <img src="{{ route('documents.preview', $attachment) }}" alt="{{ $attachment->displayName() }}" class="doc-preview-image">
                            </div>
                        </div>
                    @elseif ($attachment->fileKind() === 'pdf')
                        <div class="doc-preview-viewport" data-doc-preview-viewport>
                            <iframe
                                src="{{ route('documents.preview', $attachment) }}#zoom=page-width"
                                data-doc-preview-frame
                                data-doc-preview-src="{{ route('documents.preview', $attachment) }}"
                                title="{{ $attachment->displayName() }}"
                                class="doc-preview-frame"
                            ></iframe>
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
                    const zoomInput = root?.querySelector('[data-doc-preview-zoom]');
                    const heightInput = root?.querySelector('[data-doc-preview-height]');
                    const zoomOutput = root?.querySelector('[data-doc-preview-zoom-value]');
                    const heightOutput = root?.querySelector('[data-doc-preview-height-value]');
                    const resetButton = root?.querySelector('[data-doc-preview-reset]');
                    const frame = root?.querySelector('[data-doc-preview-frame]');
                    const content = root?.querySelector('[data-doc-preview-content]');

                    if (!root || !viewport || !zoomInput || !heightInput) {
                        return;
                    }

                    const storageKey = 'doc-preview-settings';
                    const defaults = { zoom: 100, height: 850 };

                    const clamp = (value, min, max) => Math.min(max, Math.max(min, value));

                    const applyZoom = (zoom) => {
                        const value = clamp(zoom, Number(zoomInput.min), Number(zoomInput.max));
                        zoomInput.value = String(value);
                        zoomOutput.textContent = `${value}%`;

                        if (frame) {
                            const baseSrc = frame.dataset.docPreviewSrc;
                            const zoomParam = value === 100 ? 'page-width' : value;
                            frame.src = `${baseSrc}#zoom=${zoomParam}`;
                        }

                        if (content) {
                            content.style.width = `${value}%`;
                        }
                    };

                    const applyHeight = (heightPx) => {
                        const value = clamp(heightPx, Number(heightInput.min), Number(heightInput.max));
                        viewport.style.height = `${value}px`;
                        heightInput.value = String(value);
                        heightOutput.textContent = `${value}px`;
                    };

                    const saveSettings = () => {
                        localStorage.setItem(storageKey, JSON.stringify({
                            zoom: Number(zoomInput.value),
                            height: Number(heightInput.value),
                        }));
                    };

                    const loadSettings = () => {
                        let zoom = defaults.zoom;
                        let height = defaults.height;

                        try {
                            const saved = JSON.parse(localStorage.getItem(storageKey) || 'null');
                            if (saved && typeof saved.zoom === 'number' && typeof saved.height === 'number') {
                                zoom = saved.zoom;
                                height = saved.height;
                            }
                        } catch (error) {
                            // Ignore invalid saved values.
                        }

                        applyZoom(zoom);
                        applyHeight(height);
                    };

                    zoomInput.addEventListener('input', () => {
                        applyZoom(Number(zoomInput.value));
                        saveSettings();
                    });

                    heightInput.addEventListener('input', () => {
                        applyHeight(Number(heightInput.value));
                        saveSettings();
                    });

                    resetButton?.addEventListener('click', () => {
                        applyZoom(defaults.zoom);
                        applyHeight(defaults.height);
                        saveSettings();
                    });

                    loadSettings();
                });
            </script>
        @endpush
    @endif
</x-app-layout>
