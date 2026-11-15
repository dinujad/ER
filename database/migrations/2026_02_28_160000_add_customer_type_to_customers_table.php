<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('customer_type', 20)->default('retail')->after('address'); // 'retail' | 'wholesale'
        });

        // Backfill: customers added by sales person = wholesale
        DB::table('customers')->whereNotNull('added_by')->update(['customer_type' => 'wholesale']);
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('customer_type');
        });
    }
};
