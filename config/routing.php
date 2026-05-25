<?php


return [
    'public' => [
        ['prefix' => '', 'file' => 'api.php']
    ],
    'portals' => [
        'dashboard' => [
//            ['prefix' => '', 'file' => '', 'middlewares' => []],
        ],

        'worker' => [
//            ['prefix' => '', 'file' => '', 'middlewares' => []],
        ],

        'company' => [
            ['prefix' => 'brands', 'file' => 'brands.php', 'middlewares' => ['auth:sanctum', 'tenant']],
            ['prefix' => 'sub-brands', 'file' => 'sub-brands.php', 'middlewares' => ['auth:sanctum', 'tenant']],
            ['prefix' => 'categories', 'file' => 'categories.php', 'middlewares' => ['auth:sanctum', 'tenant']],
            ['prefix' => 'sub-categories', 'file' => 'sub-categories.php', 'middlewares' => ['auth:sanctum', 'tenant']],
            ['prefix' => 'products', 'file' => 'products.php', 'middlewares' => ['auth:sanctum', 'tenant']],
        ]
    ]
];
