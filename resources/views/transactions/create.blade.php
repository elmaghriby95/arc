<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('transactions.index') }}" class="settings-back-link">← العودة للمعاملات</a>
                <h2 class="page-title">معاملة جديدة</h2>
            </div>
        </div>
    </x-slot>

    @once
        @php($txCreateCss = resource_path('css/transaction-create.css'))
        @if (is_readable($txCreateCss))
            <style>{!! file_get_contents($txCreateCss) !!}</style>
        @endif
    @endonce

    <div class="txnw-page">
        <div class="txnw-wrap">
            @if (! $initialStatus)
                <div class="txnw-alert txnw-alert--warn txnw-alert--center">
                    يجب إعداد <a href="{{ route('settings.transaction-statuses.index') }}">حالات المعاملات</a> قبل الإنشاء.
                </div>
            @else
                {{-- بطاقة علوية: مسار سير العمل --}}
                <div class="txnw-hero">
                    <div class="txnw-hero-top">
                        <div>
                            <span class="txnw-hero-tag">إنشاء معاملة</span>
                            <h3 class="txnw-hero-title">ابدأ معاملة جديدة في النظام</h3>
                            <p class="txnw-hero-desc">أكمل الخطوات الثلاث ثم احفظ — ستبدأ المعاملة تلقائياً بالحالة الأولى في المسار</p>
                        </div>
                        <div class="txnw-hero-status">
                            <span class="txnw-hero-status-label">الحالة الابتدائية</span>
                            <x-transaction-status-badge :status="$initialStatus" />
                        </div>
                    </div>

                    @if ($workflow->isNotEmpty())
                        <div class="txnw-workflow" aria-label="مسار سير العمل">
                            <span class="txnw-workflow-title">مسار المعاملة بعد الإنشاء</span>
                            <div class="txnw-workflow-track">
                                @foreach ($workflow as $step)
                                    <div class="txnw-workflow-step {{ $step->id === $initialStatus->id ? 'is-current' : '' }} {{ $step->sort_order < ($initialStatus->sort_order ?? 0) ? 'is-past' : '' }}">
                                        <span class="txnw-workflow-dot" style="--step-color: {{ $step->color ?? '#6366f1' }}"></span>
                                        <span class="txnw-workflow-name">{{ $step->name }}</span>
                                    </div>
                                    @unless ($loop->last)
                                        <span class="txnw-workflow-connector" aria-hidden="true"></span>
                                    @endunless
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                @if ($errors->any())
                    <div class="txnw-alert txnw-alert--error" role="alert">
                        <strong>تحقق من الحقول:</strong>
                        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif

                <form method="POST"
                      action="{{ route('transactions.store') }}"
                      enctype="multipart/form-data"
                      class="txnw-form"
                      data-txn-create-form
                      data-ref-config='@json($referenceFormConfig)'
                      data-initial-step="{{ $errors->has('folder_id') ? 2 : 1 }}">
                    @csrf

                    <div class="txnw-card">
                        {{-- شريط الخطوات الأفقي --}}
                        <nav class="txnw-steps-bar" aria-label="خطوات الإنشاء">
                            <button type="button" class="txnw-step-pill is-active" data-go-step="1">
                                <span class="txnw-step-pill-num">1</span>
                                <span class="txnw-step-pill-text">البيانات الأساسية</span>
                            </button>
                            <span class="txnw-step-connector" aria-hidden="true"></span>
                            <button type="button" class="txnw-step-pill" data-go-step="2">
                                <span class="txnw-step-pill-num">2</span>
                                <span class="txnw-step-pill-text">المجلد والوصف</span>
                            </button>
                            <span class="txnw-step-connector" aria-hidden="true"></span>
                            <button type="button" class="txnw-step-pill" data-go-step="3">
                                <span class="txnw-step-pill-num">3</span>
                                <span class="txnw-step-pill-text">المستندات</span>
                            </button>
                        </nav>

                        <div class="txnw-card-body">
                            {{-- الخطوة 1 --}}
                            <section class="txnw-step is-visible" data-step="1">
                                <header class="txnw-step-head">
                                    <h3>البيانات الأساسية</h3>
                                    <p>أدخل معلومات المعاملة المطلوبة للتسجيل</p>
                                </header>

                                <div class="txnw-box">
                                    <div class="txnw-field txnw-field--full">
                                        <label for="title">عنوان المعاملة <span class="txnw-req">*</span></label>
                                        <input type="text" id="title" name="title" class="txnw-input" value="{{ old('title') }}" required autofocus placeholder="مثال: طلب صيانة معدات المختبر">
                                    </div>
                                    <div class="txnw-row txnw-row--3">
                                        <div class="txnw-field">
                                            <label for="department_id">الوحدة التنظيمية <span class="txnw-req">*</span></label>
                                            @include('settings.partials.org-unit-select', [
                                                'orgUnits' => $orgUnits,
                                                'selected' => old('department_id', $defaultDepartmentId ?? null),
                                                'selectClass' => 'txnw-input txnw-select',
                                                'showHint' => false,
                                                'required' => true,
                                                'placeholder' => '— اختر الوحدة —',
                                            ])
                                        </div>
                                        <div class="txnw-field">
                                            <label for="transaction_type_id">نوع المعاملة</label>
                                            <select id="transaction_type_id" name="transaction_type_id" class="txnw-input txnw-select">
                                                <option value="">— اختياري —</option>
                                                @foreach ($transactionTypes as $type)
                                                    <option value="{{ $type->id }}" data-type-code="{{ $type->code }}" @selected(old('transaction_type_id') == $type->id)>{{ $type->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="txnw-field">
                                            <label for="transaction_date">تاريخ المعاملة</label>
                                            <input type="date" id="transaction_date" name="transaction_date" class="txnw-input" value="{{ old('transaction_date', now()->format('Y-m-d')) }}">
                                        </div>
                                    </div>
                                </div>

                                <footer class="txnw-step-foot">
                                    <span></span>
                                    <button type="button" class="txnw-btn txnw-btn--primary txnw-btn--lg" data-next-step>التالي ←</button>
                                </footer>
                            </section>

                            {{-- الخطوة 2 --}}
                            <section class="txnw-step" data-step="2">
                                <header class="txnw-step-head">
                                    <h3>المجلد والوصف</h3>
                                    <p>حدد مكان حفظ المعاملة في شجرة الأرشفة — يجب أن يطابق المجلد الوحدة التنظيمية في الخطوة الأولى</p>
                                </header>

                                <div class="txnw-box {{ $errors->has('folder_id') ? 'txnw-box--error' : '' }}">
                                    <div class="txnw-field txnw-field--full">
                                        <label>المجلد <span class="txnw-req">*</span></label>
                                        <div class="txnw-folder-selected is-empty" data-folder-selected>
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h5l2 2h9a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                                            <span data-folder-selected-name>لم يُحدد مجلد بعد — اختر من القائمة</span>
                                        </div>
                                        @include('transactions.partials.folder-tree-picker', [
                                            'folders' => $folders,
                                            'folderTree' => $folderTree,
                                            'selected' => old('folder_id'),
                                            'departmentBreadcrumbs' => $departmentBreadcrumbs ?? [],
                                        ])
                                    </div>
                                    <div class="txnw-row">
                                        <div class="txnw-field">
                                            <label for="description">الوصف</label>
                                            <textarea id="description" name="description" class="txnw-input txnw-textarea" rows="3" placeholder="وصف مختصر للمعاملة...">{{ old('description') }}</textarea>
                                        </div>
                                        <div class="txnw-field">
                                            <label for="notes">ملاحظات</label>
                                            <textarea id="notes" name="notes" class="txnw-input txnw-textarea" rows="3" placeholder="ملاحظات داخلية...">{{ old('notes') }}</textarea>
                                        </div>
                                    </div>
                                </div>

                                <footer class="txnw-step-foot">
                                    <button type="button" class="txnw-btn txnw-btn--ghost txnw-btn--lg" data-prev-step>→ السابق</button>
                                    <button type="button" class="txnw-btn txnw-btn--primary txnw-btn--lg" data-next-step>التالي ←</button>
                                </footer>
                            </section>

                            {{-- الخطوة 3 --}}
                            <section class="txnw-step" data-step="3">
                                <header class="txnw-step-head">
                                    <h3>المستندات</h3>
                                    <p>ارفع ملفات المعاملة مع الرقم الإشاري لكل مستند</p>
                                </header>

                                <div class="txnw-box">
                                    @if ($referenceSettings->operational_number_enabled && $referenceSettings->operational_number_disclaimer)
                                        <p class="txnw-hint txnw-hint--warn">{{ $referenceSettings->operational_number_disclaimer }}</p>
                                    @endif

                                    <div class="txnw-upload" data-txn-create-dropzone>
                                        <input type="file" name="files[]" class="txnw-upload-input" data-txn-file-input multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp,.bmp">
                                        <div class="txnw-upload-empty" data-txn-dropzone-content>
                                            <div class="txnw-upload-icon">
                                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                                            </div>
                                            <span class="txnw-upload-title">اسحب الملفات إلى هنا</span>
                                            <span class="txnw-upload-sub">أو</span>
                                            <button type="button" class="txnw-btn txnw-btn--outline" data-txn-browse>اختر ملفات من الجهاز</button>
                                            <small>PDF · Word · Excel · صور — حتى 20 MB للملف</small>
                                        </div>
                                        <ul class="txnw-upload-list is-hidden" data-txn-file-list data-txn-queue></ul>
                                    </div>
                                </div>

                                <footer class="txnw-step-foot">
                                    <button type="button" class="txnw-btn txnw-btn--ghost txnw-btn--lg" data-prev-step>→ السابق</button>
                                    <button type="submit" class="txnw-btn txnw-btn--success txnw-btn--lg txnw-btn--submit" {{ $folders->isEmpty() ? 'disabled' : '' }}>
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" d="M5 13l4 4L19 7"/></svg>
                                        إنشاء المعاملة
                                    </button>
                                </footer>
                            </section>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>

    @once
        @php($txCreateJs = resource_path('js/transaction-create.js'))
        @if (is_readable($txCreateJs))
            <script>{!! file_get_contents($txCreateJs) !!}</script>
        @endif
    @endonce
</x-app-layout>
