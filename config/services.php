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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'gmail' => [
        'client_id' => env('GMAIL_CLIENT_ID'),
        'client_secret' => env('GMAIL_CLIENT_SECRET'),
        'redirect_uri' => env('GMAIL_REDIRECT_URI'),
        'pubsub_topic' => env('GMAIL_PUBSUB_TOPIC'),
        'pubsub_verification_token' => env('GMAIL_PUBSUB_VERIFICATION_TOKEN'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4.1-mini'),
        'timeout' => env('OPENAI_TIMEOUT', 30),
    ],

    'meta' => [
        'app_id' => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),
        'verify_token' => env('META_VERIFY_TOKEN'),
        'graph_version' => env('META_GRAPH_VERSION', 'v25.0'),
        'redirect_uri' => env('META_REDIRECT_URI'),
        'webhook_verify_token' => env('META_WEBHOOK_VERIFY_TOKEN'),
        'embedded_signup_config_id' => env('META_EMBEDDED_SIGNUP_CONFIG_ID'),
    ],

    'telegram' => [
        'api_base' => env('TELEGRAM_API_BASE', 'https://api.telegram.org/bot'),
    ],

    'n8n' => [
        'base_url' => env('N8N_BASE_URL'),
        'webhook_secret' => env('N8N_WEBHOOK_SECRET') ?: env('APP_WEBHOOK_SECRET'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'webhooks' => [
        'secret' => env('APP_WEBHOOK_SECRET') ?: env('META_WEBHOOK_SECRET'),
    ],

];
