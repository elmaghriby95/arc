@php
    $attachments = $transaction?->attachments ?? collect();
    $canPreviewDocuments = auth()->user()?->hasPermission('documents.view')
        || auth()->user()?->hasPermission('lending-requests.review')
        || auth()->user()?->hasPermission('lending-requests.handover');
    $documentQuery = ['from' => 'lending', 'lending_request' => $lendingRequest->id];
@endphp

<section class="card lending-documents">
    <div class="card-header">
        <div>
            <h3 class="card-title">{{ __('lending_requests.transaction_documents') }}</h3>
            <p class="card-subtitle">{{ __('lending_requests.transaction_documents_subtitle', ['count' => $attachments->count()]) }}</p>
        </div>
    </div>
    <div class="card-body lending-documents-body">
        <div class="txn-attachments-list lending-documents-list">
            @forelse ($attachments as $attachment)
                <article class="txn-attachment-card txn-attachment-card--{{ $attachment->fileKind() }}">
                    <div class="txn-attachment-preview">
                        @if ($canPreviewDocuments)
                            @if ($attachment->isImage() && $attachment->fileExists())
                                <a href="{{ route('documents.show', array_merge(['attachment' => $attachment], $documentQuery)) }}" class="txn-attachment-thumb">
                                    <img src="{{ route('documents.preview', $attachment) }}" alt="{{ $attachment->displayName() }}" loading="lazy">
                                </a>
                            @else
                                <a href="{{ route('documents.show', array_merge(['attachment' => $attachment], $documentQuery)) }}" class="txn-attachment-icon-wrap txn-attachment-icon-wrap--link">
                                    <x-transaction-file-icon :kind="$attachment->fileKind()" />
                                </a>
                            @endif
                        @else
                            <div class="txn-attachment-icon-wrap">
                                <x-transaction-file-icon :kind="$attachment->fileKind()" />
                            </div>
                        @endif
                    </div>
                    <div class="txn-attachment-body">
                        <h4 class="txn-attachment-name" title="{{ $attachment->displayName() }}">{{ $attachment->displayName() }}</h4>
                        @if ($attachment->reference_number)
                            <div class="txn-attachment-ref">
                                <code>{{ $attachment->reference_number }}</code>
                                @if ($attachment->is_operational_number)
                                    <span class="txn-attachment-ref-tag">{{ __('transactions.operational') }}</span>
                                @endif
                            </div>
                        @endif
                        <div class="txn-attachment-meta">
                            <span>{{ $attachment->formattedSize() }}</span>
                        </div>
                        <div class="txn-attachment-actions">
                            @if ($canPreviewDocuments)
                                <a href="{{ route('documents.show', array_merge(['attachment' => $attachment], $documentQuery)) }}" class="txn-attachment-action" title="{{ __('common.view') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path stroke-linecap="round" d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                            @endif
                            @permission('documents.download')
                                <a href="{{ route('documents.download', $attachment) }}" class="txn-attachment-action" title="{{ __('common.download') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path stroke-linecap="round" d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16"/></svg>
                                </a>
                            @endpermission
                        </div>
                    </div>
                </article>
            @empty
                <div class="txn-attachments-empty">
                    <x-transaction-file-icon kind="file" />
                    <p>{{ __('transactions.attachments_empty') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</section>
