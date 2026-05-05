<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'aba' => [
        'api_key' => env('ABA_API_KEY'),
        'deeplink_base' => env('ABA_DEEPLINK_BASE', 'https://pay.ababank.com'),
        'merchant_name' => env('ABA_MERCHANT_NAME', 'Salon Payment'),
        'merchant_id' => env('ABA_MERCHANT_ID'),
        'account' => env('ABA_ACCOUNT'),
        'currency' => env('ABA_CURRENCY', 'USD'),
        'qr_image_url' => env('ABA_QR_IMAGE_URL'),
        'topup_template_image_url' => env('ABA_TOPUP_TEMPLATE_IMAGE_URL'),
    ],

    'payway' => [
        'base_url' => env('PAYWAY_BASE_URL', 'https://checkout-sandbox.payway.com.kh'),
        'merchant_id' => env('PAYWAY_MERCHANT_ID'),
        'api_key' => env('PAYWAY_API_KEY'),
        'currency' => env('PAYWAY_CURRENCY', 'USD'),
        'return_url' => env('PAYWAY_RETURN_URL'),
        'status_url' => env('PAYWAY_STATUS_URL'),
        'cancel_url' => env('PAYWAY_CANCEL_URL'),
        'payment_option' => env('PAYWAY_PAYMENT_OPTION'),
        'checkout_script_url' => env('PAYWAY_CHECKOUT_SCRIPT_URL'),
        'hash_fields' => env('PAYWAY_HASH_FIELDS'),
    ],

];
