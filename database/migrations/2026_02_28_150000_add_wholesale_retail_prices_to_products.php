<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('wholesale_price', 12, 2)->nullable()->after('selling_price');
            $table->decimal('retail_price', 12, 2)->nullable()->after('wholesale_price');
        });

        // Backfill: use selling_price for both where null
        DB::table('products')->whereNull('wholesale_price')->update([
            'wholesale_price' => DB::raw('selling_price'),
            'retail_price' => DB::raw('selling_price'),
        ]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['wholesale_price', 'retail_price']);
        });
    }
};
