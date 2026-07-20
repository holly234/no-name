<?php

return [
    'enabled' => (bool) env('AI_ENABLED', false),
    'provider' => env('AI_PROVIDER', 'gemini'),
    'reservation_credits' => (int) env('AI_RESERVATION_CREDITS', 25),
    'tokens_per_credit' => (int) env('AI_TOKENS_PER_CREDIT', 100),

    'providers' => [
        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-2.5-flash-lite'),
            'timeout' => (int) env('GEMINI_TIMEOUT', 30),
            'billing_mode' => env('GEMINI_BILLING_MODE', 'free'),
            'input_cost_per_million_usd' => (float) env('GEMINI_INPUT_COST_PER_MILLION_USD', 0.10),
            'output_cost_per_million_usd' => (float) env('GEMINI_OUTPUT_COST_PER_MILLION_USD', 0.40),
        ],
    ],
];
