<?php

return [
    'company' => [
        'name' => env('DERMAS_COMPANY_NAME', 'DERMAS APPAREL (PVT) LTD'),
        'address' => env('DERMAS_COMPANY_ADDRESS', 'Vishaka Mawatha, Bandarawela'),
        'telephone' => env('DERMAS_COMPANY_PHONE', '077 103 9230 | 057 205 2557'),
        'email' => env('DERMAS_COMPANY_EMAIL', 'info@dermasapparel.com'),
        /** 9-digit TIN from VAT registration certificate */
        'tin' => env('DERMAS_COMPANY_TIN', '241784266'),
    ],

    /** Branch / entity code for tax invoice serial (QQQQ), 1–15 alphanumeric */
    'invoice_branch_code' => env('DERMAS_INVOICE_BRANCH_CODE', 'DERM'),

    /**
     * SSCL (Social Security Contribution Levy) percent — applied on goods value before VAT.
     */
    'sscl_rate' => (float) env('DERMAS_SSCL_RATE', 2.5),

    /**
     * VAT rate percent for Tax Invoice breakdown.
     * Order total = goods exclusive of SSCL/VAT. Then: SSCL on goods, VAT on (goods + SSCL).
     */
    'vat_rate' => (float) env('DERMAS_VAT_RATE', 18),
];

