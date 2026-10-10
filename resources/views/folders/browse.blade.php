<x-app-layout>
    @push('styles')
        <x-inline-css file="folder-browse.css" />
    @endpush

    <x-slot name="header">
        <div class="page-header">
            <div>
                <nav class="folder-browse-crumb" aria-label="{{ __('nav.folders') }}">
                    <a href="{{ route('folders.index') }}">{{ __('nav.folders') }}</a>
                    <span aria-hidden="true">/</span>
                    @foreach ($ancestors as $ancestor)
                        <a href="{{ route('folders.browse', $ancestor) }}">{{ $ancestor->name }}</a>
                        <span aria-hidden="true">/</span>
                    @endforeach
                    <span>{{ $folder->name }}</span>
                </nav>
                <h1 class="page-title">{{ $folder->name }}</h1>
            </div>
            @if ($parentFolder)
                <a href="{{ route('folders.browse', $parentFolder) }}" class="btn btn-secondary">{{ __('folders.back_to_parent', ['name' => $parentFolder->name]) }}</a>
            @else
                <a href="{{ route('folders.index') }}" class="btn btn-secondary">{{ __('folders.back_to_list') }}</a>
            @endif
        </div>
    </x-slot>

    <div class="card card-elevated">
        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('folders.info_title') }}</h3>
            </div>
        </div>
        <div class="card-body">
            <dl class="dl-grid">
                <div>
                    <dt>{{ __('folders.org_unit') }}</dt>
                    <dd>{{ $folder->department?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt>{{ __('folders.parent') }}</dt>
                    <dd>
                        @if ($parentFolder)
                            <a href="{{ route('folders.browse', $parentFolder) }}">{{ $parentFolder->name }}</a>
                        @else
                            {{ __('folders.root') }}
                        @endif
                    </dd>
                </div>
                <div>
                    <dt>{{ __('folders.cabinet') }}</dt>
                    <dd>{{ $folder->cabinet_number ?: '—' }}</dd>
                </div>
                <div>
                    <dt>{{ __('folders.row') }}</dt>
                    <dd>{{ $folder->row_number ?: '—' }}</dd>
                </div>
                <div>
                    <dt>{{ __('folders.box') }}</dt>
                    <dd>{{ $folder->box_number ?: '—' }}</dd>
                </div>
                <div>
                    <dt>{{ __('folders.status') }}</dt>
                    <dd>{{ $folder->is_closed ? __('folders.status_closed') : __('folders.status_open') }}</dd>
                </div>
                <div>
                    <dt>{{ __('folders.description') }}</dt>
                    <dd>{{ $folder->description ?: '—' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    @include('folders.partials.search', [
        'action' => route('folders.browse', $folder),
        'inputId' => 'folder-browse-search',
        'placeholder' => __('folders.search_inside'),
    ])

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
                        @include('folders.partials.card', ['folder' => $child])
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
                                    <a href="{{ route('transactions.show', ['transaction' => $transaction, 'from' => 'folder', 'folder' => $folder->id]) }}" class="btn btn-secondary btn-sm">{{ __('common.view') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="table-empty">{{ request()->filled('search') ? __('folders.no_results') : __('folders.empty_transactions') }}</td>
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
