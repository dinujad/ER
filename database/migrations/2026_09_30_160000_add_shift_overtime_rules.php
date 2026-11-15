<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            if (! Schema::hasColumn('shifts', 'ot_grace_mins')) {
                $table->unsignedInteger('ot_grace_mins')->default(0)->after('is_overnight');
            }
            if (! Schema::hasColumn('shifts', 'ot_rate_per_hour')) {
                $table->decimal('ot_rate_per_hour', 12, 2)->default(0)->after('ot_grace_mins');
            }
        });

        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'shift_id')) {
                $table->foreignId('shift_id')->nullable()->after('designation_id')->constrained('shifts')->nullOnDelete();
            }
        });

        Schema::table('payroll_run_items', function (Blueprint $table) {
            if (! Schema::hasColumn('payroll_run_items', 'ot_minutes')) {
                $table->unsignedInteger('ot_minutes')->default(0)->after('leave_days');
            }
            if (! Schema::hasColumn('payroll_run_items', 'ot_amount')) {
                $table->decimal('ot_amount', 12, 2)->default(0)->after('ot_minutes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_run_items', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_run_items', 'ot_amount')) {
                $table->dropColumn('ot_amount');
            }
            if (Schema::hasColumn('payroll_run_items', 'ot_minutes')) {
                $table->dropColumn('ot_minutes');
            }
        });

        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'shift_id')) {
                $table->dropConstrainedForeignId('shift_id');
            }
        });

        Schema::table('shifts', function (Blueprint $table) {
            $drop = array_values(array_filter(
                ['ot_grace_mins', 'ot_rate_per_hour'],
                fn ($col) => Schema::hasColumn('shifts', $col)
            ));
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
