<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('lending-requests.index') }}" class="settings-back-link">{{ __('lending_requests.title') }}</a>
                <h1 class="page-title">{{ __('lending_requests.show_title') }}</h1>
                <p class="page-subtitle">
                    <code>{{ $lendingRequest->transaction?->reference_number }}</code>
                    — <x-lending-status-badge :status="$lendingRequest->status" />
                </p>
            </div>
            @if ($lendingRequest->transaction && auth()->user()?->canAccessTransaction($lendingRequest->transaction))
                <a href="{{ route('transactions.show', $lendingRequest->transaction) }}" class="btn btn-secondary btn-lg">
                    {{ __('documents.view_transaction') }}
                </a>
            @endif
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        <div class="txn-layout">
            <div class="txn-main">
                @if ($canReview || $canHandover || $canReturn)
                    <div class="card card-elevated">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('lending_requests.pending_actions') }}</h3>
                        </div>
                        <div class="card-body">
                            @if ($canReview)
                                <div class="form-actions" style="margin-bottom:1rem;">
                                    <form method="POST" action="{{ route('lending-requests.review', $lendingRequest) }}" class="txn-advance-form">
                                        @csrf
                                        <input type="hidden" name="approve" value="1">
                                        <div class="form-group">
                                            <x-input-label for="review-notes-approve" :value="__('lending_requests.review_notes')" />
                                            <textarea id="review-notes-approve" name="notes" class="form-textarea" rows="2"></textarea>
                                        </div>
                                        <x-primary-button>{{ __('lending_requests.approve_review') }}</x-primary-button>
                                    </form>
                                </div>
                                <form method="POST" action="{{ route('lending-requests.review', $lendingRequest) }}">
                                    @csrf
                                    <input type="hidden" name="approve" value="0">
                                    <div class="form-group">
                                        <x-input-label for="review-notes-reject" :value="__('lending_requests.reject_notes_required')" />
                                        <textarea id="review-notes-reject" name="notes" class="form-textarea" rows="2" required placeholder="{{ __('lending_requests.reject_notes_placeholder') }}"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-danger">{{ __('lending_requests.reject_review') }}</button>
                                </form>
                            @endif

                            @if ($canHandover)
                                <div class="form-actions" style="margin-bottom:1rem;">
                                    <form method="POST" action="{{ route('lending-requests.handover', $lendingRequest) }}">
                                        @csrf
                                        <input type="hidden" name="confirm" value="1">
                                        <div class="form-group">
                                            <x-input-label for="handover-notes-confirm" :value="__('lending_requests.handover_notes')" />
                                            <textarea id="handover-notes-confirm" name="notes" class="form-textarea" rows="2"></textarea>
                                        </div>
                                        <x-primary-button>{{ __('lending_requests.confirm_handover') }}</x-primary-button>
                                    </form>
                                </div>
                                <form method="POST" action="{{ route('lending-requests.handover', $lendingRequest) }}">
                                    @csrf
                                    <input type="hidden" name="confirm" value="0">
                                    <div class="form-group">
                                        <x-input-label for="handover-notes-reject" :value="__('lending_requests.reject_notes_required')" />
                                        <textarea id="handover-notes-reject" name="notes" class="form-textarea" rows="2" required placeholder="{{ __('lending_requests.reject_notes_placeholder') }}"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-danger">{{ __('lending_requests.reject_handover') }}</button>
                                </form>
                            @endif

                            @if ($canReturn)
                                <form method="POST" action="{{ route('lending-requests.return', $lendingRequest) }}">
                                    @csrf
                                    <div class="form-group">
                                        <x-input-label for="return-notes" :value="__('lending_requests.return_notes')" />
                                        <textarea id="return-notes" name="notes" class="form-textarea" rows="2" placeholder="{{ __('lending_requests.return_notes_placeholder') }}"></textarea>
                                    </div>
                                    <x-primary-button>{{ __('lending_requests.return_documents') }}</x-primary-button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="card">
                    <div class="card-header"><h3 class="card-title">{{ __('lending_requests.request_info') }}</h3></div>
                    <div class="card-body">
                        <dl class="detail-list">
                            <div><dt>{{ __('lending_requests.requester') }}</dt><dd>{{ $lendingRequest->requester?->name ?? '—' }}</dd></div>
                            <div><dt>{{ __('lending_requests.purpose') }}</dt><dd>{{ $lendingRequest->purpose ?? '—' }}</dd></div>
                            <div><dt>{{ __('lending_requests.due_date') }}</dt><dd>{{ $lendingRequest->due_date?->format('Y-m-d') ?? '—' }}</dd></div>
                            <div><dt>{{ __('common.date') }}</dt><dd>{{ $lendingRequest->created_at?->format('Y-m-d H:i') ?? '—' }}</dd></div>
                            @if ($lendingRequest->reviewer)
                                <div><dt>{{ __('lending_requests.reviewer') }}</dt><dd>{{ $lendingRequest->reviewer->name }} ({{ $lendingRequest->reviewed_at?->format('Y-m-d H:i') }})</dd></div>
                                @if ($lendingRequest->review_notes)
                                    <div><dt>{{ __('lending_requests.review_notes') }}</dt><dd>{{ $lendingRequest->review_notes }}</dd></div>
                                @endif
                            @endif
                            @if ($lendingRequest->handoverBy)
                                <div><dt>{{ __('lending_requests.handover_by') }}</dt><dd>{{ $lendingRequest->handoverBy->name }} ({{ $lendingRequest->handed_over_at?->format('Y-m-d H:i') }})</dd></div>
                                @if ($lendingRequest->handover_notes)
                                    <div><dt>{{ __('lending_requests.handover_notes') }}</dt><dd>{{ $lendingRequest->handover_notes }}</dd></div>
                                @endif
                            @endif
                            @if ($lendingRequest->returnedByUser)
                                <div><dt>{{ __('lending_requests.returned_by') }}</dt><dd>{{ $lendingRequest->returnedByUser->name }} ({{ $lendingRequest->returned_at?->format('Y-m-d H:i') }})</dd></div>
                                @if ($lendingRequest->return_notes)
                                    <div><dt>{{ __('lending_requests.return_notes') }}</dt><dd>{{ $lendingRequest->return_notes }}</dd></div>
                                @endif
                            @endif
                        </dl>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3 class="card-title">{{ __('lending_requests.transaction_info') }}</h3></div>
                    <div class="card-body">
                        @php $transaction = $lendingRequest->transaction; @endphp
                        <dl class="detail-list">
                            <div><dt>{{ __('common.reference_number') }}</dt><dd><code>{{ $transaction?->reference_number ?? '—' }}</code></dd></div>
                            <div><dt>{{ __('common.title') }}</dt><dd>{{ $transaction?->title ?? '—' }}</dd></div>
                            <div><dt>{{ __('common.org_unit') }}</dt><dd>{{ $transaction?->department?->name ?? '—' }}</dd></div>
                            <div><dt>{{ __('common.transaction_type') }}</dt><dd>{{ $transaction?->transactionType?->name ?? '—' }}</dd></div>
                            <div><dt>{{ __('common.status') }}</dt><dd><x-transaction-status-badge :status="$transaction?->status" /></dd></div>
                            <div><dt>{{ __('lending_requests.lending_status.available') }}</dt>
                                <dd>
                                    @if ($transaction?->isOnLoan())
                                        <span class="badge" style="background:#8b5cf6;color:#fff;">{{ __('lending_requests.on_loan_badge') }}</span>
                                    @else
                                        <span class="badge" style="background:#10b981;color:#fff;">{{ __('lending_requests.available_badge') }}</span>
                                    @endif
                                </dd>
                            </div>
                            <div><dt>{{ __('lending_requests.attachments_count', ['count' => $transaction?->attachments?->count() ?? 0]) }}</dt><dd>{{ $transaction?->attachments?->count() ?? 0 }}</dd></div>
                        </dl>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3 class="card-title">{{ __('lending_requests.history_title') }}</h3></div>
                    <div class="card-body card-body-flush">
                        <div class="table-wrapper">
                            <table class="table table-modern">
                                <thead>
                                    <tr>
                                        <th>{{ __('lending_requests.action') }}</th>
                                        <th>{{ __('lending_requests.from_status') }}</th>
                                        <th>{{ __('lending_requests.to_status') }}</th>
                                        <th>{{ __('lending_requests.performer') }}</th>
                                        <th>{{ __('common.notes') }}</th>
                                        <th>{{ __('common.date') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($lendingRequest->histories as $history)
                                        <tr>
                                            <td>{{ $history->action->label() }}</td>
                                            <td>{{ $history->from_status?->label() ?? '—' }}</td>
                                            <td><x-lending-status-badge :status="$history->to_status" /></td>
                                            <td>{{ $history->performer?->name ?? '—' }}</td>
                                            <td>{{ $history->notes ?? '—' }}</td>
                                            <td>{{ $history->created_at?->format('Y-m-d H:i') }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="table-empty">—</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
