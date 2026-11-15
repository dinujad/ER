<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_daily_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->date('run_date'); // one run per vehicle per day
            $table->unique(['vehicle_id', 'run_date']);
            $table->decimal('start_km', 12, 2)->nullable();
            $table->decimal('end_km', 12, 2)->nullable();
            $table->string('start_route')->nullable(); // from (place)
            $table->string('end_route')->nullable();   // to (place)
            $table->text('google_map_link')->nullable();
            $table->decimal('distance_km', 10, 2)->nullable();
            $table->string('fuel_received')->nullable(); // e.g. "20L" or notes
            $table->string('fuel_receipt_path')->nullable(); // upload
            $table->string('meter_photo_path')->nullable(); // end of day odometer photo
            $table->string('slip_path')->nullable(); // slip document
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_daily_runs');
    }
};
