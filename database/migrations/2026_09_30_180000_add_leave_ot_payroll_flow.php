<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            if (! Schema::hasColumn('leave_types', 'carry_forward_days')) {
                $table->unsignedSmallInteger('carry_forward_days')->default(0)->after('days_per_year');
            }
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('leave_requests', 'duration_unit')) {
                $table->string('duration_unit', 20)->default('full')->after('days');
            }
            if (! Schema::hasColumn('leave_requests', 'hours')) {
                $table->decimal('hours', 6, 2)->nullable()->after('duration_unit');
            }
            if (! Schema::hasColumn('leave_requests', 'document_path')) {
                $table->string('document_path')->nullable()->after('reason');
            }
            if (! Schema::hasColumn('leave_requests', 'supervisor_status')) {
                $table->string('supervisor_status', 20)->default('pending')->after('status');
            }
            if (! Schema::hasColumn('leave_requests', 'hr_status')) {
                $table->string('hr_status', 20)->default('pending')->after('supervisor_status');
            }
        });

        Schema::table('shifts', function (Blueprint $table) {
            if (! Schema::hasColumn('shifts', 'weekend_ot_rate_per_hour')) {
                $table->decimal('weekend_ot_rate_per_hour', 12, 2)->default(0)->after('ot_rate_per_hour');
            }
            if (! Schema::hasColumn('shifts', 'holiday_ot_rate_per_hour')) {
                $table->decimal('holiday_ot_rate_per_hour', 12, 2)->default(0)->after('weekend_ot_rate_per_hour');
            }
            if (! Schema::hasColumn('shifts', 'ot_daily_limit_hours')) {
                $table->decimal('ot_daily_limit_hours', 5, 2)->default(4)->after('holiday_ot_rate_per_hour');
            }
        });

        Schema::create('ot_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->string('category', 20)->default('workday');
            $table->decimal('planned_hours', 6, 2);
            $table->decimal('approved_hours', 6, 2)->nullable();
            $table->unsignedInteger('actual_minutes')->default(0);
            $table->decimal('rate_per_hour', 12, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('reason')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('supervisor_status', 20)->default('pending');
            $table->string('hr_status', 20)->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_pay_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20);
            $table->string('title');
            $table->decimal('total_amount', 12, 2);
            $table->decimal('monthly_amount', 12, 2);
            $table->decimal('balance', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('payroll_run_items', function (Blueprint $table) {
            if (! Schema::hasColumn('payroll_run_items', 'nopay_amount')) {
                $table->decimal('nopay_amount', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('payroll_run_items', 'epf_amount')) {
                $table->decimal('epf_amount', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('payroll_run_items', 'etf_amount')) {
                $table->decimal('etf_amount', 12, 2)->default(0);
            }
        });

        Schema::create('payroll_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_run_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('summary');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_corrections');
        Schema::dropIfExists('employee_pay_deductions');
        Schema::dropIfExists('ot_requests');

        Schema::table('payroll_run_items', function (Blueprint $table) {
            foreach (['nopay_amount', 'epf_amount', 'etf_amount'] as $col) {
                if (Schema::hasColumn('payroll_run_items', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        Schema::table('shifts', function (Blueprint $table) {
            foreach (['weekend_ot_rate_per_hour', 'holiday_ot_rate_per_hour', 'ot_daily_limit_hours'] as $col) {
                if (Schema::hasColumn('shifts', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        Schema::table('leave_requests', function (Blueprint $table) {
            foreach (['duration_unit', 'hours', 'document_path', 'supervisor_status', 'hr_status'] as $col) {
                if (Schema::hasColumn('leave_requests', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        Schema::table('leave_types', function (Blueprint $table) {
            if (Schema::hasColumn('leave_types', 'carry_forward_days')) {
                $table->dropColumn('carry_forward_days');
            }
        });
    }
};
