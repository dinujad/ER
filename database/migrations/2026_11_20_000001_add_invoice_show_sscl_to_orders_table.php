<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Normal (non-TIN) invoices only: true = print SSCL as its own line,
     * false = fold SSCL into each item's price. TIN customers always get the
     * full tax-invoice layout regardless of this flag.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'invoice_show_sscl')) {
                $table->boolean('invoice_show_sscl')->default(false)->after('invoice_other_expenses');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'invoice_show_sscl')) {
                $table->dropColumn('invoice_show_sscl');
            }
        });
    }
};
