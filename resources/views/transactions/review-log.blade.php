<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1 class="page-title">{{ __('transactions.review_log_title') }}</h1>
                <p class="page-subtitle">{{ __('transactions.review_log_subtitle') }}</p>
            </div>
            @permission('transactions.view')
                <a href="{{ route('transactions.index') }}" class="btn btn-secondary btn-lg">
                    {{ __('common.back_to_transactions') }}
                </a>
            @endpermission
        </div>
    </x-slot>

    <div class="card card-elevated">
        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('transactions.review_log_filters_title') }}</h3>
                <p class="card-subtitle">{{ __('transactions.review_log_filters_subtitle') }}</p>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="form-grid">
                <div class="form-group">
                    <x-input-label for="search" :value="__('common.search')" />
                    <x-text-input id="search" name="search" type="text" :value="request('search')" :placeholder="__('transactions.search_placeholder')" />
                </div>
                <div class="form-group">
                    <x-input-label for="department_id" :value="__('common.org_unit')" />
                    @include('settings.partials.org-unit-select', [
                        'orgUnits' => $orgUnits,
                        'selected' => request('department_id'),
                        'placeholder' => __('common.all_dash'),
                        'showHint' => false,
                    ])
                </div>
                <div class="form-group">
                    <x-input-label for="changed_by" :value="__('transactions.reviewer')" />
                    <select id="changed_by" name="changed_by" class="form-select">
                        <option value="">{{ __('common.all') }}</option>
                        @foreach ($reviewers as $reviewer)
                            <option value="{{ $reviewer->id }}" @selected(request('changed_by') == $reviewer->id)>{{ $reviewer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <x-input-label for="action" :value="__('transactions.action')" />
                    <select id="action" name="action" class="form-select">
                        <option value="">{{ __('common.all') }}</option>
                        @foreach ($actions as $workflowAction)
                            <option value="{{ $workflowAction->value }}" @selected(request('action') === $workflowAction->value)>{{ $workflowAction->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <x-input-label for="date_from" :value="__('transactions.review_log_date_from')" />
                    <x-text-input id="date_from" name="date_from" type="date" :value="request('date_from')" />
                </div>
                <div class="form-group">
                    <x-input-label for="date_to" :value="__('transactions.review_log_date_to')" />
                    <x-text-input id="date_to" name="date_to" type="date" :value="request('date_to')" />
                </div>
                <div class="form-group" style="display:flex; align-items:flex-end;">
                    <x-primary-button>{{ __('common.filter') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-elevated">
        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('transactions.review_log_list_title') }}</h3>
                <p class="card-subtitle">{{ __('transactions.review_log_list_count', ['count' => $entries->total()]) }}</p>
            </div>
        </div>
        <div class="card-body card-body-flush">
            <div class="table-wrapper">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>{{ __('common.reference_number') }}</th>
                            <th>{{ __('common.title') }}</th>
                            <th>{{ __('common.transaction_type') }}</th>
                            <th>{{ __('common.org_unit') }}</th>
                            <th>{{ __('transactions.action') }}</th>
                            <th>{{ __('transactions.from_status') }}</th>
                            <th>{{ __('transactions.to_status') }}</th>
                            <th>{{ __('transactions.reviewer') }}</th>
                            <th>{{ __('common.notes') }}</th>
                            <th>{{ __('common.date') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($entries as $entry)
                            @php
                                $transaction = $entry->transaction;
                                $historyAction = $entry->action ? \App\Enums\WorkflowAction::tryFrom($entry->action) : null;
                                $canViewTransaction = $transaction && auth()->user()?->canAccessTransaction($transaction);
                            @endphp
                            <tr>
                                <td><code>{{ $transaction?->reference_number ?? '—' }}</code></td>
                                <td>{{ $transaction?->title ?? '—' }}</td>
                                <td>{{ $transaction?->transactionType?->name ?? '—' }}</td>
                                <td>{{ $transaction?->department?->name ?? '—' }}</td>
                                <td>
                                    @if ($historyAction)
                                        <span class="txn-history-action txn-history-action--{{ $entry->action }}">
                                            {{ $historyAction->label() }}
                                        </span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $entry->fromStatus?->name ?? '—' }}</td>
                                <td><x-transaction-status-badge :status="$entry->toStatus" /></td>
                                <td>{{ $entry->changedBy?->name ?? '—' }}</td>
                                <td>{{ $entry->notes ?? '—' }}</td>
                                <td>{{ $entry->created_at?->format('Y-m-d H:i') }}</td>
                                <td>
                                    @if ($canViewTransaction)
                                        <a href="{{ route('transactions.show', $transaction) }}" class="btn btn-secondary btn-sm">{{ __('common.view') }}</a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="table-empty">{{ __('transactions.review_log_empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($entries->hasPages())
                <div class="card-footer">{{ $entries->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
