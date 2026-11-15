<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hourly_production_daily_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturing_job_id')->constrained('manufacturing_jobs')->cascadeOnDelete();
            $table->date('production_date');
            $table->string('line_name');
            $table->unsignedInteger('target_quantity');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['manufacturing_job_id', 'production_date', 'line_name'], 'hourly_daily_target_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hourly_production_daily_targets');
    }
};
