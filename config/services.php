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

    'brave' => [
        'token' => env('BRAVE_API_TOKEN'),
    ],

    'serper' => [
        'key' => env('SERPER_API_KEY'),
    ],

    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],

    'ai_orchestration' => [
        'key' => env('AI_ORCHESTRATION_API_KEY'),
    ],

    'dataforseo_proxy' => [
        'url'    => env('DATAFORSEO_PROXY_URL'),
        'secret' => env('DATAFORSEO_PROXY_SECRET'),
    ],

    // Team alerts fired when a new user completes registration
    // (NotificationHub::userRegistered). Discord silently skips when the
    // webhook URL is not set.
    'admin_alerts' => [
        'new_user_email'      => env('NEW_USER_ALERT_EMAIL', 'networkmenford@gmail.com'),
        'discord_webhook_url' => env('DISCORD_NEW_USER_WEBHOOK_URL'),
    ],

];
