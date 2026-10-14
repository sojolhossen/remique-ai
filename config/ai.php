<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    |
    | Supported: "nvidia_nim"
    |
    */
    'default_provider' => env('AI_DEFAULT_PROVIDER', 'nvidia_nim'),

    /*
    |--------------------------------------------------------------------------
    | NVIDIA NIM Configuration
    |--------------------------------------------------------------------------
    */
    'nvidia' => [
        'api_key' => env('NVIDIA_NIM_API_KEY'),
        'base_url' => env('NVIDIA_NIM_BASE_URL', 'https://integrate.api.nvidia.com/v1'),
        'model' => env('NVIDIA_NIM_MODEL', 'meta/llama-3.2-11b-vision-instruct'),
        'timeout' => (int) env('NVIDIA_NIM_TIMEOUT', 20),
        'max_retries' => (int) env('NVIDIA_NIM_MAX_RETRIES', 1),
        'available_models' => [
            'meta/llama-3.2-11b-vision-instruct' => 'Llama 3.2 11B Vision Instruct (Ultra-Fast 1.6s)',
            'openai/gpt-oss-20b' => 'GPT-OSS-20B (NVIDIA NIM)',
        ],
    ],
];
