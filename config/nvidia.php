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

    'model' => env('NVIDIA_NIM_MODEL', 'meta/llama-3.2-11b-vision-instruct'),

    'timeout' => (int) env('NVIDIA_NIM_TIMEOUT', 20),

    'max_retries' => (int) env('NVIDIA_NIM_MAX_RETRIES', 1),
];
