<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Auto reminder to customer (sent X days before cheque_date by scheduler)
            $table->timestamp('customer_reminder_sent_at')->nullable()->after('cheque_followup_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('customer_reminder_sent_at');
        });
    }
};
