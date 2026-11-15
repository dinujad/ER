<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Allow multiple jobs per vehicle per day (remove unique). */
    public function up(): void
    {
        Schema::table('vehicle_daily_runs', function (Blueprint $table) {
            $table->dropForeign(['vehicle_id']);
            $table->dropForeign(['driver_id']);
        });
        Schema::table('vehicle_daily_runs', function (Blueprint $table) {
            $table->dropUnique(['vehicle_id', 'run_date']);
            $table->index(['vehicle_id', 'run_date']);
        });
        Schema::table('vehicle_daily_runs', function (Blueprint $table) {
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->cascadeOnDelete();
            $table->foreign('driver_id')->references('id')->on('drivers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_daily_runs', function (Blueprint $table) {
            $table->dropForeign(['vehicle_id']);
            $table->dropForeign(['driver_id']);
        });
        Schema::table('vehicle_daily_runs', function (Blueprint $table) {
            $table->dropIndex(['vehicle_id', 'run_date']);
            $table->unique(['vehicle_id', 'run_date']);
        });
        Schema::table('vehicle_daily_runs', function (Blueprint $table) {
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->cascadeOnDelete();
            $table->foreign('driver_id')->references('id')->on('drivers')->cascadeOnDelete();
        });
    }
};
