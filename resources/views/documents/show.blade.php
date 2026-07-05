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
            </div>
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        @if ($attachment->fileExists())
            <div class="card doc-preview-card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('documents.preview_title') }}</h3>
                </div>
                <div class="card-body doc-preview-body">
                    @if ($attachment->isImage())
                        <img src="{{ route('documents.preview', $attachment) }}" alt="{{ $attachment->displayName() }}" class="doc-preview-image">
                    @elseif ($attachment->fileKind() === 'pdf')
                        <iframe src="{{ route('documents.preview', $attachment) }}" title="{{ $attachment->displayName() }}" class="doc-preview-frame"></iframe>
                    @else
                        <div class="doc-preview-fallback">
                            <x-transaction-file-icon :kind="$attachment->fileKind()" />
                            <p>{{ __('documents.preview_unavailable') }}</p>
                            @permission('documents.download')
                                <a href="{{ route('documents.download', $attachment) }}" class="btn btn-secondary btn-sm">{{ __('documents.download_file') }}</a>
                            @endpermission
                        </div>
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
</x-app-layout>
