<form method="GET" class="reports-filters form-grid">
    <div class="form-group">
        <x-input-label for="date_from" :value="__('reports.date_from')" />
        <x-text-input id="date_from" name="date_from" type="date" :value="request('date_from')" />
    </div>
    <div class="form-group">
        <x-input-label for="date_to" :value="__('reports.date_to')" />
        <x-text-input id="date_to" name="date_to" type="date" :value="request('date_to')" />
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
    @if ($showTypeFilter ?? true)
        <div class="form-group">
            <x-input-label for="transaction_type_id" :value="__('common.transaction_type')" />
            <select id="transaction_type_id" name="transaction_type_id" class="form-select">
                <option value="">{{ __('common.all') }}</option>
                @foreach ($transactionTypes as $type)
                    <option value="{{ $type->id }}" @selected(request('transaction_type_id') == $type->id)>{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    @if ($showStatusFilter ?? true)
        <div class="form-group">
            <x-input-label for="transaction_status_id" :value="__('common.status')" />
            <select id="transaction_status_id" name="transaction_status_id" class="form-select">
                <option value="">{{ __('common.all') }}</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->id }}" @selected(request('transaction_status_id') == $status->id)>{{ $status->name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    @if ($showStaleDays ?? false)
        <div class="form-group">
            <x-input-label for="stale_days" :value="__('reports.stale_days')" />
            <x-text-input id="stale_days" name="stale_days" type="number" min="1" max="365" :value="request('stale_days', 7)" />
        </div>
    @endif
    <div class="form-group reports-filters-actions">
        <x-primary-button>{{ __('reports.apply_filters') }}</x-primary-button>
        <a href="{{ route('reports.show', $reportType) }}" class="btn btn-secondary">{{ __('common.reset') }}</a>
    </div>
</form>
