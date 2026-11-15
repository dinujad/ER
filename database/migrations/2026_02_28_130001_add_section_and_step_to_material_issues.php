<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_issues', function (Blueprint $table) {
            if (!Schema::hasColumn('material_issues', 'production_section_id')) {
                $table->foreignId('production_section_id')->nullable()->after('manufacturing_job_id')->constrained('production_sections')->nullOnDelete();
            }
            if (!Schema::hasColumn('material_issues', 'recipe_step_id')) {
                $table->foreignId('recipe_step_id')->nullable()->after('production_section_id')->constrained('recipe_steps')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('material_issues', function (Blueprint $table) {
            if (Schema::hasColumn('material_issues', 'production_section_id')) {
                $table->dropForeign(['production_section_id']);
            }
            if (Schema::hasColumn('material_issues', 'recipe_step_id')) {
                $table->dropForeign(['recipe_step_id']);
            }
        });
    }
};
