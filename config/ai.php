<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    |
    | Supported: "gemini", "nvidia_nim"
    |
    */
    'default_provider' => env('AI_DEFAULT_PROVIDER', 'gemini'),

    /*
    |--------------------------------------------------------------------------
    | Google Gemini Configuration
    |--------------------------------------------------------------------------
    */
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY', 'AIzaSyD2NN8Jo5m-9FYTNcmsIvzPODVPYlpD2jY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'timeout' => (int) env('GEMINI_TIMEOUT', 30),
        'max_retries' => (int) env('GEMINI_MAX_RETRIES', 2),
        'available_models' => [
            'gemini-2.5-flash' => 'Gemini 2.5 Flash (Ultra Fast & Recommended)',
            'gemini-2.5-flash-lite' => 'Gemini 2.5 Flash-Lite (Lightweight)',
            'gemini-flash-latest' => 'Gemini Flash Latest',
            'gemini-3-flash-preview' => 'Gemini 3 Flash Preview',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | NVIDIA NIM Configuration
    |--------------------------------------------------------------------------
    */
    'nvidia' => [
        'api_key' => env('NVIDIA_NIM_API_KEY'),
        'base_url' => env('NVIDIA_NIM_BASE_URL', 'https://integrate.api.nvidia.com/v1'),
        'model' => env('NVIDIA_NIM_MODEL', 'openai/gpt-oss-20b'),
        'timeout' => (int) env('NVIDIA_NIM_TIMEOUT', 30),
        'max_retries' => (int) env('NVIDIA_NIM_MAX_RETRIES', 2),
        'available_models' => [
            'openai/gpt-oss-20b' => 'GPT-OSS-20B (NVIDIA NIM)',
            'meta/llama-3.1-70b-instruct' => 'Llama 3.1 70B Instruct',
            'meta/llama-3.1-8b-instruct' => 'Llama 3.1 8B Instruct',
            'mistralai/mixtral-8x7b-instruct-v0.1' => 'Mixtral 8x7B Instruct',
        ],
    ],
];
