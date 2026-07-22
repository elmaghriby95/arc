<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1 class="page-title">{{ __('transactions.title') }}</h1>
                <p class="page-subtitle">{{ __('transactions.subtitle') }}</p>
            </div>
            @permission('transactions.create')
                <a href="{{ route('transactions.create') }}" class="btn btn-primary btn-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                    {{ __('transactions.new_button') }}
                </a>
            @endpermission
            @permission('transactions.review-log.view')
                @if (Route::has('transactions.review-log'))
                    <a href="{{ route('transactions.review-log') }}" class="btn btn-secondary btn-lg">
                        {{ __('transactions.review_log_title') }}
                    </a>
                @endif
            @endpermission
        </div>
    </x-slot>

    <div class="card card-elevated">
        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('transactions.filters_title') }}</h3>
                <p class="card-subtitle">{{ __('transactions.filters_subtitle') }}</p>
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
                    <x-input-label for="folder_id" :value="__('common.folder')" />
                    <select id="folder_id" name="folder_id" class="form-select">
                        <option value="">{{ __('common.all') }}</option>
                        @foreach ($folders as $folder)
                            <option value="{{ $folder->id }}" @selected(request('folder_id') == $folder->id)>{{ $folder->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <x-input-label for="transaction_type_id" :value="__('common.transaction_type')" />
                    <select id="transaction_type_id" name="transaction_type_id" class="form-select">
                        <option value="">{{ __('common.all') }}</option>
                        @foreach ($transactionTypes as $type)
                            <option value="{{ $type->id }}" @selected(request('transaction_type_id') == $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <x-input-label for="transaction_status_id" :value="__('common.status')" />
                    <select id="transaction_status_id" name="transaction_status_id" class="form-select">
                        <option value="">{{ __('common.all') }}</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->id }}" @selected(request('transaction_status_id') == $status->id)>{{ $status->name }}</option>
                        @endforeach
                    </select>
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
                <h3 class="card-title">{{ __('transactions.list_title') }}</h3>
                <p class="card-subtitle">{{ __('transactions.list_count', ['count' => $transactions->total()]) }}</p>
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
                            <th>{{ __('common.folder') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th class="table-col-date">{{ __('common.date') }}</th>
                            <th class="table-col-actions"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions as $transaction)
                            <tr>
                                <td class="table-cell-truncate" title="{{ $transaction->archival_reference }}"><code>{{ $transaction->archival_reference }}</code></td>
                                <td class="table-cell-truncate" title="{{ $transaction->title }}">{{ $transaction->title }}</td>
                                <td class="table-cell-truncate" title="{{ $transaction->transactionType?->name }}">{{ $transaction->transactionType?->name ?? '—' }}</td>
                                <td class="table-cell-truncate" title="{{ $transaction->department?->name }}">{{ $transaction->department?->name ?? '—' }}</td>
                                <td class="table-cell-truncate" title="{{ $transaction->folder?->name }}">{{ $transaction->folder?->name ?? '—' }}</td>
                                <td class="table-cell-truncate" title="{{ $transaction->status?->name }}"><x-transaction-status-badge :status="$transaction->status" /></td>
                                <td class="table-col-date">{{ $transaction->transaction_date?->format('Y-m-d') ?? $transaction->created_at->format('Y-m-d') }}</td>
                                <td class="table-col-actions">
                                    <a href="{{ route('transactions.show', $transaction) }}" class="btn btn-secondary btn-sm">{{ __('common.view') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="table-empty">{{ __('transactions.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($transactions->hasPages())
                <div class="card-footer">{{ $transactions->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
