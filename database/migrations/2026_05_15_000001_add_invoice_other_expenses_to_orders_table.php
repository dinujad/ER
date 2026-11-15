<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'invoice_other_expenses')) {
                $table->decimal('invoice_other_expenses', 12, 2)->default(0)->after('shipping');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'invoice_other_expenses')) {
                $table->dropColumn('invoice_other_expenses');
            }
        });
    }
};
