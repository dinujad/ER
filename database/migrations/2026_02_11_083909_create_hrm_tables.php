<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Departments
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique()->nullable();
            $table->unsignedBigInteger('manager_id')->nullable(); // FK logic later
            $table->timestamps();
        });

        // 2. Designations
        Schema::create('designations', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->integer('level')->default(1);
            $table->timestamps();
        });

        // 3. Shifts
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., General, Morning
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('grace_period_mins')->default(15);
            $table->integer('late_threshold_mins')->default(30);
            $table->integer('early_leave_allowed_mins')->default(0);
            $table->boolean('is_overnight')->default(false);
            $table->timestamps();
        });
        
        // 4. Update Employees Table (Add FKs and Device Mapping)
        Schema::table('employees', function (Blueprint $table) {
            // Check if columns exist before adding (safety)
            if (!Schema::hasColumn('employees', 'department_id')) {
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            }
            if (!Schema::hasColumn('employees', 'designation_id')) {
                $table->foreignId('designation_id')->nullable()->constrained('designations')->nullOnDelete();
            }
             if (!Schema::hasColumn('employees', 'device_enroll_id')) {
                $table->string('device_enroll_id')->unique()->nullable()->comment('Hikvision employeeNo');
            }
        });

        // 5. Employee Shifts (Roster)
        Schema::create('employee_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->boolean('is_off_day')->default(false);
            $table->unique(['employee_id', 'date']);
            $table->timestamps();
        });

        // 6. Attendance Raw Logs (Immutable)
        // Stores payload from Hikvision Push
        Schema::create('attendance_raw_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('device_ip')->nullable();
            $table->string('device_id')->nullable();
            $table->string('employee_device_id'); // employeeNoString from JSON
            $table->dateTime('event_time');
            $table->string('event_type')->nullable(); // authenticated, etc
            $table->json('payload_json')->nullable();
            $table->timestamp('received_at')->useCurrent();
            $table->string('unique_hash')->unique()->comment('md5(device_id+time+emp_id)');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        // 7. Attendance Dailies (Processed)
        Schema::create('attendance_dailies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->foreignId('shift_id')->nullable()->constrained();
            
            $table->dateTime('first_in')->nullable();
            $table->dateTime('last_out')->nullable();
            
            $table->integer('worked_minutes')->default(0);
            $table->integer('late_minutes')->default(0);
            $table->integer('overtime_minutes')->default(0);
            
            $table->string('status')->default('absent'); // present, absent, leave, late, etc.
            $table->boolean('is_finalized')->default(false);
            
            $table->unique(['employee_id', 'date']);
            $table->timestamps();
        });

        // 8. Manual Adjustments
         Schema::create('attendance_manual_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_daily_id')->constrained('attendance_dailies')->cascadeOnDelete();
            $table->unsignedBigInteger('adjusted_by_user_id')->nullable();
            $table->dateTime('original_in')->nullable();
            $table->dateTime('new_in')->nullable();
            $table->dateTime('original_out')->nullable();
            $table->dateTime('new_out')->nullable();
            $table->string('reason');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_manual_adjustments');
        Schema::dropIfExists('attendance_dailies');
        Schema::dropIfExists('attendance_raw_logs');
        Schema::dropIfExists('employee_shifts');
        
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'department_id')) $table->dropForeign(['department_id']);
            if (Schema::hasColumn('employees', 'designation_id')) $table->dropForeign(['designation_id']);
            $table->dropColumn(['department_id', 'designation_id', 'device_enroll_id']);
        });

        Schema::dropIfExists('shifts');
        Schema::dropIfExists('designations');
        Schema::dropIfExists('departments');
    }
};
