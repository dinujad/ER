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
        Schema::create('packing_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturing_job_id')->constrained('manufacturing_jobs')->onDelete('cascade');
            $table->enum('type', ['good', 'damage']); // Separate streams
            $table->decimal('quantity', 10, 2);
            $table->string('status')->default('packing'); // packing, sent_to_final_check, warehouse_received
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packing_batches');
    }
};
