<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | NASS E-Payment Gateway
    |--------------------------------------------------------------------------
    |
    | Portal integration (hosted checkout). Currency code 368 = Iraqi Dinar.
    | transactionType "1" = sale. Status checks work within 24 hours of init.
    |
    | UAT:  https://uat-gateway.nass.iq:9746
    | Prod: https://gateway.nass.iq:9746
    |
    */
    'gateway' => [
        'default' => env('NASS_GATEWAY_MERCHANT', 'default'),

        'token_cache_ttl_seconds' => (int) env('NASS_GATEWAY_TOKEN_TTL', 3300),

        'merchants' => [
            'default' => [
                'environment' => env('NASS_GATEWAY_ENVIRONMENT', 'uat'),
                'username' => env('NASS_GATEWAY_USERNAME'),
                'password' => env('NASS_GATEWAY_PASSWORD'),
                'base_url' => env('NASS_GATEWAY_BASE_URL', 'https://uat-gateway.nass.iq:9746'),
                'back_ref' => env('NASS_GATEWAY_BACK_REF'),
                'notify_url' => env('NASS_GATEWAY_NOTIFY_URL'),
            ],
        ],

        'http' => [
            'timeout' => (int) env('NASS_GATEWAY_TIMEOUT', 30),
            'retry_times' => (int) env('NASS_GATEWAY_RETRY_TIMES', 1),
            'retry_sleep_ms' => (int) env('NASS_GATEWAY_RETRY_SLEEP_MS', 200),
        ],

        'sync' => [
            'status_max_age_hours' => 24,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Nass Wallet
    |--------------------------------------------------------------------------
    |
    | UAT base:  https://uatgw1.nasswallet.com/payment/transaction
    | Prod base: https://gw-api.nasswallet.com/phase3/payment/transaction
    |
    */
    'wallet' => [
        'default' => env('NASS_WALLET_MERCHANT', 'default'),

        'token_cache_ttl_seconds' => (int) env('NASS_WALLET_TOKEN_TTL', 3300),

        'merchants' => [
            'default' => [
                'environment' => env('NASS_WALLET_ENVIRONMENT', 'uat'),
                'basic_token' => env('NASS_WALLET_BASIC_TOKEN'),
                'username' => env('NASS_WALLET_USERNAME'),
                'password' => env('NASS_WALLET_PASSWORD'),
                'transaction_pin' => env('NASS_WALLET_TRANSACTION_PIN'),
                'base_url' => env('NASS_WALLET_BASE_URL', 'https://uatgw1.nasswallet.com/payment/transaction'),
                'portal_url' => env('NASS_WALLET_PORTAL_URL', 'https://uatcheckout1.nasswallet.com'),
                'callback_url' => env('NASS_WALLET_CALLBACK_URL'),
                'language_code' => env('NASS_WALLET_LANGUAGE', 'en'),
            ],
        ],

        'http' => [
            'timeout' => (int) env('NASS_WALLET_TIMEOUT', 30),
            'retry_times' => (int) env('NASS_WALLET_RETRY_TIMES', 1),
            'retry_sleep_ms' => (int) env('NASS_WALLET_RETRY_SLEEP_MS', 200),
        ],
    ],

];
