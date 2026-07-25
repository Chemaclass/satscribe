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

    // The '' defaults below are load-bearing: these values are injected into
    // non-nullable string constructor params, so a missing env var would
    // otherwise fail container resolution with a TypeError naming an argument
    // position rather than the setting. Consumers treat '' as "not configured".
    'openai' => [
        'key' => env('OPENAI_API_KEY', ''),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('OPENAI_MODEL', 'gpt-4'),
        'model_followup' => env('OPENAI_MODEL_FOLLOWUP', 'gpt-4o-mini'),
        'max_attempts' => env('OPENAI_MAX_ATTEMPTS', 200),
    ],

    // Optional OpenAI-compatible providers. With a key set here the server can
    // serve their models without the visitor supplying one; left empty, those
    // models are only reachable with a bring-your-own key. Base URLs are not
    // configurable on purpose — they are pinned in the provider registry.
    'openrouter' => [
        'key' => env('OPENROUTER_API_KEY', ''),
    ],

    'groq' => [
        'key' => env('GROQ_API_KEY', ''),
    ],

    'alby' => [
        'api_key' => env('ALBY_API_KEY', ''),
        'webhook_secret' => env('ALBY_WEBHOOK_SECRET', ''),
    ],

    'rate_limit' => [
        'guest' => [
            'max_attempts' => (int) env('GUEST_RATE_LIMIT_MAX_ATTEMPTS', 5),
            'invoice_amount' => (int) env('GUEST_RATE_LIMIT_INVOICE_AMOUNT', 150),
        ],
        'nostr' => [
            'max_attempts' => (int) env('NOSTR_RATE_LIMIT_MAX_ATTEMPTS', 15),
            'invoice_amount' => (int) env('NOSTR_RATE_LIMIT_INVOICE_AMOUNT', 210),
        ],
        'invoice_expiry' => (int) env('RATE_LIMIT_INVOICE_EXPIRY_SECONDS', 300),
    ],
];
