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
    'integer' => 'حقل :attribute يجب أن يكون رقماً صحيحاً.',
    'array' => 'حقل :attribute يجب أن يكون قائمة.',

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
        'password_required' => 'كلمة المرور مطلوبة.',
        'password_confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        'role_required' => 'يجب اختيار دور للمستخدم.',
        'department_exists' => 'الوحدة التنظيمية المحددة غير موجودة.',
        'cannot_assign_admin' => 'لا يمكنك تعيين دور مدير النظام.',
        'cannot_demote_self' => 'لا يمكنك تغيير دورك إلى دور أقل من مدير النظام.',
    ],

    'attributes' => [
        'title' => 'عنوان المعاملة',
        'description' => 'الوصف',
        'department_id' => 'الوحدة التنظيمية',
        'folder_id' => 'المجلد',
        'transaction_type_id' => 'نوع المعاملة',
        'transaction_date' => 'تاريخ المعاملة',
        'notes' => 'الملاحظات',
        'files' => 'المستندات',
        'files.*' => 'المستند',
    ],
];
