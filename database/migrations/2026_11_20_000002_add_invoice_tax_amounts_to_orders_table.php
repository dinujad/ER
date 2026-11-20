<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SSCL + VAT the customer is charged on top of total_amount (goods value).
     * NULL = invoice issued before tax was charged in the system (legacy) — due stays total_amount.
     * Set when admin issues the invoice; kept in sync by Order::saving when total_amount changes.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'invoice_sscl_amount')) {
                $table->decimal('invoice_sscl_amount', 12, 2)->nullable()->after('invoice_show_sscl');
            }
            if (!Schema::hasColumn('orders', 'invoice_vat_amount')) {
                $table->decimal('invoice_vat_amount', 12, 2)->nullable()->after('invoice_sscl_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach (['invoice_vat_amount', 'invoice_sscl_amount'] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
