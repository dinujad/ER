<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sale_return_items')) {
            return;
        }

        Schema::table('sale_return_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_return_items', 'list_unit_price')) {
                $table->decimal('list_unit_price', 12, 2)->nullable()->after('unit_price');
            }
            if (!Schema::hasColumn('sale_return_items', 'effective_unit_price')) {
                $table->decimal('effective_unit_price', 12, 2)->nullable()->after('list_unit_price');
            }
        });

        if (Schema::hasColumn('sale_return_items', 'list_unit_price')
            && Schema::hasColumn('sale_return_items', 'effective_unit_price')) {
            DB::table('sale_return_items')
                ->whereNull('list_unit_price')
                ->update([
                    'list_unit_price' => DB::raw('unit_price'),
                    'effective_unit_price' => DB::raw('unit_price'),
                ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('sale_return_items')) {
            return;
        }

        Schema::table('sale_return_items', function (Blueprint $table) {
            if (Schema::hasColumn('sale_return_items', 'effective_unit_price')) {
                $table->dropColumn('effective_unit_price');
            }
            if (Schema::hasColumn('sale_return_items', 'list_unit_price')) {
                $table->dropColumn('list_unit_price');
            }
        });
    }
};
