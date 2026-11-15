<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('business')) {
            Schema::create('business', function (Blueprint $table) {
                $table->unsignedInteger('id')->primary();
                $table->unsignedTinyInteger('fy_start_month')->default(1);
                $table->timestamps();
            });

            DB::table('business')->insert([
                'id'             => 1,
                'fy_start_month' => 1,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        if (!Schema::hasTable('business_locations')) {
            Schema::create('business_locations', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->default(1)->index();
                $table->string('name')->default('Main');
                $table->timestamps();
            });

            DB::table('business_locations')->insert([
                'business_id' => 1,
                'name'        => 'Main',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('business_locations');
        Schema::dropIfExists('business');
    }
};
