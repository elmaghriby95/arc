<x-app-layout>
    @push('styles')
        <x-inline-css file="folder-browse.css" />
    @endpush

    <x-slot name="header">
        <div class="page-header">
            <div>
                @if ($ancestors !== [])
                    <nav class="folder-browse-crumb" aria-label="{{ __('nav.folders') }}">
                        @foreach ($ancestors as $ancestor)
                            <a href="{{ route('folders.browse', $ancestor) }}">{{ $ancestor->name }}</a>
                            <span aria-hidden="true">/</span>
                        @endforeach
                        <span>{{ $folder->name }}</span>
                    </nav>
                @endif
                <h1 class="page-title">{{ $folder->name }}</h1>
                @if ($folder->locationLabel() !== '')
                    <p class="page-subtitle">{{ $folder->locationLabel() }}</p>
                @endif
                @if ($folder->is_closed)
                    <p class="page-subtitle">{{ __('settings.folders.closed_badge') }}</p>
                @endif
            </div>
        </div>
    </x-slot>

    @if ($children->isNotEmpty())
        <div class="card card-elevated">
            <div class="card-header">
                <div>
                    <h3 class="card-title">{{ __('folders.subfolders') }}</h3>
                </div>
            </div>
            <div class="card-body">
                <div class="folder-browse-grid">
                    @foreach ($children as $child)
                        <a href="{{ route('folders.browse', $child) }}" class="folder-browse-card">
                            <span class="folder-browse-card-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 0 1 2-2h5l2 2h9a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z"/></svg>
                            </span>
                            <span class="folder-browse-card-copy">
                                <span class="folder-browse-card-name">{{ $child->name }}</span>
                                <span class="folder-browse-card-meta">
                                    {{ __('folders.transactions_count', ['count' => $child->visible_transactions_count]) }}
                                    @if ($child->is_closed)
                                        · {{ __('settings.folders.closed_badge') }}
                                    @endif
                                </span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="card card-elevated">
        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('folders.transactions_title') }}</h3>
                <p class="card-subtitle">{{ __('folders.transactions_count', ['count' => $transactions->total()]) }}</p>
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
                                <td class="table-cell-truncate" title="{{ $transaction->status?->name }}">
                                    @if ($transaction->status)
                                        <x-transaction-status-badge :status="$transaction->status" />
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="table-col-date">{{ $transaction->transaction_date?->format('Y-m-d') ?? $transaction->created_at->format('Y-m-d') }}</td>
                                <td class="table-col-actions">
                                    <a href="{{ route('transactions.show', $transaction) }}" class="btn btn-secondary btn-sm">{{ __('common.view') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="table-empty">{{ __('folders.empty_transactions') }}</td>
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
