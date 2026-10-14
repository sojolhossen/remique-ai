<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Business Cloud API Configuration
    |--------------------------------------------------------------------------
    |
    | Official Meta WhatsApp Cloud API credentials and settings.
    | Secrets are kept strictly in environment variables and never exposed to
    | browser code or Git.
    |
    */

    'base_url' => env('WHATSAPP_CLOUD_API_BASE_URL', 'https://graph.facebook.com'),
    'api_version' => env('WHATSAPP_CLOUD_API_VERSION', 'v21.0'),
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID', ''),
    'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID', ''),
    'access_token' => env('WHATSAPP_ACCESS_TOKEN', ''),
    'verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN', ''),
    'app_secret' => env('WHATSAPP_APP_SECRET', ''),

    'timeout' => (int) env('WHATSAPP_API_TIMEOUT', 15),
    'retry_limit' => (int) env('WHATSAPP_RETRY_LIMIT', 3),

    /*
    | Behavior flags
    */
    'send_onboarding_replies' => (bool) env('WHATSAPP_SEND_ONBOARDING_REPLIES', true),
    'default_reminder_template' => env('WHATSAPP_DEFAULT_REMINDER_TEMPLATE', 'remi_reminder_alert'),
    'default_template_language' => env('WHATSAPP_DEFAULT_TEMPLATE_LANGUAGE', 'en_US'),

    /*
    | Storage and rate limiting
    */
    'max_file_size_mb' => (int) env('WHATSAPP_MAX_FILE_SIZE_MB', 15),
];
