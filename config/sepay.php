<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SePay API Configuration
    |--------------------------------------------------------------------------
    |
    | Cấu hình API cho SePay Payment Gateway
    |
    */

    'merchant_id' => env('SEPAY_MERCHANT_ID'),
    
    'secret_key' => env('SEPAY_SECRET_KEY'),
    
    // Access Token để dùng làm Bearer token cho API calls
    'access_token' => env('SEPAY_ACCESS_TOKEN'),
    
    // Bank Account ID để lấy thông tin từ SePay API
    'bank_account_id' => env('SEPAY_BANK_ACCOUNT_ID'),
    
    'api_url' => env('SEPAY_API_URL', 'https://pgapi.sepay.vn'),
    
    'environment' => env('SEPAY_ENVIRONMENT', 'sandbox'), // 'sandbox' hoặc 'production'
    
    'webhook_secret' => env('SEPAY_WEBHOOK_SECRET'),
    
    'transfer_content_prefix' => env('SEPAY_TRANSFER_CONTENT_PREFIX', 'NAPTIEN'),
    
    /*
    |--------------------------------------------------------------------------
    | Checkout URLs
    |--------------------------------------------------------------------------
    */
    'checkout_url' => [
        'sandbox' => 'https://pay-sandbox.sepay.vn/v1/checkout/init',
        'production' => 'https://pay.sepay.vn/v1/checkout/init',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Success/Error URLs
    |--------------------------------------------------------------------------
    */
    'success_url' => env('SEPAY_SUCCESS_URL', '/deposit/success'),
    'error_url' => env('SEPAY_ERROR_URL', '/deposit/error'),
    'cancel_url' => env('SEPAY_CANCEL_URL', '/deposit'),
];
