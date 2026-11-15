<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_daily_runs', function (Blueprint $table) {
            $table->string('start_meter_photo_path')->nullable()->after('run_date');
            $table->string('status')->default('started')->after('notes'); // started, ended
        });
        \DB::table('vehicle_daily_runs')->whereNotNull('meter_photo_path')->update(['status' => 'ended']);
    }

    public function down(): void
    {
        Schema::table('vehicle_daily_runs', function (Blueprint $table) {
            $table->dropColumn(['start_meter_photo_path', 'status']);
        });
    }
};
