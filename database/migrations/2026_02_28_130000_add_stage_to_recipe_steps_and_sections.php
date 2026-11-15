<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('production_stages')) {
            return;
        }
        Schema::table('recipe_steps', function (Blueprint $table) {
            if (!Schema::hasColumn('recipe_steps', 'production_stage_id')) {
                $table->foreignId('production_stage_id')->nullable()->after('product_recipe_id')->constrained('production_stages')->nullOnDelete();
            }
        });
        Schema::table('production_sections', function (Blueprint $table) {
            if (!Schema::hasColumn('production_sections', 'production_stage_id')) {
                $table->foreignId('production_stage_id')->nullable()->after('slug')->constrained('production_stages')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('recipe_steps', function (Blueprint $table) {
            $table->dropForeign(['production_stage_id']);
        });
        Schema::table('production_sections', function (Blueprint $table) {
            $table->dropForeign(['production_stage_id']);
        });
    }
};
