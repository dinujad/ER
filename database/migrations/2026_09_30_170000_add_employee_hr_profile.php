<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'employee_code')) {
                $table->string('employee_code', 32)->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('employees', 'address')) {
                $table->text('address')->nullable();
            }
            if (! Schema::hasColumn('employees', 'emergency_contact_name')) {
                $table->string('emergency_contact_name')->nullable();
            }
            if (! Schema::hasColumn('employees', 'emergency_contact_phone')) {
                $table->string('emergency_contact_phone', 40)->nullable();
            }
            if (! Schema::hasColumn('employees', 'id_type')) {
                $table->string('id_type', 30)->nullable();
            }
            if (! Schema::hasColumn('employees', 'employment_type')) {
                $table->string('employment_type', 30)->default('permanent');
            }
            if (! Schema::hasColumn('employees', 'pay_type')) {
                $table->string('pay_type', 30)->default('monthly');
            }
            if (! Schema::hasColumn('employees', 'pay_rate')) {
                $table->decimal('pay_rate', 12, 2)->nullable();
            }
            if (! Schema::hasColumn('employees', 'bank_name')) {
                $table->string('bank_name')->nullable();
            }
            if (! Schema::hasColumn('employees', 'bank_branch')) {
                $table->string('bank_branch')->nullable();
            }
            if (! Schema::hasColumn('employees', 'bank_account_name')) {
                $table->string('bank_account_name')->nullable();
            }
            if (! Schema::hasColumn('employees', 'bank_account_number')) {
                $table->string('bank_account_number', 50)->nullable();
            }
            if (! Schema::hasColumn('employees', 'payroll_registration_no')) {
                $table->string('payroll_registration_no', 50)->nullable();
            }
            if (! Schema::hasColumn('employees', 'reporting_manager_id')) {
                $table->foreignId('reporting_manager_id')->nullable()->constrained('employees')->nullOnDelete();
            }
        });

        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('change_type', 30);
            $table->date('effective_date');
            $table->string('summary');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_changes');
        Schema::dropIfExists('employee_documents');

        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'reporting_manager_id')) {
                $table->dropConstrainedForeignId('reporting_manager_id');
            }
            $cols = [
                'employee_code', 'address', 'emergency_contact_name', 'emergency_contact_phone',
                'id_type', 'employment_type', 'pay_type', 'pay_rate',
                'bank_name', 'bank_branch', 'bank_account_name', 'bank_account_number', 'payroll_registration_no',
            ];
            $drop = array_values(array_filter($cols, fn ($col) => Schema::hasColumn('employees', $col)));
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
