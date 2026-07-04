<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('transactions.index') }}" class="settings-back-link">← العودة للمعاملات</a>
                <h2 class="page-title">معاملة جديدة</h2>
            </div>
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        @unless ($initialStatus)
            <div class="alert alert-warning">
                يجب إعداد <a href="{{ route('settings.transaction-statuses.index') }}">حالات المعاملات</a> في الإعدادات قبل إنشاء معاملة.
            </div>
        @else
            <div class="card">
                <div class="card-body">
                    <p class="form-hint" style="margin-bottom:1rem;">
                        ستُنشأ المعاملة بحالة: <x-transaction-status-badge :status="$initialStatus" />
                    </p>

                    <form method="POST" action="{{ route('transactions.store') }}">
                        @csrf

                        <div class="form-group">
                            <x-input-label for="title" value="عنوان المعاملة" />
                            <x-text-input id="title" name="title" type="text" :value="old('title')" required />
                        </div>

                        <div class="form-group">
                            <x-input-label for="description" value="الوصف" />
                            <textarea id="description" name="description" rows="4" class="form-control">{{ old('description') }}</textarea>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <x-input-label for="department_id" value="الوحدة التنظيمية" />
                                @include('settings.partials.org-unit-select', [
                                    'orgUnits' => $orgUnits,
                                    'selected' => old('department_id', $defaultDepartmentId ?? null),
                                ])
                            </div>
                            <div class="form-group">
                                <x-input-label for="folder_id" value="المجلد" />
                                <select id="folder_id" name="folder_id" class="form-select">
                                    <option value="">—</option>
                                    @foreach ($folders as $folder)
                                        <option value="{{ $folder->id }}" data-department="{{ $folder->department_id }}" @selected(old('folder_id') == $folder->id)>{{ $folder->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <x-input-label for="transaction_type_id" value="نوع المعاملة" />
                                <select id="transaction_type_id" name="transaction_type_id" class="form-select">
                                    <option value="">—</option>
                                    @foreach ($transactionTypes as $type)
                                        <option value="{{ $type->id }}" @selected(old('transaction_type_id') == $type->id)>{{ $type->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <x-input-label for="transaction_date" value="تاريخ المعاملة" />
                                <x-text-input id="transaction_date" name="transaction_date" type="date" :value="old('transaction_date')" />
                            </div>
                        </div>

                        <div class="form-group">
                            <x-input-label for="notes" value="ملاحظات" />
                            <textarea id="notes" name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
                        </div>

                        <div class="form-actions">
                            <x-primary-button>إنشاء المعاملة</x-primary-button>
                            <a href="{{ route('transactions.index') }}" class="btn btn-secondary">إلغاء</a>
                        </div>
                    </form>
                </div>
            </div>
        @endunless
    </div>
</x-app-layout>
