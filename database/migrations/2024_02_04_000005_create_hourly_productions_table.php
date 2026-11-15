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
        Schema::create('hourly_productions', function (Blueprint $table) {
            $table->id();
            
            // Link to the Job
            $table->foreignId('manufacturing_job_id')->constrained('manufacturing_jobs')->onDelete('cascade');
            
            // Link to the Employee (Who produced the items)
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            
            // Which line this production is for (e.g., Line 1, Line 2)
            $table->string('line_name'); 
            
            // Date of production
            $table->date('production_date');
            
            // Hour of the day (e.g., 9 for 9:00-10:00)
            $table->integer('hour');
            
            // Quantity produced in this hour
            $table->integer('quantity');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hourly_productions');
    }
};
