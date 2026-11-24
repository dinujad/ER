<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** 4020 Sales Discounts (contra revenue) — settlement discounts given on customer payments. */
    public function up(): void
    {
        if (!Schema::hasTable('chart_of_accounts') || DB::table('chart_of_accounts')->where('gl_code', '4020')->exists()) {
            return;
        }
        $row = ['gl_code' => '4020', 'name' => 'Sales Discounts', 'account_type' => 'income', 'active' => 1];
        foreach (['normal_balance' => 'debit', 'business_id' => 0] as $col => $v) {
            if (Schema::hasColumn('chart_of_accounts', $col)) {
                $row[$col] = $v;
            }
        }
        if (Schema::hasColumn('chart_of_accounts', 'created_at')) {
            $row['created_at'] = now();
            $row['updated_at'] = now();
        }
        DB::table('chart_of_accounts')->insert($row);
    }

    public function down(): void
    {
        // kept: journal lines may reference it
    }
};
