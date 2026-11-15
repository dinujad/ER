<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variations', function (Blueprint $table) {
            $table->decimal('wholesale_price', 12, 2)->nullable()->after('default_sell_price');
            $table->decimal('retail_price', 12, 2)->nullable()->after('wholesale_price');
        });

        DB::table('product_variations')->whereNull('wholesale_price')->update([
            'wholesale_price' => DB::raw('default_sell_price'),
            'retail_price' => DB::raw('default_sell_price'),
        ]);
    }

    public function down(): void
    {
        Schema::table('product_variations', function (Blueprint $table) {
            $table->dropColumn(['wholesale_price', 'retail_price']);
        });
    }
};
