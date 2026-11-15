<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->date('cheque_date')->nullable()->after('payment_reference');
            $table->string('cheque_bank')->nullable()->after('cheque_date');
            $table->string('cheque_number', 100)->nullable()->after('cheque_bank');
        });

        Schema::table('purchase_payments', function (Blueprint $table) {
            $table->date('cheque_date')->nullable()->after('reference');
            $table->string('cheque_bank')->nullable()->after('cheque_date');
            $table->string('cheque_number', 100)->nullable()->after('cheque_bank');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_payments', function (Blueprint $table) {
            $table->dropColumn(['cheque_date', 'cheque_bank', 'cheque_number']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['cheque_date', 'cheque_bank', 'cheque_number']);
        });
    }
};
