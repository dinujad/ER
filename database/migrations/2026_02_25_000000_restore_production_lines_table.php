<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Restores production_lines so JobController and Lines module work alongside production_sections.
     */
    public function up(): void
    {
        if (Schema::hasTable('production_lines')) {
            return;
        }

        Schema::create('production_lines', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        if (!Schema::hasColumn('employees', 'production_line_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->foreignId('production_line_id')->nullable()->constrained('production_lines')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('employees', 'production_line_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropForeign(['production_line_id']);
                $table->dropColumn('production_line_id');
            });
        }
        Schema::dropIfExists('production_lines');
    }
};
