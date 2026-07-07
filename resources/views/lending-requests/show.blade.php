@php
    $transaction = $lendingRequest->transaction;
@endphp

<x-app-layout>
    @push('styles')
        <x-inline-css file="lending-requests.css" />
    @endpush

    <x-slot name="header">
        <div class="page-header lending-page-header">
            <div class="txn-page-header-main">
                <a href="{{ route('lending-requests.index') }}" class="settings-back-link">{{ __('lending_requests.title') }}</a>
                <div class="txn-page-header-row">
                    <div>
                        <h1 class="page-title">{{ __('lending_requests.show_title') }}</h1>
                        <p class="page-subtitle">{{ __('lending_requests.show_subtitle') }}</p>
                        <div class="lending-page-meta">
                            <code class="lending-ref">{{ $transaction?->reference_number ?? '—' }}</code>
                            <x-lending-status-badge :status="$lendingRequest->status" />
                        </div>
                    </div>
                    @if ($transaction && auth()->user()?->canAccessTransaction($transaction))
                        <a href="{{ route('transactions.show', $transaction) }}" class="btn btn-secondary btn-lg">
                            {{ __('documents.view_transaction') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </x-slot>

    <div class="container lending-page">
        <div class="txn-layout">
            <aside class="txn-sidebar">
                <div class="lending-sidebar-card">
                    <div class="lending-sidebar-card-header">
                        <h3 class="lending-sidebar-card-title">{{ __('lending_requests.workflow_title') }}</h3>
                        <p class="lending-sidebar-card-subtitle">{{ __('lending_requests.workflow_subtitle') }}</p>
                    </div>
                    <div class="lending-sidebar-card-body">
                        @include('lending-requests.partials.workflow-steps', ['status' => $lendingRequest->status])
                    </div>
                </div>

                @include('lending-requests.partials.action-panel', [
                    'lendingRequest' => $lendingRequest,
                    'canReview' => $canReview ?? false,
                    'canHandover' => $canHandover ?? false,
                    'canReturn' => $canReturn ?? false,
                ])
            </aside>

            <div class="txn-main">
                <section class="card lending-hero">
                    <div class="lending-hero-grid">
                        <div class="lending-hero-item">
                            <span class="lending-hero-label">{{ __('lending_requests.requester') }}</span>
                            <strong>{{ $lendingRequest->requester?->name ?? '—' }}</strong>
                        </div>
                        <div class="lending-hero-item">
                            <span class="lending-hero-label">{{ __('lending_requests.due_date') }}</span>
                            <strong>{{ $lendingRequest->due_date?->format('Y-m-d') ?? '—' }}</strong>
                        </div>
                        <div class="lending-hero-item">
                            <span class="lending-hero-label">{{ __('common.date') }}</span>
                            <strong>{{ $lendingRequest->created_at?->format('Y-m-d H:i') ?? '—' }}</strong>
                        </div>
                        <div class="lending-hero-item">
                            <span class="lending-hero-label">{{ __('common.title') }}</span>
                            <strong>{{ $transaction?->title ?? '—' }}</strong>
                        </div>
                        <div class="lending-hero-item">
                            <span class="lending-hero-label">{{ __('common.org_unit') }}</span>
                            <strong>{{ $transaction?->department?->name ?? '—' }}</strong>
                        </div>
                        <div class="lending-hero-item">
                            <span class="lending-hero-label">{{ __('lending_requests.document_availability') }}</span>
                            <strong>
                                @if ($transaction?->isOnLoan())
                                    <span class="lending-status-badge" style="background:#8b5cf6;">{{ __('lending_requests.on_loan_badge') }}</span>
                                @else
                                    <span class="lending-status-badge" style="background:#10b981;">{{ __('lending_requests.available_badge') }}</span>
                                @endif
                            </strong>
                        </div>
                        @if ($lendingRequest->purpose)
                            <div class="lending-hero-item lending-hero-purpose">
                                <span class="lending-hero-label">{{ __('lending_requests.purpose') }}</span>
                                <strong>{{ $lendingRequest->purpose }}</strong>
                            </div>
                        @endif
                    </div>
                </section>

                <div class="lending-panels">
                    <section class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('lending_requests.request_info') }}</h3>
                        </div>
                        <div class="card-body">
                            <dl class="lending-detail-grid">
                                <div class="lending-detail-row">
                                    <dt>{{ __('lending_requests.requester') }}</dt>
                                    <dd>{{ $lendingRequest->requester?->name ?? '—' }}</dd>
                                </div>
                                <div class="lending-detail-row">
                                    <dt>{{ __('lending_requests.purpose') }}</dt>
                                    <dd>{{ $lendingRequest->purpose ?? '—' }}</dd>
                                </div>
                                <div class="lending-detail-row">
                                    <dt>{{ __('lending_requests.due_date') }}</dt>
                                    <dd>{{ $lendingRequest->due_date?->format('Y-m-d') ?? '—' }}</dd>
                                </div>
                                @if ($lendingRequest->reviewer)
                                    <div class="lending-detail-row">
                                        <dt>{{ __('lending_requests.reviewer') }}</dt>
                                        <dd>{{ $lendingRequest->reviewer->name }} — {{ $lendingRequest->reviewed_at?->format('Y-m-d H:i') }}</dd>
                                    </div>
                                    @if ($lendingRequest->review_notes)
                                        <div class="lending-detail-row">
                                            <dt>{{ __('lending_requests.review_notes') }}</dt>
                                            <dd>{{ $lendingRequest->review_notes }}</dd>
                                        </div>
                                    @endif
                                @endif
                                @if ($lendingRequest->handoverBy)
                                    <div class="lending-detail-row">
                                        <dt>{{ __('lending_requests.handover_by') }}</dt>
                                        <dd>{{ $lendingRequest->handoverBy->name }} — {{ $lendingRequest->handed_over_at?->format('Y-m-d H:i') }}</dd>
                                    </div>
                                    @if ($lendingRequest->handover_notes)
                                        <div class="lending-detail-row">
                                            <dt>{{ __('lending_requests.handover_notes') }}</dt>
                                            <dd>{{ $lendingRequest->handover_notes }}</dd>
                                        </div>
                                    @endif
                                @endif
                                @if ($lendingRequest->returnedByUser)
                                    <div class="lending-detail-row">
                                        <dt>{{ __('lending_requests.returned_by') }}</dt>
                                        <dd>{{ $lendingRequest->returnedByUser->name }} — {{ $lendingRequest->returned_at?->format('Y-m-d H:i') }}</dd>
                                    </div>
                                    @if ($lendingRequest->return_notes)
                                        <div class="lending-detail-row">
                                            <dt>{{ __('lending_requests.return_notes') }}</dt>
                                            <dd>{{ $lendingRequest->return_notes }}</dd>
                                        </div>
                                    @endif
                                @endif
                            </dl>
                        </div>
                    </section>

                    <section class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('lending_requests.transaction_info') }}</h3>
                        </div>
                        <div class="card-body">
                            <dl class="lending-detail-grid">
                                <div class="lending-detail-row">
                                    <dt>{{ __('common.reference_number') }}</dt>
                                    <dd><code>{{ $transaction?->reference_number ?? '—' }}</code></dd>
                                </div>
                                <div class="lending-detail-row">
                                    <dt>{{ __('common.title') }}</dt>
                                    <dd>{{ $transaction?->title ?? '—' }}</dd>
                                </div>
                                <div class="lending-detail-row">
                                    <dt>{{ __('common.org_unit') }}</dt>
                                    <dd>{{ $transaction?->department?->name ?? '—' }}</dd>
                                </div>
                                <div class="lending-detail-row">
                                    <dt>{{ __('common.transaction_type') }}</dt>
                                    <dd>{{ $transaction?->transactionType?->name ?? '—' }}</dd>
                                </div>
                                <div class="lending-detail-row">
                                    <dt>{{ __('common.status') }}</dt>
                                    <dd><x-transaction-status-badge :status="$transaction?->status" /></dd>
                                </div>
                                <div class="lending-detail-row">
                                    <dt>{{ __('lending_requests.attachments_count', ['count' => $transaction?->attachments?->count() ?? 0]) }}</dt>
                                    <dd>{{ $transaction?->attachments?->count() ?? 0 }}</dd>
                                </div>
                            </dl>
                        </div>
                    </section>
                </div>

                <section class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">{{ __('lending_requests.history_title') }}</h3>
                            <p class="card-subtitle">{{ __('lending_requests.history_subtitle') }}</p>
                        </div>
                    </div>
                    <div class="card-body card-body-flush">
                        @if ($lendingRequest->histories->isNotEmpty())
                            <div class="lending-timeline">
                                @foreach ($lendingRequest->histories as $history)
                                    <article class="lending-timeline-item">
                                        <span class="lending-timeline-dot"></span>
                                        <div>
                                            <div class="lending-timeline-head">
                                                <span class="lending-timeline-action">{{ $history->actionLabel() }}</span>
                                                <x-lending-status-badge :status="$history->toStatusEnum()" />
                                            </div>
                                            <div class="lending-timeline-meta">
                                                {{ $history->performer?->name ?? '—' }}
                                                ·
                                                {{ $history->created_at?->format('Y-m-d H:i') }}
                                                @if ($history->fromStatusLabel() !== '—')
                                                    · {{ __('lending_requests.from_status') }}: {{ $history->fromStatusLabel() }}
                                                @endif
                                            </div>
                                            @if ($history->notes)
                                                <p class="lending-timeline-notes">{{ $history->notes }}</p>
                                            @endif
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @else
                            <div class="lending-empty-state">—</div>
                        @endif
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
