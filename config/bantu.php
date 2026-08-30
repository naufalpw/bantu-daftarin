<?php

return [
    'admin' => [
        'email' => env('ADMIN_EMAIL', 'admin@example.test'),
        'name' => env('ADMIN_NAME', 'Super Admin'),
        'seed_password' => env('ADMIN_SEED_PASSWORD'),
    ],

    'service_prices' => [
        'NPWP_PERSONAL' => (float) env('NPWP_PERSONAL_PRICE', 150000),
        'NPWP_BUSINESS' => (float) env('NPWP_BUSINESS_PRICE', 500000),
    ],
];
