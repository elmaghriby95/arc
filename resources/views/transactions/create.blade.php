<x-app-layout>
    <x-slot name="header">
        <div class="page-header txn-create-header">
            <div>
                <a href="{{ route('transactions.index') }}" class="settings-back-link">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5m0 0l7 7m-7-7l7-7"/></svg>
                    العودة للمعاملات
                </a>
                <h2 class="page-title">معاملة جديدة</h2>
                <p class="page-subtitle">أدخل بيانات المعاملة، اختر المجلد، وأرفق المستندات مع أرقامها الإشارية</p>
            </div>
        </div>
    </x-slot>

    <div class="container txn-create-page">
        @if (! $initialStatus)
            <div class="txn-create-empty">
                <div class="txn-create-empty-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                </div>
                <h3>إعدادات غير مكتملة</h3>
                <p>يجب إعداد <a href="{{ route('settings.transaction-statuses.index') }}">حالات المعاملات</a> قبل إنشاء معاملة.</p>
            </div>
        @else
            @if ($errors->any())
                <div class="txn-create-errors" role="alert">
                    <div class="txn-create-errors-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 8v4m0 4h.01"/></svg>
                    </div>
                    <div>
                        <strong>يرجى تصحيح الأخطاء التالية</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <form method="POST"
                  action="{{ route('transactions.store') }}"
                  enctype="multipart/form-data"
                  class="txn-create-form"
                  data-txn-create-form
                  data-ref-config='@json($referenceFormConfig)'>
                @csrf

                <div class="txn-create-hero">
                    <div class="txn-create-hero-steps">
                        <span class="txn-create-step is-active">
                            <span class="txn-create-step-num">1</span>
                            بيانات المعاملة
                        </span>
                        <span class="txn-create-step-divider" aria-hidden="true"></span>
                        <span class="txn-create-step">
                            <span class="txn-create-step-num">2</span>
                            المجلد والمستندات
                        </span>
                    </div>
                    <div class="txn-create-hero-status">
                        <span>الحالة الابتدائية</span>
                        <x-transaction-status-badge :status="$initialStatus" />
                    </div>
                </div>

                <div class="txn-create-layout">
                    <aside class="txn-create-archive">
                        <section class="txn-create-section {{ $errors->has('folder_id') ? 'has-error' : '' }}">
                            <header class="txn-create-section-head">
                                <span class="txn-create-section-icon txn-create-section-icon--folder" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h5l2 2h9a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                                </span>
                                <div>
                                    <h3>المجلد</h3>
                                    <p>مكان حفظ المعاملة في شجرة الأرشفة</p>
                                </div>
                            </header>
                            <div class="txn-create-section-body">
                                <div class="txn-folder-selected is-empty" data-folder-selected>
                                    <span class="txn-folder-selected-icon" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h5l2 2h9a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                                    </span>
                                    <div>
                                        <span class="txn-folder-selected-label">لم يُحدد مجلد بعد</span>
                                        <span class="txn-folder-selected-name" data-folder-selected-name>—</span>
                                    </div>
                                </div>

                                @include('transactions.partials.folder-tree-picker', [
                                    'folders' => $folders,
                                    'folderTree' => $folderTree,
                                    'selected' => old('folder_id'),
                                ])
                            </div>
                        </section>

                        <section class="txn-create-section {{ $errors->has('files') ? 'has-error' : '' }}">
                            <header class="txn-create-section-head">
                                <span class="txn-create-section-icon txn-create-section-icon--docs" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </span>
                                <div>
                                    <h3>المستندات</h3>
                                    <p>كل مستند يحتاج رقماً إشارياً</p>
                                </div>
                            </header>
                            <div class="txn-create-section-body">
                                @if ($referenceSettings->operational_number_enabled)
                                    <div class="txn-ref-disclaimer">
                                        {{ $referenceSettings->operational_number_disclaimer }}
                                    </div>
                                @endif

                                <div class="txn-dropzone txn-dropzone--create"
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
                                        <p class="txn-dropzone-title">اسحب الملفات هنا</p>
                                        <p class="txn-dropzone-hint">PDF · Word · Excel · صور — حتى 20 MB</p>
                                        <button type="button" class="btn btn-secondary btn-sm" data-txn-browse>تصفح الملفات</button>
                                    </div>
                                    <div class="txn-dropzone-queue is-hidden" data-txn-queue>
                                        <ul class="txn-doc-upload-list" data-txn-file-list></ul>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="txn-create-section">
                            <header class="txn-create-section-head">
                                <span class="txn-create-section-icon txn-create-section-icon--text" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h10M4 18h7"/></svg>
                                </span>
                                <div>
                                    <h3>الوصف والملاحظات</h3>
                                    <p>تفاصيل إضافية اختيارية</p>
                                </div>
                            </header>
                            <div class="txn-create-section-body">
                                <div class="form-group form-group--compact">
                                    <x-input-label for="description" value="الوصف" />
                                    <textarea id="description" name="description" rows="3" class="form-control form-control--compact" placeholder="وصف مختصر للمعاملة...">{{ old('description') }}</textarea>
                                </div>
                                <div class="form-group form-group--compact" style="margin-bottom:0">
                                    <x-input-label for="notes" value="ملاحظات" />
                                    <textarea id="notes" name="notes" rows="2" class="form-control form-control--compact" placeholder="ملاحظات داخلية...">{{ old('notes') }}</textarea>
                                </div>
                            </div>
                        </section>
                    </aside>

                    <div class="txn-create-main">
                        <div class="txn-create-main-card {{ $errors->has('title') || $errors->has('department_id') ? 'has-error' : '' }}">
                            <header class="txn-create-main-head">
                                <span class="txn-create-section-icon txn-create-section-icon--data" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                </span>
                                <div>
                                    <h3>البيانات الأساسية</h3>
                                    <p>معلومات المعاملة المطلوبة للتسجيل</p>
                                </div>
                            </header>

                            <div class="txn-create-main-body">
                                <div class="form-group form-group--compact">
                                    <x-input-label for="title" value="عنوان المعاملة" />
                                    <x-text-input id="title" name="title" type="text" class="form-control--compact" :value="old('title')" required autofocus placeholder="مثال: طلب صيانة معدات المختبر" />
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
                                    </div>

                                    <div class="form-group form-group--compact">
                                        <x-input-label for="transaction_type_id" value="نوع المعاملة" />
                                        <select id="transaction_type_id" name="transaction_type_id" class="form-select form-control--compact">
                                            <option value="">— اختياري —</option>
                                            @foreach ($transactionTypes as $type)
                                                <option value="{{ $type->id }}" data-type-code="{{ $type->code }}" @selected(old('transaction_type_id') == $type->id)>{{ $type->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group form-group--compact">
                                        <x-input-label for="transaction_date" value="تاريخ المعاملة" />
                                        <x-text-input id="transaction_date" name="transaction_date" type="date" class="form-control--compact" :value="old('transaction_date', now()->format('Y-m-d'))" />
                                    </div>
                                </div>
                            </div>

                            <footer class="txn-create-main-footer">
                                <p class="txn-create-footer-hint">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 16v-4M12 8h.01"/></svg>
                                    تأكد من اختيار المجلد قبل الحفظ
                                </p>
                                <div class="txn-create-actions">
                                    <button type="submit" class="btn btn-primary btn-lg txn-create-submit" {{ $folders->isEmpty() ? 'disabled' : '' }}>
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" aria-hidden="true"><path stroke-linecap="round" d="M5 13l4 4L19 7"/></svg>
                                        إنشاء المعاملة
                                    </button>
                                    <a href="{{ route('transactions.index') }}" class="btn btn-ghost">إلغاء</a>
                                </div>
                            </footer>
                        </div>
                    </div>
                </div>
            </form>
        @endif
    </div>

    @push('scripts')
        <script src="{{ asset('js/transaction-create.js') }}"></script>
    @endpush
</x-app-layout>
