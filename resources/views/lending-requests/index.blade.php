<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1 class="page-title">{{ __('lending_requests.title') }}</h1>
                <p class="page-subtitle">{{ __('lending_requests.filters_subtitle') }}</p>
            </div>
            <div class="form-actions">
                @permission('lending-requests.log.view')
                    <a href="{{ route('lending-requests.log') }}" class="btn btn-secondary btn-lg">{{ __('lending_requests.log_title') }}</a>
                @endpermission
            </div>
        </div>
    </x-slot>

    <div class="card card-elevated">
        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('lending_requests.filters_title') }}</h3>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="form-grid">
                <div class="form-group">
                    <x-input-label for="search" :value="__('common.search')" />
                    <x-text-input id="search" name="search" type="text" :value="request('search')" :placeholder="__('transactions.search_placeholder')" />
                </div>
                <div class="form-group">
                    <x-input-label for="status" :value="__('common.status')" />
                    <select id="status" name="status" class="form-select">
                        <option value="">{{ __('common.all') }}</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
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
                <h3 class="card-title">{{ __('lending_requests.list_title') }}</h3>
                <p class="card-subtitle">{{ __('lending_requests.list_count', ['count' => $requests->total()]) }}</p>
            </div>
        </div>
        <div class="card-body card-body-flush">
            <div class="table-wrapper">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>{{ __('common.reference_number') }}</th>
                            <th>{{ __('common.title') }}</th>
                            <th>{{ __('common.org_unit') }}</th>
                            <th>{{ __('lending_requests.requester') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th>{{ __('common.date') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $lendingRequest)
                            @php $transaction = $lendingRequest->transaction; @endphp
                            <tr>
                                <td><code>{{ $transaction?->reference_number ?? '—' }}</code></td>
                                <td>{{ $transaction?->title ?? '—' }}</td>
                                <td>{{ $transaction?->department?->name ?? '—' }}</td>
                                <td>{{ $lendingRequest->requester?->name ?? '—' }}</td>
                                <td><x-lending-status-badge :status="$lendingRequest->status" /></td>
                                <td>{{ $lendingRequest->created_at?->format('Y-m-d H:i') }}</td>
                                <td>
                                    <a href="{{ route('lending-requests.show', $lendingRequest) }}" class="btn btn-secondary btn-sm">{{ __('common.view') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="table-empty">{{ __('lending_requests.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($requests->hasPages())
                <div class="card-footer">{{ $requests->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
