<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Telegram Bot API Configuration
    |--------------------------------------------------------------------------
    */

    'bot_token' => env('TELEGRAM_BOT_TOKEN', ''),
    'bot_username' => env('TELEGRAM_BOT_USERNAME', 'RemiqueAiBot'),
    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET', ''),
    'api_base_url' => env('TELEGRAM_API_BASE_URL', 'https://api.telegram.org'),
    'timeout' => (int) env('TELEGRAM_TIMEOUT', 15),
];
