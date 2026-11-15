<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_run_items', function (Blueprint $table) {
            $table->string('reference_no', 50)->nullable()->after('employee_id');
            $table->string('payment_status', 20)->default('due')->after('net_salary');
            $table->timestamp('paid_at')->nullable()->after('payment_status');
            $table->string('payment_reference', 100)->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_run_items', function (Blueprint $table) {
            $table->dropColumn(['reference_no', 'payment_status', 'paid_at', 'payment_reference']);
        });
    }
};
