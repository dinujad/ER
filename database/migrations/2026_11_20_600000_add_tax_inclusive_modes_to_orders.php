<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Separate VAT / SSCL choice per invoice:
     *   *_inclusive = false → tax added on top of the item prices
     *   *_inclusive = true  → item prices already contain the tax (it is extracted, total stays the item total)
     * invoice_goods_amount = taxable value excluding all tax (what goes to Sales). NULL = same as total_amount.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'invoice_vat_inclusive')) {
                $table->boolean('invoice_vat_inclusive')->nullable();
            }
            if (!Schema::hasColumn('orders', 'invoice_sscl_inclusive')) {
                $table->boolean('invoice_sscl_inclusive')->nullable();
            }
            if (!Schema::hasColumn('orders', 'invoice_goods_amount')) {
                $table->decimal('invoice_goods_amount', 14, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach (['invoice_vat_inclusive', 'invoice_sscl_inclusive', 'invoice_goods_amount'] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
