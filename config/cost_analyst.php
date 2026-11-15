<?php

return [
    'default_margin_percent' => (float) env('COST_ANALYST_DEFAULT_MARGIN', 35),
    'min_margin_percent' => 5,
    'max_margin_percent' => 200,
    'gemini_model' => env('COST_ANALYST_GEMINI_MODEL', 'gemini-2.0-flash'),
    'sales_history_months' => 12,
];
