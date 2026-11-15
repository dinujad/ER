<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incentive_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('production_line_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('manufacturing_job_id')->nullable()->constrained()->nullOnDelete();
            $table->date('work_date');
            $table->string('operation')->nullable();
            $table->string('mode', 20)->default('individual');
            $table->decimal('rate', 12, 2)->default(0);
            $table->unsignedInteger('target_qty')->default(0);
            $table->unsignedInteger('actual_qty')->default(0);
            $table->unsignedInteger('rejected_qty')->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('status', 20)->default('pending');
            $table->foreignId('payroll_run_item_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('employee_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->string('machine')->nullable();
            $table->string('operation')->nullable();
            $table->unsignedTinyInteger('level')->default(1);
            $table->date('assessed_on')->nullable();
            $table->timestamps();
        });

        Schema::create('training_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('kind', 20)->default('skill');
            $table->date('session_date');
            $table->boolean('attended')->default(true);
            $table->string('certificate_no')->nullable();
            $table->date('expires_on')->nullable();
            $table->timestamps();
        });

        Schema::create('line_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_line_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('required_count')->default(0);
            $table->string('skill_name')->nullable();
            $table->timestamps();
        });

        Schema::create('staff_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('production_line_id')->nullable()->constrained()->nullOnDelete();
            $table->date('work_date');
            $table->string('kind', 30);
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('job_vacancies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('production_line_id')->nullable()->constrained()->nullOnDelete();
            $table->string('skill_name')->nullable();
            $table->string('status', 20)->default('open');
            $table->timestamps();
        });

        Schema::create('job_applicants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_vacancy_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('stage', 30)->default('applied');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('period', 20);
            $table->unsignedTinyInteger('attendance_score')->default(0);
            $table->unsignedTinyInteger('quality_score')->default(0);
            $table->unsignedTinyInteger('productivity_score')->default(0);
            $table->unsignedTinyInteger('teamwork_score')->default(0);
            $table->text('comments')->nullable();
            $table->string('recommendation', 30)->default('none');
            $table->boolean('acknowledged')->default(false);
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('welfare_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 30);
            $table->string('detail');
            $table->date('issued_on');
            $table->date('returned_on')->nullable();
            $table->decimal('deduction_amount', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('safety_incidents', function (Blueprint $table) {
            $table->id();
            $table->date('incident_date');
            $table->string('place');
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 30);
            $table->text('first_aid')->nullable();
            $table->text('follow_up')->nullable();
            $table->text('corrective_action')->nullable();
            $table->string('responsible_name')->nullable();
            $table->text('medical_note')->nullable();
            $table->timestamps();
        });

        Schema::create('grievances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('handler_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('summary');
            $table->string('status', 30)->default('open');
            $table->text('resolution')->nullable();
            $table->text('employee_response')->nullable();
            $table->timestamps();
        });

        Schema::create('hr_letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 30);
            $table->string('title');
            $table->date('issued_on');
            $table->date('expires_on')->nullable();
            $table->text('body')->nullable();
            $table->boolean('acknowledged')->default(false);
            $table->timestamps();
        });

        Schema::create('resignations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('requested_on');
            $table->date('last_day');
            $table->text('exit_notes')->nullable();
            $table->boolean('assets_returned')->default(false);
            $table->boolean('loans_cleared')->default(false);
            $table->boolean('access_disabled')->default(false);
            $table->boolean('confirmed')->default(false);
            $table->timestamps();
        });

        Schema::create('hr_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('subject');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'hr_audit_logs', 'resignations', 'hr_letters', 'grievances', 'safety_incidents',
            'welfare_records', 'performance_reviews', 'job_applicants', 'job_vacancies',
            'staff_assignments', 'line_requirements', 'training_records', 'employee_skills', 'incentive_claims',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
