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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4'),
        'max_attempts' => env('OPENAI_MAX_ATTEMPTS', 200),
    ],

    'alby' => [
        'api_key' => env('ALBY_API_KEY'),
        'webhook_secret' => env('ALBY_WEBHOOK_SECRET'),
    ],

    'rate_limit' => [
        'max_attempts' => env('RATE_LIMIT_MAX_ATTEMPTS', 20),
        'invoice_amount' => env('RATE_LIMIT_INVOICE_AMOUNT', 1000),
        'invoice_expiry' => env('RATE_LIMIT_EXPIRY_SECONDS', 300)
    ],
];
