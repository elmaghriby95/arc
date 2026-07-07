<x-app-layout>
    <x-slot name="header">
        <div class="page-header txn-page-header">
            <div class="txn-page-header-main">
                <a href="{{ route('transactions.index') }}" class="settings-back-link">{{ __('common.back_to_transactions') }}</a>
                <div class="txn-page-header-row">
                    <div>
                        <h2 class="page-title">{{ $transaction->title }}</h2>
                        <div class="txn-page-meta">
                            <code class="txn-ref">{{ $transaction->archival_reference }}</code>
                            <span class="text-muted txn-system-ref">{{ $transaction->reference_number }}</span>
                            <x-transaction-status-badge :status="$transaction->status" />
                        </div>
                    </div>
                    @permission('transactions.edit')
                        @if ($transaction->canBeEdited())
                            <a href="{{ route('transactions.edit', $transaction) }}" class="btn btn-secondary">{{ __('transactions.edit_data') }}</a>
                        @endif
                    @endpermission
                    @include('lending-requests.partials.request-modal', [
                        'transaction' => $transaction,
                        'canShowLendingButton' => $canShowLendingButton ?? false,
                        'canRequestLending' => $canRequestLending ?? false,
                        'lendingRequestBlockReason' => $lendingRequestBlockReason ?? null,
                    ])
                </div>
            </div>
        </div>
    </x-slot>

    <div class="container txn-page">
        <div class="txn-layout">
            @include('transactions.partials.attachments-sidebar', [
                'transaction' => $transaction,
                'canManageAttachments' => $canManageAttachments,
                'txnAttachmentsI18n' => $txnAttachmentsI18n,
                'qrPayload' => $qrPayload,
            ])

            <div class="txn-main">
                <section class="txn-hero card">
                    <div class="txn-hero-grid">
                        <div class="txn-hero-item">
                            <span class="txn-hero-label">{{ __('common.transaction_type') }}</span>
                            <strong>{{ $transaction->transactionType?->name ?? '—' }}</strong>
                        </div>
                        <div class="txn-hero-item">
                            <span class="txn-hero-label">{{ __('common.org_unit') }}</span>
                            <strong>{{ $transaction->department?->name ?? '—' }}</strong>
                        </div>
                        <div class="txn-hero-item">
                            <span class="txn-hero-label">{{ __('common.folder') }}</span>
                            <strong>{{ $transaction->folder?->name ?? '—' }}</strong>
                            @if ($transaction->folder?->locationLabel())
                                <small class="text-muted d-block">{{ $transaction->folder->locationLabel() }}</small>
                            @endif
                        </div>
                        <div class="txn-hero-item">
                            <span class="txn-hero-label">{{ __('transactions.date') }}</span>
                            <strong>{{ $transaction->transaction_date?->format('Y-m-d') ?? '—' }}</strong>
                        </div>
                        <div class="txn-hero-item">
                            <span class="txn-hero-label">{{ __('transactions.created_by') }}</span>
                            <strong>{{ $transaction->creator?->name ?? '—' }}</strong>
                        </div>
                        <div class="txn-hero-item">
                            <span class="txn-hero-label">{{ __('transactions.attachments_count') }}</span>
                            <strong>{{ $transaction->attachments->count() }}</strong>
                        </div>
                    </div>
                </section>

                <section class="card txn-workflow-card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('transactions.workflow_title') }}</h3>
                    </div>
                    <div class="card-body">
                        <div class="txn-workflow-steps txn-workflow-steps--compact">
                            @foreach ($workflow as $index => $step)
                                @php
                                    $isCurrent = $transaction->transaction_status_id === $step->id;
                                    $isPast = $step->sort_order < ($transaction->status?->sort_order ?? 0)
                                        || ($step->sort_order === ($transaction->status?->sort_order ?? 0) && $step->id <= $transaction->transaction_status_id);
                                @endphp
                                <div class="txn-workflow-step {{ $isCurrent ? 'txn-workflow-step--current' : '' }} {{ $isPast && ! $isCurrent ? 'txn-workflow-step--done' : '' }}">
                                    <span class="txn-workflow-step-num">{{ $index + 1 }}</span>
                                    <span class="txn-status-badge" style="--txn-status-color: {{ $step->color ?? '#64748b' }}">{{ $step->name }}</span>
                                </div>
                                @if (! $loop->last)
                                    <span class="txn-workflow-arrow" aria-hidden="true">←</span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </section>

                <div class="txn-panels">
                    <section class="card txn-panel">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('transactions.details_title') }}</h3>
                        </div>
                        <div class="card-body">
                            @if ($transaction->description)
                                <div class="txn-detail-block">
                                    <h4>{{ __('common.description') }}</h4>
                                    <p>{{ $transaction->description }}</p>
                                </div>
                            @endif
                            @if ($transaction->notes)
                                <div class="txn-detail-block">
                                    <h4>{{ __('common.notes') }}</h4>
                                    <p>{{ $transaction->notes }}</p>
                                </div>
                            @endif
                            @unless ($transaction->description || $transaction->notes)
                                <p class="text-muted">{{ __('transactions.no_details') }}</p>
                            @endunless
                            <dl class="dl-grid txn-meta-grid">
                                <div><dt>{{ __('transactions.org_path') }}</dt><dd>{{ $transaction->department?->breadcrumb() ?? '—' }}</dd></div>
                                <div><dt>{{ __('transactions.created_at') }}</dt><dd>{{ $transaction->created_at->format('Y-m-d H:i') }}</dd></div>
                            </dl>
                        </div>
                    </section>

                    <section class="card txn-panel txn-panel--action">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('transactions.workflow_actions') }}</h3>
                        </div>
                        <div class="card-body">
                            @if ($workflowActions->isNotEmpty())
                                <div class="txn-advance-box">
                                    <p class="txn-advance-next">{{ __('transactions.current_status') }}</p>
                                    <x-transaction-status-badge :status="$transaction->status" />
                                    @if ($transaction->status?->required_permission)
                                        <p class="form-hint">{{ __('transactions.stage_permission', ['permission' => $transaction->status->permissionLabel()]) }}</p>
                                    @endif

                                    @foreach ($workflowActions as $workflowAction)
                                        <form method="POST" action="{{ route('transactions.transition', $transaction) }}" class="txn-advance-form">
                                            @csrf
                                            <input type="hidden" name="action" value="{{ $workflowAction['action']->value }}">
                                            <div class="form-group">
                                                <x-input-label
                                                    for="notes_{{ $workflowAction['action']->value }}"
                                                    :value="$workflowAction['action'] === \App\Enums\WorkflowAction::Reject
                                                        ? __('transactions.note_required_reject')
                                                        : __('transactions.note_optional')"
                                                />
                                                <textarea
                                                    id="notes_{{ $workflowAction['action']->value }}"
                                                    name="notes"
                                                    rows="2"
                                                    class="form-control"
                                                    placeholder="{{ $workflowAction['action'] === \App\Enums\WorkflowAction::Reject ? __('transactions.reject_reason_placeholder') : __('transactions.note_placeholder') }}"
                                                    @if ($workflowAction['action'] === \App\Enums\WorkflowAction::Reject) required @endif
                                                >{{ old('action') === $workflowAction['action']->value ? old('notes') : '' }}</textarea>
                                            </div>
                                            <button type="submit" class="btn {{ $workflowAction['button_class'] }}">
                                                {{ $workflowAction['label'] }}
                                            </button>
                                        </form>
                                    @endforeach
                                </div>
                            @elseif ($transaction->isAtFinalStatus())
                                <div class="txn-state-message txn-state-message--success">
                                    <strong>{{ __('transactions.completed_title') }}</strong>
                                    <p>{{ __('transactions.completed_desc') }}</p>
                                </div>
                            @else
                                <div class="txn-state-message txn-state-message--warning">
                                    <strong>{{ __('transactions.awaiting_permission') }}</strong>
                                    <p>{{ __('transactions.no_action_permission', ['status' => $transaction->status?->name]) }}</p>
                                    @if ($transaction->status?->required_permission)
                                        <p class="form-hint">{{ __('transactions.required_permission', ['permission' => $transaction->status->permissionLabel()]) }}</p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </section>
                </div>

                @if ($transaction->statusHistories->isNotEmpty())
                    <section class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('transactions.status_history') }}</h3>
                        </div>
                        <div class="card-body card-body-flush">
                            <div class="table-wrapper">
                                <table class="table table-modern">
                                    <thead>
                                        <tr>
                                            <th>{{ __('transactions.action') }}</th>
                                            <th>{{ __('transactions.from_status') }}</th>
                                            <th>{{ __('transactions.to_status') }}</th>
                                            <th>{{ __('transactions.changed_by') }}</th>
                                            <th>{{ __('common.notes') }}</th>
                                            <th>{{ __('common.date') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($transaction->statusHistories as $history)
                                            <tr>
                                                <td>
                                                    @if ($history->action)
                                                        @php $historyAction = \App\Enums\WorkflowAction::tryFrom($history->action); @endphp
                                                        <span class="txn-history-action txn-history-action--{{ $history->action }}">
                                                            {{ $historyAction?->label() ?? $history->action }}
                                                        </span>
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td>{{ $history->fromStatus?->name ?? '—' }}</td>
                                                <td><x-transaction-status-badge :status="$history->toStatus" /></td>
                                                <td>{{ $history->changedBy?->name ?? '—' }}</td>
                                                <td>{{ $history->notes ?? '—' }}</td>
                                                <td>{{ $history->created_at?->format('Y-m-d H:i') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                @endif

                @permission('transactions.delete')
                    <form method="POST" action="{{ route('transactions.destroy', $transaction) }}" onsubmit="return confirm(@json(__('transactions.confirm_delete')))" class="txn-delete-form">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">{{ __('transactions.delete_button') }}</button>
                    </form>
                @endpermission
            </div>
        </div>
    </div>

    @push('scripts')
        @php($txAttachmentsJs = resource_path('js/transaction-attachments.js'))
        @if (is_readable($txAttachmentsJs))
            <script>{!! file_get_contents($txAttachmentsJs) !!}</script>
        @endif
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const printButton = document.querySelector('[data-txn-qr-print]');
                const printArea = document.getElementById('txn-qr-print-area');

                if (!printButton || !printArea) {
                    return;
                }

                printButton.addEventListener('click', () => {
                    const printWindow = window.open('', '_blank', 'noopener,noreferrer');

                    if (!printWindow) {
                        return;
                    }

                    const direction = document.documentElement.getAttribute('dir') || 'rtl';
                    const language = document.documentElement.getAttribute('lang') || 'ar';
                    const title = printArea.querySelector('.txn-sidebar-qr-title')?.textContent?.trim() || '';

                    printWindow.document.write(`<!DOCTYPE html>
<html lang="${language}" dir="${direction}">
<head>
    <meta charset="utf-8">
    <title>${title}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 2rem;
            font-family: Cairo, sans-serif;
            text-align: center;
            color: #0f172a;
        }
        h1, h3 {
            margin: 0 0 1.25rem;
            font-size: 1.2rem;
            font-weight: 800;
        }
        img {
            width: 240px;
            height: 240px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #fff;
            padding: 0.5rem;
        }
        p {
            margin: 1rem 0 0.5rem;
            color: #64748b;
            font-size: 0.9rem;
        }
        code {
            display: block;
            font-size: 0.85rem;
            word-break: break-all;
            color: #334155;
        }
    </style>
</head>
<body>
    ${printArea.innerHTML}
</body>
</html>`);
                    printWindow.document.close();
                    printWindow.focus();

                    const printWhenReady = () => {
                        printWindow.print();
                        printWindow.close();
                    };

                    const image = printWindow.document.querySelector('img');
                    if (image && !image.complete) {
                        image.addEventListener('load', printWhenReady, { once: true });
                        image.addEventListener('error', printWhenReady, { once: true });
                    } else {
                        printWhenReady();
                    }
                });
            });
        </script>
    @endpush
</x-app-layout>
