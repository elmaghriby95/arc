<?php

return [
    'required' => 'حقل :attribute مطلوب.',
    'string' => 'حقل :attribute يجب أن يكون نصاً.',
    'max' => [
        'string' => 'حقل :attribute يجب ألا يتجاوز :max حرفاً.',
        'file' => 'حجم الملف يجب ألا يتجاوز :max كيلوبايت.',
    ],
    'date' => 'حقل :attribute يجب أن يكون تاريخاً صحيحاً.',
    'exists' => 'القيمة المحددة في :attribute غير صالحة.',
    'unique' => 'قيمة :attribute مستخدمة مسبقاً في معاملة أخرى. غيّر الرقم أو افتح المعاملة الموجودة لإكمال رفع المستندات.',
    'integer' => 'حقل :attribute يجب أن يكون رقماً صحيحاً.',
    'array' => 'حقل :attribute يجب أن يكون قائمة.',
    'file' => 'يجب أن يكون :attribute ملفاً صالحاً.',
    'uploaded' => 'فشل رفع :attribute. تحقق من حجم الملف وإعدادات الخادم.',
    'post_too_large' => 'حجم الطلب أكبر من الحد المسموح في الخادم. اطلب من مسؤول السيرفر رفع client_max_body_size في Nginx و post_max_size في PHP.',
    'post_too_large_detail' => 'حجم الطلب (:content) أكبر من حد الخادم. post_max_size=:post_max ، upload_max_filesize=:upload_max.',

    'role' => [
        'name_required' => 'اسم الدور مطلوب.',
        'permissions_required' => 'يجب تحديد صلاحية واحدة على الأقل.',
        'permissions_min' => 'يجب تحديد صلاحية واحدة على الأقل.',
    ],

    'user' => [
        'name_required' => 'اسم المستخدم مطلوب.',
        'email_required' => 'البريد الإلكتروني مطلوب.',
        'email_format' => 'صيغة البريد الإلكتروني غير صحيحة.',
        'email_unique' => 'هذا البريد الإلكتروني مستخدم بالفعل.',
        'employee_number_required' => 'الرقم الوظيفي مطلوب.',
        'employee_number_unique' => 'هذا الرقم الوظيفي مستخدم بالفعل.',
        'password_required' => 'كلمة المرور مطلوبة.',
        'password_confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        'role_required' => 'يجب اختيار دور للمستخدم.',
        'department_exists' => 'الوحدة التنظيمية المحددة غير موجودة.',
        'cannot_assign_admin' => 'لا يمكنك تعيين دور مدير النظام.',
        'cannot_demote_self' => 'لا يمكنك تغيير دورك إلى دور أقل من مدير النظام.',
        'cannot_change_super_admin_role' => 'لا يمكن تغيير دور مدير النظام.',
    ],

    'database_clean' => [
        'confirmation_required' => 'يجب كتابة عبارة التأكيد.',
        'confirmation_mismatch' => 'عبارة التأكيد غير مطابقة.',
        'password_required' => 'كلمة المرور مطلوبة لتأكيد العملية.',
        'password_incorrect' => 'كلمة المرور غير صحيحة.',
    ],

    'attributes' => [
        'title' => 'عنوان المعاملة',
        'archival_reference' => 'الرقم الإشاري للمعاملة',
        'description' => 'الوصف',
        'department_id' => 'الوحدة التنظيمية',
        'folder_id' => 'المجلد',
        'transaction_type_id' => 'نوع المعاملة',
        'transaction_date' => 'تاريخ المعاملة',
        'notes' => 'الملاحظات',
        'files' => 'المستندات',
        'files.*' => 'المستند',
        'employee_number' => 'الرقم الوظيفي',
    ],
];
