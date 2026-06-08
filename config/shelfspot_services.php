<?php

use App\Modules\V1\Services\Domain\ValueObjects\ServiceTypeEnum;

return [
    'catalog' => [
        ServiceTypeEnum::PRIMARY_DISPLAY->value => [
            'minimum_price' => 50,
            'minimum_execution_time' => 30,
            'description' => [
                'en' => 'Ensure products are displayed on shelf according to planogram, FIFO rules and pricing tag guidelines.',
                'ar' => 'التأكد من عرض المنتجات على الرف الأساسي حسب البلانوجرام وقواعد FIFO وإرشادات بطاقة السعر.',
            ],
            'request_form' => [
                'fields' => [
                    'brand_id' => ['type' => 'integer', 'required' => true, 'source' => 'company_brands'],
                    'sub_brand_id' => ['type' => 'integer', 'required' => true, 'source' => 'company_sub_brands'],
                    'category_id' => ['type' => 'integer', 'required' => true, 'source' => 'company_categories'],
                    'sub_category_id' => ['type' => 'integer', 'required' => true, 'source' => 'company_sub_categories'],
                    'planogram_files' => ['type' => 'array<file>', 'required' => true, 'min_items' => 1, 'attachment_type' => 'planogram', 'accept' => ['application/pdf', 'image/*']],
                ],
                'requires_products' => true,
            ],
            'submission_form' => [
                'readonly_job_order_fields' => ['location', 'service_type', 'brand', 'sub_brand', 'category', 'sub_category', 'attachments'],
                'fields' => [
                    'before_picture_files' => ['type' => 'array<file>', 'required' => true, 'min_items' => 1, 'attachment_type' => 'before_picture', 'accept' => ['image/*']],
                    'after_picture_files' => ['type' => 'array<file>', 'required' => true, 'min_items' => 1, 'attachment_type' => 'after_picture', 'accept' => ['image/*']],
                    'additional_notes' => ['type' => 'string', 'required' => false, 'max' => 2000],
                ],
            ],
        ],

        ServiceTypeEnum::SECONDARY_DISPLAY_EXECUTION->value => [
            'minimum_price' => 75,
            'minimum_execution_time' => 45,
            'description' => [
                'en' => 'Execute secondary displays in the right store location according to the approved job order and planogram guidelines.',
                'ar' => 'تنفيذ العرض الثانوي في المكان الصحيح داخل المتجر حسب أمر التشغيل والبلانوجرام المعتمدين.',
            ],
            'request_form' => [
                'fields' => [
                    'brand_id' => ['type' => 'integer', 'required' => true, 'source' => 'company_brands'],
                    'sub_brand_id' => ['type' => 'integer', 'required' => true, 'source' => 'company_sub_brands'],
                    'category_id' => ['type' => 'integer', 'required' => true, 'source' => 'company_categories'],
                    'sub_category_id' => ['type' => 'integer', 'required' => true, 'source' => 'company_sub_categories'],
                    'planogram_files' => ['type' => 'array<file>', 'required' => true, 'min_items' => 1, 'attachment_type' => 'planogram', 'accept' => ['application/pdf', 'image/*']],
                    'job_order_files' => ['type' => 'array<file>', 'required' => false, 'min_items' => 1, 'attachment_type' => 'job_order', 'accept' => ['application/pdf', 'image/*', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']],
                ],
                'requires_products' => true,
            ],
            'submission_form' => [
                'readonly_job_order_fields' => ['location', 'service_type', 'brand', 'sub_brand', 'category', 'sub_category', 'attachments'],
                'fields' => [
                    'before_picture_files' => ['type' => 'array<file>', 'required' => true, 'min_items' => 1, 'attachment_type' => 'before_picture', 'accept' => ['image/*']],
                    'after_picture_files' => ['type' => 'array<file>', 'required' => true, 'min_items' => 1, 'attachment_type' => 'after_picture', 'accept' => ['image/*']],
                    'additional_notes' => ['type' => 'string', 'required' => false, 'max' => 2000],
                ],
            ],
        ],

        ServiceTypeEnum::ON_SHELF_AVAILABILITY->value => [
            'minimum_price' => 25,
            'minimum_execution_time' => 15,
            'description' => [
                'en' => 'Report each selected SKU shelf availability as available or unavailable.',
                'ar' => 'تقرير حالة توفر كل SKU مختار على الرف كمتوفر أو غير متوفر.',
            ],
            'request_form' => [
                'fields' => [
                    'brand_id' => ['type' => 'integer', 'required' => true, 'source' => 'company_brands'],
                    'sub_brand_id' => ['type' => 'integer', 'required' => true, 'source' => 'company_sub_brands'],
                    'category_id' => ['type' => 'integer', 'required' => true, 'source' => 'company_categories'],
                    'sub_category_id' => ['type' => 'integer', 'required' => true, 'source' => 'company_sub_categories'],
                    'planogram_files' => ['type' => 'array<file>', 'required' => true, 'min_items' => 1, 'attachment_type' => 'planogram', 'accept' => ['application/pdf', 'image/*']],
                ],
                'requires_products' => true,
            ],
            'submission_form' => [
                'readonly_job_order_fields' => ['location', 'service_type', 'brand', 'sub_brand', 'category', 'sub_category', 'attachments', 'products'],
                'fields' => [
                    'items' => [
                        'type' => 'array',
                        'required' => true,
                        'min_items' => 1,
                        'item_fields' => [
                            'product_id' => ['type' => 'integer', 'required' => true],
                            'sku' => ['type' => 'string', 'required' => true],
                            'availability' => ['type' => 'enum', 'required' => true, 'values' => ['available', 'unavailable']],
                        ],
                    ],
                    'additional_notes' => ['type' => 'string', 'required' => false, 'max' => 2000],
                ],
            ],
        ],

        ServiceTypeEnum::INSTORE_VISIBILITY->value => [
            'minimum_price' => 30,
            'minimum_execution_time' => 20,
            'description' => [
                'en' => 'Capture visibility photos for selected products at primary and/or secondary displays.',
                'ar' => 'تصوير ظهور المنتجات المختارة في العرض الأساسي و/أو العرض الثانوي داخل المتجر.',
            ],
            'request_form' => [
                'fields' => [
                    'brand_id' => ['type' => 'integer', 'required' => true, 'source' => 'company_brands'],
                    'sub_brand_id' => ['type' => 'integer', 'required' => true, 'source' => 'company_sub_brands'],
                    'category_id' => ['type' => 'integer', 'required' => true, 'source' => 'company_categories'],
                    'sub_category_id' => ['type' => 'integer', 'required' => true, 'source' => 'company_sub_categories'],
                    'planogram_files' => ['type' => 'array<file>', 'required' => true, 'min_items' => 1, 'attachment_type' => 'planogram', 'accept' => ['application/pdf', 'image/*']],
                ],
                'requires_products' => true,
            ],
            'submission_form' => [
                'readonly_job_order_fields' => ['location', 'service_type', 'brand', 'sub_brand', 'category', 'sub_category', 'attachments'],
                'fields' => [
                    'picture_files' => ['type' => 'array<file>', 'required' => true, 'min_items' => 1, 'attachment_type' => 'visibility_picture', 'accept' => ['image/*']],
                    'additional_notes' => ['type' => 'string', 'required' => false, 'max' => 2000],
                ],
            ],
        ],

        ServiceTypeEnum::FRESHNESS_REPORT->value => [
            'minimum_price' => 40,
            'minimum_execution_time' => 25,
            'description' => [
                'en' => 'Report selected SKU quantities and expiry dates from backdoor/store inventory.',
                'ar' => 'تقرير كميات وتواريخ صلاحية الـ SKUs المختارة من المخزن أو منطقة الـ Backdoor.',
            ],
            'request_form' => [
                'fields' => [
                    'brand_id' => ['type' => 'integer', 'required' => true, 'source' => 'company_brands'],
                    'sub_brand_id' => ['type' => 'integer', 'required' => true, 'source' => 'company_sub_brands'],
                    'category_id' => ['type' => 'integer', 'required' => true, 'source' => 'company_categories'],
                    'sub_category_id' => ['type' => 'integer', 'required' => true, 'source' => 'company_sub_categories'],
                    'planogram_files' => ['type' => 'array<file>', 'required' => true, 'min_items' => 1, 'attachment_type' => 'planogram', 'accept' => ['application/pdf', 'image/*']],
                    'expected_quantity' => ['type' => 'integer', 'required' => false, 'min' => 1],
                    'expected_expiry_date' => ['type' => 'date', 'required' => false],
                ],
                'requires_products' => true,
            ],
            'submission_form' => [
                'readonly_job_order_fields' => ['location', 'service_type', 'brand', 'sub_brand', 'category', 'sub_category', 'attachments', 'products'],
                'fields' => [
                    'items' => [
                        'type' => 'array',
                        'required' => true,
                        'min_items' => 1,
                        'item_fields' => [
                            'product_id' => ['type' => 'integer', 'required' => true],
                            'sku' => ['type' => 'string', 'required' => true],
                            'quantity' => ['type' => 'integer', 'required' => true, 'min' => 0],
                            'expiry_date' => ['type' => 'date', 'required' => true],
                        ],
                    ],
                    'additional_notes' => ['type' => 'string', 'required' => false, 'max' => 2000],
                ],
            ],
        ],
    ],
];
