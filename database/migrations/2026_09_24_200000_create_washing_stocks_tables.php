<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('washing_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturing_job_id')->unique()->constrained('manufacturing_jobs')->cascadeOnDelete();
            $table->unsignedInteger('quantity_received')->default(0);
            $table->timestamps();
        });

        Schema::create('washing_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturing_job_id')->constrained('manufacturing_jobs')->cascadeOnDelete();
            $table->foreignId('washing_stock_id')->constrained('washing_stocks')->cascadeOnDelete();
            $table->string('type', 16);
            $table->unsignedInteger('quantity');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->unsignedBigInteger('washing_transfer_id')->nullable();
            $table->timestamps();
            $table->index(['manufacturing_job_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('washing_stock_movements');
        Schema::dropIfExists('washing_stocks');
    }
};
