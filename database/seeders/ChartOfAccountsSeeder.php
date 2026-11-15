<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Add missing account subtypes (income & expense) ──────────────
        $extraSubtypes = [
            ['account_type' => 'income',   'name' => 'Operating Revenue',    'description' => 'Core business income'],
            ['account_type' => 'income',   'name' => 'Other Income',         'description' => 'Non-operating income'],
            ['account_type' => 'expense',  'name' => 'Cost of Goods Sold',   'description' => 'Direct costs of goods sold'],
            ['account_type' => 'expense',  'name' => 'Operating Expenses',   'description' => 'Day-to-day operating expenses'],
            ['account_type' => 'expense',  'name' => 'Other Expenses',       'description' => 'Non-operating expenses'],
        ];

        foreach ($extraSubtypes as $sub) {
            DB::table('account_subtypes')->updateOrInsert(
                ['account_type' => $sub['account_type'], 'name' => $sub['name']],
                array_merge($sub, ['business_id' => 0, 'active' => 1, 'created_at' => now(), 'updated_at' => now()])
            );
        }

        // Reload subtype ids
        $subtypeId = function (string $type, string $name): ?int {
            $row = DB::table('account_subtypes')->where('account_type', $type)->where('name', $name)->first();
            return $row ? $row->id : null;
        };

        // ── 2. Seed essential Chart of Accounts ─────────────────────────────
        $accounts = [
            // ── ASSETS ──────────────────────────────────────────────────────
            [
                'gl_code'           => '1000',
                'name'              => 'Cash',
                'account_type'      => 'asset',
                'account_subtype_id'=> $subtypeId('asset', 'Cash and Cash Equivalents (CCE)'),
                'allow_manual'      => 1,
            ],
            [
                'gl_code'           => '1010',
                'name'              => 'Bank Account',
                'account_type'      => 'asset',
                'account_subtype_id'=> $subtypeId('asset', 'Cash and Cash Equivalents (CCE)'),
                'allow_manual'      => 1,
            ],
            [
                'gl_code'           => '1150',
                'name'              => 'Post-Dated Cheques (PDC / Uncleared)',
                'account_type'      => 'asset',
                'account_subtype_id'=> $subtypeId('asset', 'Current Assets'),
                'allow_manual'      => 1,
            ],
            [
                'gl_code'           => '1200',
                'name'              => 'Accounts Receivable',
                'account_type'      => 'asset',
                'account_subtype_id'=> $subtypeId('asset', 'Accounts Receivable(A/R)'),
                'allow_manual'      => 1,
            ],
            [
                'gl_code'           => '1300',
                'name'              => 'Inventory',
                'account_type'      => 'asset',
                'account_subtype_id'=> $subtypeId('asset', 'Current Assets'),
                'allow_manual'      => 1,
            ],
            [
                'gl_code'           => '1310',
                'name'              => 'Raw Material Inventory',
                'account_type'      => 'asset',
                'account_subtype_id'=> $subtypeId('asset', 'Current Assets'),
                'allow_manual'      => 1,
            ],
            [
                'gl_code'           => '1320',
                'name'              => 'Work in Progress Inventory',
                'account_type'      => 'asset',
                'account_subtype_id'=> $subtypeId('asset', 'Current Assets'),
                'allow_manual'      => 1,
            ],
            [
                'gl_code'           => '1330',
                'name'              => 'Finished Goods Inventory',
                'account_type'      => 'asset',
                'account_subtype_id'=> $subtypeId('asset', 'Current Assets'),
                'allow_manual'      => 1,
            ],

            // ── LIABILITIES ─────────────────────────────────────────────────
            [
                'gl_code'           => '2000',
                'name'              => 'Accounts Payable',
                'account_type'      => 'liability',
                'account_subtype_id'=> $subtypeId('liability', 'Accounts Payable (A/P)'),
                'allow_manual'      => 1,
            ],
            [
                'gl_code'           => '2100',
                'name'              => 'Other Current Liabilities',
                'account_type'      => 'liability',
                'account_subtype_id'=> $subtypeId('liability', 'Current Liabilities'),
                'allow_manual'      => 1,
            ],

            // ── EQUITY ──────────────────────────────────────────────────────
            [
                'gl_code'           => '3000',
                'name'              => "Owner's Equity",
                'account_type'      => 'equity',
                'account_subtype_id'=> $subtypeId('equity', "Owner's Equity"),
                'allow_manual'      => 1,
            ],
            [
                'gl_code'           => '3100',
                'name'              => 'Retained Earnings',
                'account_type'      => 'equity',
                'account_subtype_id'=> $subtypeId('equity', "Owner's Equity"),
                'allow_manual'      => 1,
            ],

            // ── INCOME ──────────────────────────────────────────────────────
            [
                'gl_code'           => '4000',
                'name'              => 'Sales Revenue',
                'account_type'      => 'income',
                'account_subtype_id'=> $subtypeId('income', 'Operating Revenue'),
                'allow_manual'      => 1,
            ],
            [
                'gl_code'           => '4010',
                'name'              => 'Sales Returns and Allowances',
                'account_type'      => 'income',
                'account_subtype_id'=> $subtypeId('income', 'Operating Revenue'),
                'allow_manual'      => 1,
            ],
            [
                'gl_code'           => '4100',
                'name'              => 'Other Income',
                'account_type'      => 'income',
                'account_subtype_id'=> $subtypeId('income', 'Other Income'),
                'allow_manual'      => 1,
            ],

            // ── EXPENSES ────────────────────────────────────────────────────
            [
                'gl_code'           => '5000',
                'name'              => 'Cost of Goods Sold',
                'account_type'      => 'expense',
                'account_subtype_id'=> $subtypeId('expense', 'Cost of Goods Sold'),
                'allow_manual'      => 1,
            ],
            [
                'gl_code'           => '5100',
                'name'              => 'Purchases',
                'account_type'      => 'expense',
                'account_subtype_id'=> $subtypeId('expense', 'Cost of Goods Sold'),
                'allow_manual'      => 1,
            ],
            [
                'gl_code'           => '6000',
                'name'              => 'General & Administrative Expenses',
                'account_type'      => 'expense',
                'account_subtype_id'=> $subtypeId('expense', 'Operating Expenses'),
                'allow_manual'      => 1,
            ],
            [
                'gl_code'           => '6100',
                'name'              => 'Salary & Wages Expense',
                'account_type'      => 'expense',
                'account_subtype_id'=> $subtypeId('expense', 'Operating Expenses'),
                'allow_manual'      => 1,
            ],
            [
                'gl_code'           => '6200',
                'name'              => 'Utilities Expense',
                'account_type'      => 'expense',
                'account_subtype_id'=> $subtypeId('expense', 'Operating Expenses'),
                'allow_manual'      => 1,
            ],
            [
                'gl_code'           => '6300',
                'name'              => 'Transport & Delivery Expense',
                'account_type'      => 'expense',
                'account_subtype_id'=> $subtypeId('expense', 'Operating Expenses'),
                'allow_manual'      => 1,
            ],
            [
                'gl_code'           => '6900',
                'name'              => 'Other Operating Expenses',
                'account_type'      => 'expense',
                'account_subtype_id'=> $subtypeId('expense', 'Other Expenses'),
                'allow_manual'      => 1,
            ],
        ];

        foreach ($accounts as $acc) {
            DB::table('chart_of_accounts')->updateOrInsert(
                ['gl_code' => $acc['gl_code'], 'business_id' => 0],
                array_merge($acc, [
                    'business_id'  => 0,
                    'active'       => 1,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ])
            );
        }

        $this->command->info('Chart of Accounts seeded ('.count($accounts).' accounts).');
    }
}
