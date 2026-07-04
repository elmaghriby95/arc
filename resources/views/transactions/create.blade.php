<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('transactions.index') }}" class="settings-back-link">← العودة للمعاملات</a>
                <h2 class="page-title">معاملة جديدة</h2>
            </div>
        </div>
    </x-slot>

    <div class="container txn-create-page">
        <x-flash-messages />

        @unless ($initialStatus)
            <div class="alert alert-warning">
                يجب إعداد <a href="{{ route('settings.transaction-statuses.index') }}">حالات المعاملات</a> في الإعدادات قبل إنشاء معاملة.
            </div>
        @else
            <form method="POST"
                  action="{{ route('transactions.store') }}"
                  enctype="multipart/form-data"
                  class="txn-create-form"
                  data-txn-create-form
                  data-ref-config='@json($referenceFormConfig)'>
                @csrf

                <div class="txn-create-layout">
                    <aside class="txn-create-sidebar">
                        <section class="txn-create-panel">
                            <div class="txn-create-panel-head">
                                <h3>المجلد والنص</h3>
                                <p>اختر مكان الحفظ وأدخل وصف المعاملة</p>
                            </div>
                            <div class="txn-create-panel-body">
                                <div class="form-group form-group--compact">
                                    <x-input-label value="المجلد" />
                                    @include('transactions.partials.folder-tree-picker', [
                                        'folders' => $folders,
                                        'folderTree' => $folderTree,
                                        'selected' => old('folder_id'),
                                    ])
                                    @error('folder_id')
                                        <p class="form-error">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="form-group form-group--compact">
                                    <x-input-label for="description" value="الوصف" />
                                    <textarea id="description" name="description" rows="4" class="form-control form-control--compact" placeholder="وصف مختصر للمعاملة...">{{ old('description') }}</textarea>
                                </div>

                                <div class="form-group form-group--compact">
                                    <x-input-label for="notes" value="ملاحظات" />
                                    <textarea id="notes" name="notes" rows="3" class="form-control form-control--compact" placeholder="ملاحظات داخلية...">{{ old('notes') }}</textarea>
                                </div>
                            </div>
                        </section>

                        <section class="txn-create-panel">
                            <div class="txn-create-panel-head">
                                <h3>مستندات المعاملة</h3>
                                <p>كل مستند يحتاج رقماً إشارياً حسب الإعدادات</p>
                            </div>
                            <div class="txn-create-panel-body">
                                @if ($referenceSettings->operational_number_enabled)
                                    <div class="txn-ref-disclaimer">
                                        {{ $referenceSettings->operational_number_disclaimer }}
                                    </div>
                                @endif

                                <div class="txn-dropzone txn-dropzone--compact"
                                     data-txn-dropzone
                                     data-txn-create-dropzone>
                                    <input type="file"
                                           name="files[]"
                                           class="txn-dropzone-input"
                                           data-txn-file-input
                                           multiple
                                           accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp,.bmp,image/*,application/pdf">
                                    <div class="txn-dropzone-content" data-txn-dropzone-content>
                                        <div class="txn-dropzone-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                                        </div>
                                        <p class="txn-dropzone-title">اسحب الملفات أو اخترها</p>
                                        <p class="txn-dropzone-hint">PDF · Word · Excel · صور</p>
                                        <button type="button" class="btn btn-secondary btn-sm" data-txn-browse>اختيار ملفات</button>
                                    </div>
                                    <div class="txn-dropzone-queue is-hidden" data-txn-queue>
                                        <ul class="txn-doc-upload-list" data-txn-file-list></ul>
                                    </div>
                                </div>
                                @error('files')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </section>
                    </aside>

                    <div class="txn-create-main">
                        <section class="card txn-create-card">
                            <div class="card-header txn-create-card-header">
                                <div>
                                    <h3 class="card-title">بيانات المعاملة</h3>
                                    <p class="txn-create-status-hint">
                                        الحالة الابتدائية:
                                        <x-transaction-status-badge :status="$initialStatus" />
                                    </p>
                                </div>
                            </div>
                            <div class="card-body txn-create-card-body">
                                <div class="form-group form-group--compact">
                                    <x-input-label for="title" value="عنوان المعاملة" />
                                    <x-text-input id="title" name="title" type="text" class="form-control--compact" :value="old('title')" required autofocus />
                                    @error('title')
                                        <p class="form-error">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="txn-create-fields-grid">
                                    <div class="form-group form-group--compact">
                                        <x-input-label for="department_id" value="الوحدة التنظيمية" />
                                        @include('settings.partials.org-unit-select', [
                                            'orgUnits' => $orgUnits,
                                            'selected' => old('department_id', $defaultDepartmentId ?? null),
                                            'selectClass' => 'form-select form-control--compact',
                                            'showHint' => false,
                                            'required' => true,
                                            'placeholder' => '— اختر الوحدة —',
                                        ])
                                        @error('department_id')
                                            <p class="form-error">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="form-group form-group--compact">
                                        <x-input-label for="transaction_type_id" value="نوع المعاملة" />
                                        <select id="transaction_type_id" name="transaction_type_id" class="form-select form-control--compact">
                                            <option value="">—</option>
                                            @foreach ($transactionTypes as $type)
                                                <option value="{{ $type->id }}" data-type-code="{{ $type->code }}" @selected(old('transaction_type_id') == $type->id)>{{ $type->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group form-group--compact">
                                        <x-input-label for="transaction_date" value="تاريخ المعاملة" />
                                        <x-text-input id="transaction_date" name="transaction_date" type="date" class="form-control--compact" :value="old('transaction_date')" />
                                    </div>
                                </div>

                                <div class="txn-create-actions">
                                    <x-primary-button @disabled($folders->isEmpty())>إنشاء المعاملة</x-primary-button>
                                    <a href="{{ route('transactions.index') }}" class="btn btn-secondary btn-sm">إلغاء</a>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </form>
        @endunless
    </div>

    @push('scripts')
        <script src="{{ asset('js/transaction-create.js') }}"></script>
    @endpush
</x-app-layout>
