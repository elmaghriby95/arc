<?php

return [
    'transaction' => [
        'rejected' => 'تم رفض المعاملة «:title» وإعادتها من :from إلى :to بواسطة :by',
        'created' => 'تم إنشاء معاملة جديدة «:title» بحالة :status',
        'status_updated' => 'تم تحديث حالة المعاملة «:title» من :from إلى :to بواسطة :by',
    ],
    'lending' => [
        'requested' => 'طلب إعارة مستندات المعاملة :reference «:title» بواسطة :by',
        'review_approved' => 'تم اعتماد طلب إعارة المعاملة :reference «:title» بواسطة :by',
        'review_rejected' => 'تم رفض طلب إعارة المعاملة :reference «:title» بواسطة :by',
        'handover_confirmed' => 'تم تأكيد تسليم مستندات المعاملة :reference «:title» بواسطة :by',
        'handover_rejected' => 'تم رفض تسليم مستندات المعاملة :reference «:title» بواسطة :by',
        'returned' => 'تم إرجاع مستندات المعاملة :reference «:title» بواسطة :by',
    ],
];
