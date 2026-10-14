<?php

return [
    /*
    |--------------------------------------------------------------------------
    | NVIDIA NIM Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration values for connecting to NVIDIA NIM OpenAI-compatible API.
    |
    */

    'api_key' => env('NVIDIA_NIM_API_KEY'),

    'base_url' => env('NVIDIA_NIM_BASE_URL', 'https://integrate.api.nvidia.com/v1'),

    'model' => env('NVIDIA_NIM_MODEL', 'openai/gpt-oss-20b'),

    'timeout' => (int) env('NVIDIA_NIM_TIMEOUT', 30),

    'max_retries' => (int) env('NVIDIA_NIM_MAX_RETRIES', 2),
];
