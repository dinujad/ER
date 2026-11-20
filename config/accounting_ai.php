<?php

/**
 * Phase 5 — AI accounting configuration (secrets via env only).
 */
return [
    'enabled' => (bool) env('ACCOUNTING_AI_ENABLED', false),

    'default_provider' => env('ACCOUNTING_AI_PROVIDER', 'fake'),

    'providers' => [
        'fake' => [
            'driver' => 'fake',
            'model' => 'fake-deterministic-v1',
        ],
        'openai' => [
            'driver' => 'openai_compatible',
            'api_key' => env('ACCOUNTING_AI_OPENAI_API_KEY'),
            'endpoint' => env('ACCOUNTING_AI_OPENAI_ENDPOINT', 'https://api.openai.com/v1'),
            'model' => env('ACCOUNTING_AI_OPENAI_MODEL', 'gpt-4o-mini'),
        ],
        'gemini' => [
            'driver' => 'gemini',
            'api_key' => env('ACCOUNTING_AI_GEMINI_API_KEY'),
            'endpoint' => env('ACCOUNTING_AI_GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta'),
            'model' => env('ACCOUNTING_AI_GEMINI_MODEL', 'gemini-2.0-flash'),
        ],
        'compatible' => [
            'driver' => 'openai_compatible',
            'api_key' => env('ACCOUNTING_AI_COMPATIBLE_API_KEY'),
            'endpoint' => env('ACCOUNTING_AI_COMPATIBLE_ENDPOINT'),
            'model' => env('ACCOUNTING_AI_COMPATIBLE_MODEL', 'local-model'),
        ],
        'self_hosted' => [
            'driver' => 'openai_compatible',
            'api_key' => env('ACCOUNTING_AI_SELF_HOSTED_API_KEY'),
            'endpoint' => env('ACCOUNTING_AI_SELF_HOSTED_ENDPOINT'),
            'model' => env('ACCOUNTING_AI_SELF_HOSTED_MODEL', 'local-model'),
        ],
    ],

    'timeout_seconds' => (int) env('ACCOUNTING_AI_TIMEOUT', 30),
    'max_tokens' => (int) env('ACCOUNTING_AI_MAX_TOKENS', 2048),
    'monthly_request_limit' => (int) env('ACCOUNTING_AI_MONTHLY_LIMIT', 500),
    'retention_days' => (int) env('ACCOUNTING_AI_RETENTION_DAYS', 90),
    'max_tool_result_rows' => (int) env('ACCOUNTING_AI_MAX_TOOL_ROWS', 50),
    'max_message_chars' => (int) env('ACCOUNTING_AI_MAX_MESSAGE_CHARS', 4000),

    'ocr' => [
        'enabled' => (bool) env('ACCOUNTING_OCR_ENABLED', true),
        // local = read the text with pdftotext / built-in PDF reader / tesseract, then parse the fields.
        'driver' => env('ACCOUNTING_OCR_DRIVER', 'local'),
        'pdftotext_path' => env('ACCOUNTING_OCR_PDFTOTEXT', 'pdftotext'),
        'tesseract_path' => env('ACCOUNTING_OCR_TESSERACT', 'tesseract'),
        'tesseract_lang' => env('ACCOUNTING_OCR_TESSERACT_LANG', 'eng'),
        'max_file_kb' => (int) env('ACCOUNTING_OCR_MAX_FILE_KB', 10240),
        'allowed_mimes' => ['image/jpeg', 'image/png', 'application/pdf'],
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'pdf'],
        'disk' => env('ACCOUNTING_OCR_DISK', 'local'),
        'path_prefix' => 'accounting/ocr',
    ],

    'features' => [
        'assistant' => true,
        'ocr' => true,
        'categorization' => true,
        'anomalies' => true,
        'insights' => true,
        'bank_assist' => true,
        'automation' => true,
        'review_center' => true,
    ],

    'prompt_injection_patterns' => [
        '/ignore (all |previous |above )?instructions/i',
        '/disregard (system|safety|security)/i',
        '/reveal (api|secret|password|credential)/i',
        '/execute (sql|shell|code)/i',
        '/bypass (permission|auth|approval)/i',
    ],
];
