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
        // Cleanup from potential previous failed runs
        Schema::dropIfExists('employees');
        Schema::dropIfExists('commission_agents');
        Schema::dropIfExists('manufacturing_jobs');
        Schema::dropIfExists('job_line_assignments');

        // 1. Employees Table
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('nic')->unique();
            $table->string('contact');
            $table->enum('role', ['admin', 'storekeeper', 'line_supervisor', 'cashier', 'hr', 'sales_agent']);
            $table->string('assigned_line')->nullable(); // For supervisors
            $table->date('join_date');
            
            // Optional: Link to a system user if they need login access
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            
            $table->timestamps();
        });

        // 2. Commission Agents Table
        Schema::create('commission_agents', function (Blueprint $table) {
            $table->id();
            $table->string('agent_name');
            $table->string('contact');
            $table->enum('commission_type', ['percentage', 'fixed']);
            $table->decimal('commission_rate', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Jobs Table (The Core Workflow)
        Schema::create('manufacturing_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->unique(); // E.g., JOB-0001
            
            // Job Details
            $table->foreignId('material_issue_id')->nullable()->constrained()->onDelete('set null'); // Link to original issue
            $table->string('style_ref')->nullable();
            $table->string('batch_ref')->nullable();
            
            // Initial Issue Data
            $table->decimal('issued_fabric_quantity', 10, 2);
            $table->text('accessories')->nullable(); // JSON or Text description
            
            // Status Flow
            $table->enum('status', [
                'FABRIC_ISSUED',
                'CUTTING_IN_PROGRESS',
                'CUTTING_COMPLETED',
                'LINE_ASSIGNED', 
                'LINE_IN_PROGRESS',
                'WASHING_OUT',
                'AFTER_WASH_RECEIVED',
                'PACKING_COMPLETED',
                'WAREHOUSE_RECEIVED',
                'SOLD'
            ])->default('FABRIC_ISSUED');
            
            // Tracking Progress
            $table->date('issue_date');
            $table->foreignId('created_by')->constrained('users'); // Who created the job
            
            // Cutting Data (Updated later)
            $table->decimal('fabric_used_qty', 10, 2)->nullable();
            $table->decimal('fabric_waste_qty', 10, 2)->nullable();
            $table->integer('total_cut_pieces')->nullable();
            $table->integer('cutting_reject_qty')->nullable();
            
            $table->timestamps();
        });

        // 4. Job Line Assignments (One job can be split to multiple lines)
        Schema::create('job_line_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturing_job_id')->constrained('manufacturing_jobs')->onDelete('cascade');
            $table->string('line_name'); // 'Line 1', 'Line 2'
            $table->integer('assigned_quantity');
            $table->date('dispatch_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_line_assignments');
        Schema::dropIfExists('manufacturing_jobs');
        Schema::dropIfExists('commission_agents');
        Schema::dropIfExists('employees');
    }
};
