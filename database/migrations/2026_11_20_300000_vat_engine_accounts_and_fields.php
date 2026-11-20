<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 6.3 — VAT engine.
     * - Separate GL accounts so the VAT return and SSCL return read straight from the ledger.
     * - VAT treatment per invoice (standard / zero-rated / exempt).
     * - Input VAT claim flag on purchases (bills).
     */
    public function up(): void
    {
        if (Schema::hasTable('chart_of_accounts')) {
            $accounts = [
                ['gl_code' => '2310', 'name' => 'VAT Output Payable', 'account_type' => 'liability', 'normal_balance' => 'credit'],
                ['gl_code' => '2320', 'name' => 'SSCL Payable', 'account_type' => 'liability', 'normal_balance' => 'credit'],
                ['gl_code' => '1165', 'name' => 'VAT Input Receivable', 'account_type' => 'asset', 'normal_balance' => 'debit'],
            ];
            foreach ($accounts as $acc) {
                if (DB::table('chart_of_accounts')->where('gl_code', $acc['gl_code'])->exists()) {
                    continue;
                }
                $payload = ['gl_code' => $acc['gl_code'], 'name' => $acc['name'], 'account_type' => $acc['account_type'], 'active' => 1];
                if (Schema::hasColumn('chart_of_accounts', 'normal_balance')) {
                    $payload['normal_balance'] = $acc['normal_balance'];
                }
                if (Schema::hasColumn('chart_of_accounts', 'business_id')) {
                    $payload['business_id'] = 0;
                }
                if (Schema::hasColumn('chart_of_accounts', 'created_at')) {
                    $payload['created_at'] = now();
                    $payload['updated_at'] = now();
                }
                DB::table('chart_of_accounts')->insert($payload);
            }
        }

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'invoice_vat_code')) {
                $table->string('invoice_vat_code', 20)->nullable();
            }
        });

        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'input_vat_claimable')) {
                $table->boolean('input_vat_claimable')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'invoice_vat_code')) {
                $table->dropColumn('invoice_vat_code');
            }
        });
        Schema::table('purchases', function (Blueprint $table) {
            if (Schema::hasColumn('purchases', 'input_vat_claimable')) {
                $table->dropColumn('input_vat_claimable');
            }
        });
        // GL accounts are kept: journal lines may reference them.
    }
};
