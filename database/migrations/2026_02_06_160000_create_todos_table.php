<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('todos')) {
            Schema::create('todos', function (Blueprint $table) {
                $table->id();
                $table->integer('business_id')->default(1);
                $table->string('task_id')->unique(); // TD-0001
                $table->string('title');
                $table->text('description')->nullable();
                
                $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
                $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
                
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->decimal('estimated_hours', 8, 2)->nullable();
                
                $table->unsignedBigInteger('assigned_by');
                $table->unsignedBigInteger('assigned_to')->nullable();
                
                // Audit
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                // Indexes for DataTable sorting/filtering
                $table->index('business_id');
                $table->index('status');
                $table->index('priority');
                $table->index('assigned_to');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('todos');
    }
};
