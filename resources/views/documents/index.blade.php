<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1 class="page-title">{{ __('documents.title') }}</h1>
                <p class="page-subtitle">{{ __('documents.subtitle') }}</p>
            </div>
            @permission('transactions.create')
                <a href="{{ route('transactions.create') }}" class="btn btn-primary btn-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                    {{ __('documents.create_transaction') }}
                </a>
            @endpermission
        </div>
    </x-slot>

    <div class="card card-elevated">
        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('documents.filters_title') }}</h3>
                <p class="card-subtitle">{{ __('documents.filters_subtitle') }}</p>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="form-grid">
                <div class="form-group">
                    <x-input-label for="search" :value="__('common.search')" />
                    <x-text-input id="search" name="search" type="text" :value="request('search')" :placeholder="__('documents.search_placeholder')" />
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
                <div class="form-group" style="display:flex; align-items:flex-end;">
                    <x-primary-button>{{ __('common.filter') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-elevated">
        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('documents.list_title') }}</h3>
                <p class="card-subtitle">{{ __('documents.list_count', ['count' => $attachments->total()]) }}</p>
            </div>
        </div>
        <div class="card-body card-body-flush">
            <div class="table-wrapper">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>{{ __('documents.document') }}</th>
                            <th>{{ __('documents.transaction') }}</th>
                            <th>{{ __('common.department') }}</th>
                            <th>{{ __('documents.file_kind') }}</th>
                            <th>{{ __('documents.uploaded_by') }}</th>
                            <th>{{ __('common.date') }}</th>
                            <th>{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($attachments as $attachment)
                            <tr>
                                <td class="table-title">{{ $attachment->displayName() }}</td>
                                <td>
                                    <a href="{{ route('transactions.show', $attachment->transaction) }}" class="ref-pill">{{ $attachment->transaction->reference_number }}</a>
                                </td>
                                <td>{{ $attachment->transaction->department?->name ?? '—' }}</td>
                                <td>{{ strtoupper($attachment->fileKind()) }}</td>
                                <td>{{ $attachment->uploader?->name ?? '—' }}</td>
                                <td class="text-muted">{{ $attachment->created_at->format('Y-m-d') }}</td>
                                <td class="table-actions">
                                    <a href="{{ route('documents.show', $attachment) }}">{{ __('common.view') }}</a>
                                    @permission('documents.download')
                                        <a href="{{ route('documents.download', $attachment) }}">{{ __('common.download') }}</a>
                                    @endpermission
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state" style="padding:2rem;">
                                        <p class="text-muted">{{ __('documents.empty') }}</p>
                                        @permission('transactions.create')
                                            <a href="{{ route('transactions.create') }}" class="btn btn-primary btn-sm">{{ __('documents.empty_action') }}</a>
                                        @endpermission
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div style="padding: 1rem 1.5rem;">{{ $attachments->links() }}</div>
        </div>
    </div>
</x-app-layout>
