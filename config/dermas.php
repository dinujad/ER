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

    /** Days from invoice date to due date when the customer has no own payment terms (Phase 6.4). */
    'default_payment_terms_days' => (int) env('DERMAS_DEFAULT_PAYMENT_TERMS_DAYS', 30),

    /** Days from bill date to due date when the supplier has no own terms (Phase 6.5). */
    'default_supplier_terms_days' => (int) env('DERMAS_DEFAULT_SUPPLIER_TERMS_DAYS', 30),

    /** Phase 6.5 — when true, confirmed bills show "Awaiting approval" until approved for payment. */
    'require_bill_approval' => (bool) env('DERMAS_REQUIRE_BILL_APPROVAL', false),

    /**
     * Phase 6.6 — fine-grained accounting permissions. false: users with "Manage Accounting" can also void,
     * credit, approve, recode etc. (same as before). true: each action needs its own permission in Roles.
     */
    'strict_accounting_permissions' => (bool) env('DERMAS_STRICT_ACCOUNTING_PERMISSIONS', false),

    /** Phase 7 — the person who prepares a supplier payment run cannot approve it. */
    'payment_run_four_eyes' => (bool) env('DERMAS_PAYMENT_RUN_FOUR_EYES', false),

    /** Phase 7 — automatic SMS reminders: days after the due date to remind (comma list), and minimum gap. */
    'reminder_schedule_days' => array_values(array_filter(array_map('intval', explode(',', (string) env('DERMAS_REMINDER_DAYS', '1,7,14,30'))))),
    'reminder_min_gap_days' => (int) env('DERMAS_REMINDER_MIN_GAP_DAYS', 5),
    'auto_reminders' => (bool) env('DERMAS_AUTO_REMINDERS', false),
];
