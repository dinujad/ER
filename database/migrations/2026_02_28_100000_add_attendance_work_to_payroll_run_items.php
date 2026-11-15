<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_run_items', function (Blueprint $table) {
            $table->unsignedSmallInteger('days_present')->default(0)->after('employee_id');
            $table->unsignedSmallInteger('days_absent')->default(0)->after('days_present');
            $table->decimal('leave_days', 5, 2)->default(0)->after('days_absent');
            $table->decimal('work_duration', 10, 2)->default(1)->after('leave_days');
            $table->string('work_duration_unit', 30)->default('Month')->after('work_duration');
            $table->decimal('amount_per_unit', 12, 2)->default(0)->after('work_duration_unit');
            $table->text('note')->nullable()->after('details');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_run_items', function (Blueprint $table) {
            $table->dropColumn([
                'days_present', 'days_absent', 'leave_days',
                'work_duration', 'work_duration_unit', 'amount_per_unit', 'note',
            ]);
        });
    }
};
