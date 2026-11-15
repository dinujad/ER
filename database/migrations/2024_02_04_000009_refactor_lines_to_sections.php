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
        // Drop foreign key from employees first
        Schema::table('employees', function (Blueprint $table) {
            // Check if column exists before dropping (safe for re-runs)
            if (Schema::hasColumn('employees', 'production_line_id')) {
                $table->dropForeign(['production_line_id']);
                $table->dropColumn('production_line_id');
            }
        });

        // Drop old table
        Schema::dropIfExists('production_lines');

        // Create new Sections table
        Schema::create('production_sections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique(); // For easier querying (e.g. 'sewing', 'cutting')
            $table->enum('type', ['department', 'line'])->default('department');
            $table->foreignId('parent_id')->nullable()->constrained('production_sections')->onDelete('cascade');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Add new FK to employees
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('production_section_id')->nullable()->constrained('production_sections')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['production_section_id']);
            $table->dropColumn('production_section_id');
        });

        Schema::dropIfExists('production_sections');
    }
};
