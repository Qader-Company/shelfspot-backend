<?php

return [
    'task_status' => [
        'draft' => 'مسودة',
        'pending' => 'قيد الانتظار',
        'started' => 'بدأت',
        'in_progress' => 'قيد التنفيذ',
        'worker_cancelled' => 'ألغيت بواسطة العامل',
        'company_cancelled' => 'ألغيت بواسطة الشركة',
        'completed' => 'مكتملة',
        'rejected' => 'مرفوضة',
        'accepted' => 'مقبولة',
        'reopened' => 'أعيد فتحها',
        'failed' => 'فشلت',
    ],
    'task_payment_status' => [
        'pending' => 'قيد الانتظار',
        'charged' => 'تم الخصم',
        'refunded' => 'تم الاسترداد',
        'failed' => 'فشل',
    ],
    'task_service_status' => [
        'pending' => 'قيد الانتظار',
        'in_progress' => 'قيد التنفيذ',
        'completed' => 'مكتملة',
    ],
    'catalog_purge_status' => [
        'queued' => 'في قائمة الانتظار',
        'failed' => 'فشل',
    ],
    'withdrawal_method' => [
        'bank_account' => 'حساب بنكي',
        'wallet' => 'محفظة إلكترونية',
    ],
    'withdrawal_status' => [
        'pending' => 'قيد الانتظار',
        'paid' => 'تم الدفع',
        'rejected' => 'مرفوض',
    ],
    'worker_wallet_transaction_type' => [
        'task_earning' => 'أرباح مهمة',
        'withdrawal' => 'سحب',
        'withdrawal_refund' => 'إعادة مبلغ سحب',
        'adjustment' => 'تسوية',
    ],
];
