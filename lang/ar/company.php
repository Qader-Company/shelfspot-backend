<?php

return [
    'industry' => [
        'industry_one' => 'industry one',
        'industry_two' => 'industry two',
        'industry_three' => 'industry three',
        'industry_four' => 'industry four',
    ],

    'wallet' => [
        'coupon_redemption' => 'استخدام كوبون محفظة',
        'admin_grant' => 'شحن يدوي للمحفظة',
        'task_payment' => 'دفع مهمة',
        'task_refund' => 'استرداد مهمة',
        'adjustment' => 'تسوية محفظة',
        'insufficient_balance' => 'رصيد المحفظة غير كافٍ.',
        'manual_recharge_description' => 'شحن يدوي للمحفظة',
        'transaction' => [
            'success' => 'تمت عملية المحفظة بنجاح.',
        ],
        'coupons' => [
            'created' => 'تم إنشاء كوبون المحفظة بنجاح.',
            'updated' => 'تم تحديث كوبون المحفظة بنجاح.',
            'deleted' => 'تم حذف كوبون المحفظة بنجاح.',
            'redeemed' => 'تم استخدام كوبون المحفظة بنجاح.',
            'redemption_description' => 'استخدام كوبون محفظة: :code',
            'invalid' => 'كوبون المحفظة غير صحيح.',
            'inactive' => 'كوبون المحفظة غير مفعل.',
            'expired' => 'انتهت صلاحية كوبون المحفظة.',
            'assigned_to_another_company' => 'كوبون المحفظة مخصص لشركة أخرى.',
            'already_redeemed' => 'تم استخدام كوبون المحفظة بواسطة هذه الشركة من قبل.',
            'max_redemptions_reached' => 'تم الوصول للحد الأقصى لاستخدام كوبون المحفظة.',
        ],
        'tasks' => [
            'company_required' => 'يجب أن تكون المهمة تابعة لشركة قبل خصم المحفظة.',
            'already_charged' => 'تم خصم تكلفة المهمة من المحفظة من قبل.',
            'not_charged' => 'يجب خصم تكلفة المهمة من المحفظة قبل ردها.',
            'payment_description' => 'دفع تكلفة المهمة رقم :task من المحفظة',
            'refund_description' => 'استرداد تكلفة المهمة رقم :task إلى المحفظة',
        ],
    ],
    'tenant' => [
        'user_does_not_allowed' => 'غير مسموح لك بالتوجد هنا'
    ]
];
