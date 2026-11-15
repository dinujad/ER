<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class PopulateAccountSubtypesTable extends Migration
{
    public function up(): void
    {
        if (DB::table('account_subtypes')->exists()) {
            return;
        }

        $timestamp = now()->toDateTimeString();

        DB::table('account_subtypes')->insert([
            ['id' => 1,  'business_id' => 0, 'account_type' => 'asset',     'name' => 'Current Assets',                  'active' => 1, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['id' => 2,  'business_id' => 0, 'account_type' => 'asset',     'name' => 'Non- Current Assets',             'active' => 1, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['id' => 3,  'business_id' => 0, 'account_type' => 'asset',     'name' => 'Fixed Assets',                    'active' => 1, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['id' => 4,  'business_id' => 0, 'account_type' => 'asset',     'name' => 'Accounts Receivable(A/R)',         'active' => 1, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['id' => 5,  'business_id' => 0, 'account_type' => 'asset',     'name' => 'Cash and Cash Equivalents (CCE)', 'active' => 1, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['id' => 6,  'business_id' => 0, 'account_type' => 'liability', 'name' => 'Current Liabilities',             'active' => 1, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['id' => 7,  'business_id' => 0, 'account_type' => 'liability', 'name' => 'Non- Current Liabilities',        'active' => 1, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['id' => 8,  'business_id' => 0, 'account_type' => 'liability', 'name' => 'Accounts Payable (A/P)',          'active' => 1, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['id' => 9,  'business_id' => 0, 'account_type' => 'liability', 'name' => 'Credit Card',                    'active' => 1, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['id' => 10, 'business_id' => 0, 'account_type' => 'equity',    'name' => 'Owner\'s Equity',                 'active' => 1, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['id' => 11, 'business_id' => 0, 'account_type' => 'income',    'name' => 'Income',                          'active' => 1, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['id' => 12, 'business_id' => 0, 'account_type' => 'income',    'name' => 'Other Income',                    'active' => 1, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['id' => 13, 'business_id' => 0, 'account_type' => 'expense',   'name' => 'Expense',                         'active' => 1, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['id' => 14, 'business_id' => 0, 'account_type' => 'expense',   'name' => 'Cost of Sales',                   'active' => 1, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['id' => 15, 'business_id' => 0, 'account_type' => 'expense',   'name' => 'Other Expense',                   'active' => 1, 'created_at' => $timestamp, 'updated_at' => $timestamp],
        ]);
    }

    public function down(): void
    {
        DB::table('account_subtypes')->whereIn('id', range(1, 15))->where('business_id', 0)->delete();
    }
}
