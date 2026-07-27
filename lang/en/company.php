<?php

return [
    'industry' => [
        'industry_one' => 'industry one',
        'industry_two' => 'industry two',
        'industry_three' => 'industry three',
        'industry_four' => 'industry four',
    ],

    'wallet' => [
        'coupon_redemption' => 'Coupon redemption',
        'admin_grant' => 'Manual wallet recharge',
        'task_payment' => 'Task payment',
        'task_refund' => 'Task refund',
        'adjustment' => 'Wallet adjustment',
        'insufficient_balance' => 'Insufficient wallet balance.',
        'manual_recharge_description' => 'Manual wallet recharge',
        'transaction' => [
            'success' => 'Wallet transaction completed successfully.',
        ],
        'coupons' => [
            'created' => 'Wallet coupon created successfully.',
            'updated' => 'Wallet coupon updated successfully.',
            'deleted' => 'Wallet coupon deleted successfully.',
            'redeemed' => 'Wallet coupon redeemed successfully.',
            'redemption_description' => 'Wallet coupon redemption: :code',
            'invalid' => 'Wallet coupon is invalid.',
            'inactive' => 'Wallet coupon is inactive.',
            'expired' => 'Wallet coupon has expired.',
            'assigned_to_another_company' => 'Wallet coupon is assigned to another company.',
            'already_redeemed' => 'Wallet coupon has already been redeemed by this company.',
            'max_redemptions_reached' => 'Wallet coupon maximum redemptions reached.',
        ],
        'tasks' => [
            'company_required' => 'Task must belong to a company before wallet charging.',
            'already_charged' => 'Task wallet payment has already been charged.',
            'not_charged' => 'Task wallet payment must be charged before it can be refunded.',
            'payment_description' => 'Task #:task wallet payment',
            'refund_description' => 'Task #:task wallet refund',
            'price_adjustment_description' => 'Task #:task price adjustment (:amount)',
        ],
    ],
    'tenant' => [
        'user_does_not_allowed' => 'You are not allowed to be here.',
    ],
];
