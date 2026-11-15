<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('accounting_txn_number')->nullable()->after('note')
                  ->comment('journal_entries transaction_number for this expense');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->string('accounting_txn_number')->nullable()->after('cancel_reason')
                  ->comment('journal_entries transaction_number for this purchase');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('accounting_txn_number');
        });
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('accounting_txn_number');
        });
    }
};
