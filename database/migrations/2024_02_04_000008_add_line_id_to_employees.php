<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('employees', 'production_line_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->foreignId('production_line_id')->nullable()->constrained('production_lines')->onDelete('set null');
            });
            return;
        }
        $fkExists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'employees')
            ->where('CONSTRAINT_NAME', 'employees_production_line_id_foreign')
            ->exists();
        if (!$fkExists) {
            Schema::table('employees', function (Blueprint $table) {
                $table->foreign('production_line_id')->references('id')->on('production_lines')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'production_line_id')) {
                $table->dropForeign(['production_line_id']);
                $table->dropColumn('production_line_id');
            }
        });
    }
};
