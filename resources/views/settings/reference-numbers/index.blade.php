<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('settings.index') }}" class="settings-back-link">← العودة للإعدادات</a>
                <h2 class="page-title">إعدادات الرقم الإشاري</h2>
                <p class="page-subtitle">تكوين قواعد الأرقام الإشارية وأرقام المستندات والرقم التشغيلي</p>
            </div>
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        <div class="ref-settings-info">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
            </svg>
            <p>تُطبَّق هذه الإعدادات على نماذج إدخال المستندات والمعاملات، وتتحكم في سلوك الأرقام الإشارية والتشغيلية.</p>
        </div>

        <form method="POST" action="{{ route('settings.reference-numbers.update') }}" class="ref-form">
            @csrf
            @method('PUT')

            <div class="ref-settings-grid">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">الحقول والإدخال</h3>
                    </div>
                    <div class="card-body ref-settings-toggles">
                        <label class="ref-settings-toggle">
                            <input type="checkbox" name="auto_assign_department" value="1" @checked(old('auto_assign_department', $settings->auto_assign_department))>
                            <span class="ref-settings-toggle-body">
                                <strong>تعيين القسم تلقائياً</strong>
                                <small>عرض القسم ورقمه بناءً على انتماء المستخدم المسجّل</small>
                            </span>
                        </label>

                        <label class="ref-settings-toggle">
                            <input type="checkbox" name="allow_previous_years" value="1" @checked(old('allow_previous_years', $settings->allow_previous_years))>
                            <span class="ref-settings-toggle-body">
                                <strong>السماح بإدخال سنوات سابقة</strong>
                                <small>تمكين المستخدم من اختيار سنة غير السنة الحالية</small>
                            </span>
                        </label>

                        <label class="ref-settings-toggle">
                            <input type="checkbox" name="month_optional" value="1" @checked(old('month_optional', $settings->month_optional))>
                            <span class="ref-settings-toggle-body">
                                <strong>الشهر اختياري</strong>
                                <small>عدم إلزام المستخدم بإدخال الشهر في الرقم الإشاري</small>
                            </span>
                        </label>

                        <label class="ref-settings-toggle">
                            <input type="checkbox" name="original_document_number_optional" value="1" @checked(old('original_document_number_optional', $settings->original_document_number_optional))>
                            <span class="ref-settings-toggle-body">
                                <strong>رقم المستند الأصلي اختياري</strong>
                                <small>إذا لم يكن موجوداً على المستند الورقي</small>
                            </span>
                        </label>

                        <label class="ref-settings-toggle">
                            <input type="checkbox" name="allow_free_format_reference" value="1" @checked(old('allow_free_format_reference', $settings->allow_free_format_reference))>
                            <span class="ref-settings-toggle-body">
                                <strong>إدخال الرقم الإشاري بحرية</strong>
                                <small>كما هو مكتوب دون فرض تنسيق محدد</small>
                            </span>
                        </label>

                        <label class="ref-settings-toggle">
                            <input type="checkbox" name="support_multilingual_characters" value="1" @checked(old('support_multilingual_characters', $settings->support_multilingual_characters))>
                            <span class="ref-settings-toggle-body">
                                <strong>دعم الأحرف متعددة اللغات</strong>
                                <small>العربية والإنجليزية والفرنسية والأرقام والرموز</small>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">الرقم التشغيلي</h3>
                    </div>
                    <div class="card-body">
                        <label class="ref-settings-toggle ref-settings-toggle--block">
                            <input type="checkbox" name="operational_number_enabled" value="1" @checked(old('operational_number_enabled', $settings->operational_number_enabled))>
                            <span class="ref-settings-toggle-body">
                                <strong>تفعيل الرقم التشغيلي</strong>
                                <small>للمستندات التي لا تحتوي على رقم إشاري رسمي</small>
                            </span>
                        </label>

                        <div class="form-grid">
                            <div class="form-group">
                                <x-input-label for="operational_number_separator" value="فاصل الأجزاء" />
                                <x-text-input id="operational_number_separator" name="operational_number_separator" type="text" :value="old('operational_number_separator', $settings->operational_number_separator)" maxlength="5" required />
                                <p class="form-hint">مثال: / أو -</p>
                            </div>
                            <div class="form-group">
                                <x-input-label for="operational_number_format" value="صيغة الرقم التشغيلي" />
                                <x-text-input id="operational_number_format" name="operational_number_format" type="text" :value="old('operational_number_format', $settings->operational_number_format)" required />
                                <p class="form-hint">
                                    المتغيرات: <code>{department_code}</code> <code>{year}</code> <code>{document_type}</code> <code>{sequence}</code> <code>{separator}</code>
                                </p>
                            </div>
                        </div>

                        <div class="ref-settings-preview">
                            <span class="ref-settings-preview-label">معاينة:</span>
                            <code>{{ $settings->formatPreview() }}</code>
                        </div>

                        <div class="form-group">
                            <x-input-label for="operational_number_disclaimer" value="تنبيه واجهة المستخدم" />
                            <textarea id="operational_number_disclaimer" name="operational_number_disclaimer" rows="2" class="form-control">{{ old('operational_number_disclaimer', $settings->operational_number_disclaimer) }}</textarea>
                            <p class="form-hint">يُعرض للمستخدم لتوضيح أن الرقم التشغيلي ليس رقماً إشارياً رسمياً</p>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">التحكم والتدقيق</h3>
                    </div>
                    <div class="card-body ref-settings-toggles">
                        <label class="ref-settings-toggle">
                            <input type="checkbox" name="prevent_duplicate_numbers" value="1" @checked(old('prevent_duplicate_numbers', $settings->prevent_duplicate_numbers))>
                            <span class="ref-settings-toggle-body">
                                <strong>منع تكرار الرقم</strong>
                                <small>ضمن نفس القسم والسنة ونوع المستند</small>
                            </span>
                        </label>

                        <label class="ref-settings-toggle">
                            <input type="checkbox" name="audit_number_changes" value="1" @checked(old('audit_number_changes', $settings->audit_number_changes))>
                            <span class="ref-settings-toggle-body">
                                <strong>تسجيل التغييرات في Audit Trail</strong>
                                <small>أي تعديل على الرقم الإشاري أو رقم المستند</small>
                            </span>
                        </label>

                        <div class="ref-settings-note">
                            <strong>صلاحية تجاوز التكرار:</strong>
                            يمكن منح صلاحية <code>documents.reference-number.duplicate-override</code> من إدارة الأدوار للمستخدمين المخوّلين، مع إلزامهم بتوثيق سبب الاستثناء.
                        </div>
                    </div>
                </div>
            </div>

            @permission('settings.reference-numbers.edit')
            <div class="ref-settings-actions">
                <x-primary-button>حفظ الإعدادات</x-primary-button>
            </div>
            @else
            <div class="ref-settings-note ref-settings-note--readonly">
                لديك صلاحية العرض فقط — لا يمكنك تعديل هذه الإعدادات.
            </div>
            @endpermission
        </form>
    </div>
</x-app-layout>
