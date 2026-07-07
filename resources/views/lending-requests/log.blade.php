<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1 class="page-title">{{ __('lending_requests.log_title') }}</h1>
                <p class="page-subtitle">{{ __('lending_requests.log_subtitle') }}</p>
            </div>
            @permission('lending-requests.view')
                <a href="{{ route('lending-requests.index') }}" class="btn btn-secondary btn-lg">{{ __('lending_requests.title') }}</a>
            @endpermission
        </div>
    </x-slot>

    <div class="card card-elevated">
        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('lending_requests.log_filters_title') }}</h3>
                <p class="card-subtitle">{{ __('lending_requests.log_filters_subtitle') }}</p>
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
                    <x-input-label for="action" :value="__('lending_requests.action')" />
                    <select id="action" name="action" class="form-select">
                        <option value="">{{ __('common.all') }}</option>
                        @foreach ($actions as $lendingAction)
                            <option value="{{ $lendingAction->value }}" @selected(request('action') === $lendingAction->value)>{{ $lendingAction->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <x-input-label for="date_from" :value="__('lending_requests.log_date_from')" />
                    <x-text-input id="date_from" name="date_from" type="date" :value="request('date_from')" />
                </div>
                <div class="form-group">
                    <x-input-label for="date_to" :value="__('lending_requests.log_date_to')" />
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
                <h3 class="card-title">{{ __('lending_requests.log_list_title') }}</h3>
                <p class="card-subtitle">{{ __('lending_requests.log_list_count', ['count' => $entries->total()]) }}</p>
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
                            <th>{{ __('lending_requests.action') }}</th>
                            <th>{{ __('lending_requests.from_status') }}</th>
                            <th>{{ __('lending_requests.to_status') }}</th>
                            <th>{{ __('lending_requests.performer') }}</th>
                            <th>{{ __('common.notes') }}</th>
                            <th>{{ __('common.date') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($entries as $entry)
                            @php
                                $lendingRequest = $entry->lendingRequest;
                                $transaction = $lendingRequest?->transaction;
                            @endphp
                            <tr>
                                <td><code>{{ $transaction?->reference_number ?? '—' }}</code></td>
                                <td>{{ $transaction?->title ?? '—' }}</td>
                                <td>{{ $transaction?->department?->name ?? '—' }}</td>
                                <td>{{ $lendingRequest?->requester?->name ?? '—' }}</td>
                                <td>{{ $entry->actionLabel() }}</td>
                                <td>{{ $entry->fromStatusLabel() }}</td>
                                <td><x-lending-status-badge :status="$entry->toStatusEnum()" /></td>
                                <td>{{ $entry->performer?->name ?? '—' }}</td>
                                <td>{{ $entry->notes ?? '—' }}</td>
                                <td>{{ $entry->created_at?->format('Y-m-d H:i') }}</td>
                                <td>
                                    @if ($lendingRequest)
                                        <a href="{{ route('lending-requests.show', $lendingRequest) }}" class="btn btn-secondary btn-sm">{{ __('common.view') }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="table-empty">{{ __('lending_requests.log_empty') }}</td>
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
