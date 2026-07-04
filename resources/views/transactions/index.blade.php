<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1 class="page-title">إدارة الأرشفة</h1>
                <p class="page-subtitle">قائمة المعاملات ضمن نطاقك التنظيمي</p>
            </div>
            @permission('transactions.create')
                <a href="{{ route('transactions.create') }}" class="btn btn-primary btn-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                    معاملة جديدة
                </a>
            @endpermission
        </div>
    </x-slot>

    <div class="card card-elevated">
        <div class="card-header">
            <div>
                <h3 class="card-title">تصفية البحث</h3>
                <p class="card-subtitle">ابحث وفلتر المعاملات حسب الوحدة والمجلد والحالة</p>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="form-grid">
                <div class="form-group">
                    <x-input-label for="search" value="بحث" />
                    <x-text-input id="search" name="search" type="text" :value="request('search')" placeholder="العنوان أو الرقم المرجعي" />
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
                <div class="form-group">
                    <x-input-label for="folder_id" value="المجلد" />
                    <select id="folder_id" name="folder_id" class="form-select">
                        <option value="">الكل</option>
                        @foreach ($folders as $folder)
                            <option value="{{ $folder->id }}" @selected(request('folder_id') == $folder->id)>{{ $folder->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <x-input-label for="transaction_type_id" value="نوع المعاملة" />
                    <select id="transaction_type_id" name="transaction_type_id" class="form-select">
                        <option value="">الكل</option>
                        @foreach ($transactionTypes as $type)
                            <option value="{{ $type->id }}" @selected(request('transaction_type_id') == $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <x-input-label for="transaction_status_id" value="الحالة" />
                    <select id="transaction_status_id" name="transaction_status_id" class="form-select">
                        <option value="">الكل</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->id }}" @selected(request('transaction_status_id') == $status->id)>{{ $status->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="display:flex; align-items:flex-end;">
                    <x-primary-button>تصفية</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-elevated">
        <div class="card-header">
            <div>
                <h3 class="card-title">قائمة المعاملات</h3>
                <p class="card-subtitle">{{ $transactions->total() }} معاملة</p>
            </div>
        </div>
        <div class="card-body card-body-flush">
            <div class="table-wrapper">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>الرقم المرجعي</th>
                            <th>العنوان</th>
                            <th>النوع</th>
                            <th>الوحدة</th>
                            <th>المجلد</th>
                            <th>الحالة</th>
                            <th>التاريخ</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions as $transaction)
                            <tr>
                                <td><code>{{ $transaction->reference_number }}</code></td>
                                <td>{{ $transaction->title }}</td>
                                <td>{{ $transaction->transactionType?->name ?? '—' }}</td>
                                <td>{{ $transaction->department?->name ?? '—' }}</td>
                                <td>{{ $transaction->folder?->name ?? '—' }}</td>
                                <td><x-transaction-status-badge :status="$transaction->status" /></td>
                                <td>{{ $transaction->transaction_date?->format('Y-m-d') ?? $transaction->created_at->format('Y-m-d') }}</td>
                                <td>
                                    <a href="{{ route('transactions.show', $transaction) }}" class="btn btn-secondary btn-sm">عرض</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="table-empty">لا توجد معاملات في نطاقك التنظيمي.</td>
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
