<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'store_id')) {
                $table->foreignId('store_id')->nullable()->after('id')->constrained()->onDelete('set null');
            }
            if (!Schema::hasColumn('orders', 'subtotal')) {
                $table->decimal('subtotal', 12, 2)->default(0)->after('total_amount');
            }
            if (!Schema::hasColumn('orders', 'tax_type')) {
                $table->string('tax_type')->nullable()->after('discount_value'); // fixed, percentage
            }
            if (!Schema::hasColumn('orders', 'tax_value')) {
                $table->decimal('tax_value', 10, 2)->default(0)->after('tax_type');
            }
            if (!Schema::hasColumn('orders', 'shipping')) {
                $table->decimal('shipping', 10, 2)->default(0)->after('tax_value');
            }
            if (!Schema::hasColumn('orders', 'register_id')) {
                $table->foreignId('register_id')->nullable()->after('user_id')->constrained()->onDelete('set null');
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'credit_limit')) {
                $table->decimal('credit_limit', 12, 2)->default(0)->after('address');
            }
            if (!Schema::hasColumn('customers', 'due_balance')) {
                $table->decimal('due_balance', 12, 2)->default(0)->after('credit_limit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'register_id')) $table->dropForeign(['register_id']);
            if (Schema::hasColumn('orders', 'store_id')) $table->dropForeign(['store_id']);
            foreach (['subtotal', 'tax_type', 'tax_value', 'shipping', 'store_id', 'register_id'] as $c) {
                if (Schema::hasColumn('orders', $c)) $table->dropColumn($c);
            }
        });
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'credit_limit')) $table->dropColumn('credit_limit');
            if (Schema::hasColumn('customers', 'due_balance')) $table->dropColumn('due_balance');
        });
    }
};
