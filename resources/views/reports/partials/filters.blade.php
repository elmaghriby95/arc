<form method="GET" class="reports-filters form-grid">
    <div class="form-group">
        <x-input-label for="date_from" value="من تاريخ" />
        <x-text-input id="date_from" name="date_from" type="date" :value="request('date_from')" />
    </div>
    <div class="form-group">
        <x-input-label for="date_to" value="إلى تاريخ" />
        <x-text-input id="date_to" name="date_to" type="date" :value="request('date_to')" />
    </div>
    <div class="form-group">
        <x-input-label for="department_id" value="الوحدة التنظيمية" />
        @include('settings.partials.org-unit-select', [
            'orgUnits' => $orgUnits,
            'selected' => request('department_id'),
            'placeholder' => '— الكل —',
            'showHint' => false,
        ])
    </div>
    @if ($showTypeFilter ?? true)
        <div class="form-group">
            <x-input-label for="transaction_type_id" value="نوع المعاملة" />
            <select id="transaction_type_id" name="transaction_type_id" class="form-select">
                <option value="">الكل</option>
                @foreach ($transactionTypes as $type)
                    <option value="{{ $type->id }}" @selected(request('transaction_type_id') == $type->id)>{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    @if ($showStatusFilter ?? true)
        <div class="form-group">
            <x-input-label for="transaction_status_id" value="الحالة" />
            <select id="transaction_status_id" name="transaction_status_id" class="form-select">
                <option value="">الكل</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->id }}" @selected(request('transaction_status_id') == $status->id)>{{ $status->name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    @if ($showStaleDays ?? false)
        <div class="form-group">
            <x-input-label for="stale_days" value="مدة التوقف (أيام)" />
            <x-text-input id="stale_days" name="stale_days" type="number" min="1" max="365" :value="request('stale_days', 7)" />
        </div>
    @endif
    <div class="form-group reports-filters-actions">
        <x-primary-button>تطبيق الفلاتر</x-primary-button>
        <a href="{{ route('reports.show', $reportType) }}" class="btn btn-secondary">إعادة تعيين</a>
    </div>
</form>
