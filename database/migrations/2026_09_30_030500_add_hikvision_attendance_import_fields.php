<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_dailies', function (Blueprint $table) {
            if (! Schema::hasColumn('attendance_dailies', 'session_key')) {
                $table->string('session_key', 191)->default('')->after('date');
            }
            if (! Schema::hasColumn('attendance_dailies', 'source')) {
                $table->string('source', 40)->nullable()->after('status');
            }
            if (! Schema::hasColumn('attendance_dailies', 'hikvision_status')) {
                $table->string('hikvision_status', 120)->nullable()->after('source');
            }
            if (! Schema::hasColumn('attendance_dailies', 'timetable')) {
                $table->string('timetable')->nullable()->after('hikvision_status');
            }
            if (! Schema::hasColumn('attendance_dailies', 'scheduled_start')) {
                $table->dateTime('scheduled_start')->nullable()->after('timetable');
            }
            if (! Schema::hasColumn('attendance_dailies', 'scheduled_end')) {
                $table->dateTime('scheduled_end')->nullable()->after('scheduled_start');
            }
            if (! Schema::hasColumn('attendance_dailies', 'clock_in_source')) {
                $table->string('clock_in_source')->nullable()->after('scheduled_end');
            }
            if (! Schema::hasColumn('attendance_dailies', 'clock_out_source')) {
                $table->string('clock_out_source')->nullable()->after('clock_in_source');
            }
            if (! Schema::hasColumn('attendance_dailies', 'absent_minutes')) {
                $table->unsignedInteger('absent_minutes')->nullable()->after('overtime_minutes');
            }
            if (! Schema::hasColumn('attendance_dailies', 'early_leave_minutes')) {
                $table->unsignedInteger('early_leave_minutes')->nullable()->after('absent_minutes');
            }
            if (! Schema::hasColumn('attendance_dailies', 'break_minutes')) {
                $table->unsignedInteger('break_minutes')->nullable()->after('early_leave_minutes');
            }
            if (! Schema::hasColumn('attendance_dailies', 'leave_minutes')) {
                $table->unsignedInteger('leave_minutes')->nullable()->after('break_minutes');
            }
            if (! Schema::hasColumn('attendance_dailies', 'workday_overtime_minutes')) {
                $table->unsignedInteger('workday_overtime_minutes')->nullable()->after('leave_minutes');
            }
            if (! Schema::hasColumn('attendance_dailies', 'weekend_overtime_minutes')) {
                $table->unsignedInteger('weekend_overtime_minutes')->nullable()->after('workday_overtime_minutes');
            }
            if (! Schema::hasColumn('attendance_dailies', 'manually_corrected')) {
                $table->boolean('manually_corrected')->default(false)->after('is_finalized');
            }
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE attendance_dailies MODIFY worked_minutes INT NULL');
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE attendance_dailies MODIFY late_minutes INT NULL');
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE attendance_dailies MODIFY overtime_minutes INT NULL');
        }

        try {
            Schema::table('attendance_dailies', function (Blueprint $table) {
                $table->dropUnique(['employee_id', 'date']);
            });
        } catch (\Throwable) {
        }

        try {
            Schema::table('attendance_dailies', function (Blueprint $table) {
                $table->unique(['employee_id', 'date', 'session_key'], 'attendance_dailies_employee_date_session_unique');
            });
        } catch (\Throwable) {
        }

        if (! Schema::hasTable('attendance_imports')) {
            Schema::create('attendance_imports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('filename');
                $table->string('source', 40)->default('hik-connect');
                $table->unsignedInteger('created_count')->default(0);
                $table->unsignedInteger('updated_count')->default(0);
                $table->unsignedInteger('unchanged_count')->default(0);
                $table->unsignedInteger('skipped_count')->default(0);
                $table->unsignedInteger('failed_count')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_imports');

        try {
            Schema::table('attendance_dailies', function (Blueprint $table) {
                $table->dropUnique('attendance_dailies_employee_date_session_unique');
            });
        } catch (\Throwable) {
        }

        Schema::table('attendance_dailies', function (Blueprint $table) {
            foreach ([
                'weekend_overtime_minutes', 'workday_overtime_minutes', 'leave_minutes', 'break_minutes',
                'early_leave_minutes', 'absent_minutes', 'clock_out_source', 'clock_in_source',
                'scheduled_end', 'scheduled_start', 'timetable', 'hikvision_status', 'source',
                'manually_corrected', 'session_key',
            ] as $col) {
                if (Schema::hasColumn('attendance_dailies', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
