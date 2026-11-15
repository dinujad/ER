<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('discount_type', 20)->nullable()->after('amount');
            $table->decimal('discount_value', 12, 4)->nullable()->after('discount_type');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('discount_value');
            $table->decimal('settlement_amount', 12, 2)->nullable()->after('discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_value', 'discount_amount', 'settlement_amount']);
        });
    }
};
