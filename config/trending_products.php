<?php

return [
    'compare_previous_period' => true,
    'hot_growth_percent' => 25,
    'rising_growth_percent' => 10,
    'max_price_increase_percent' => 15,
    'default_price_increase_percent' => 5,
    'sales_history_months' => 12,
    'gemini_model' => env('TRENDING_PRODUCTS_GEMINI_MODEL', env('COST_ANALYST_GEMINI_MODEL', 'gemini-2.0-flash')),
    'agent_brand' => 'E media AI agent recommendation',
];
