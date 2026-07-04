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
