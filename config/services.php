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
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],

    'ai_orchestration' => [
        'key' => env('AI_ORCHESTRATION_API_KEY'),
    ],

    'dataforseo' => [
        'login' => env('DATAFORSEO_LOGIN'),
        'password' => env('DATAFORSEO_PASSWORD'),
    ],

    'dataforseo_proxy' => [
        'url' => env('DATAFORSEO_PROXY_URL'),
        'secret' => env('DATAFORSEO_PROXY_SECRET'),
    ],

    // Team alerts fired when a new user completes registration
    // (NotificationHub::userRegistered). Discord silently skips when the
    // webhook URL is not set.
    'admin_alerts' => [
        'new_user_email' => env('NEW_USER_ALERT_EMAIL', 'networkmenford@gmail.com'),
        'discord_webhook_url' => env('DISCORD_NEW_USER_WEBHOOK_URL'),
    ],

    // Read-only Monday.com access, used by monday:import-network-sales to pull
    // board 582070825. Personal API token from Monday → Admin → API.
    'monday' => [
        'token' => env('MONDAY_API_TOKEN'),
        'api_url' => env('MONDAY_API_URL', 'https://api.monday.com/v2'),
        'board_id' => env('MONDAY_NETWORK_SALES_BOARD_ID', '582070825'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Stripe
    |--------------------------------------------------------------------------
    | Credentials only. WHICH gateway is active is config/tokens.php's
    | 'driver' — these keys being present does not switch anything on.
    |
    | Test keys (sk_test_/pk_test_) belong to a Sandbox and cannot move money;
    | live keys can. Nothing here has a default: a missing secret must fail
    | loudly at boot rather than silently talking to the wrong account.
    |
    | webhook_secret is NOT interchangeable between environments. The Stripe
    | CLI issues its own secret for forwarded events (`stripe listen
    | --print-secret`); a Dashboard endpoint issues a different one. Using the
    | wrong one fails signature verification on every delivery.
    */
    'stripe' => [
        // Publishable key. Unused by hosted Checkout (there is no client-side
        // Stripe JS in this integration) — kept so the pair is complete.
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

];
