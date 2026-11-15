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
        if (!Schema::hasColumn('manufacturing_jobs', 'product_id')) {
            Schema::table('manufacturing_jobs', function (Blueprint $table) {
                $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete()->after('job_number');
            });
            return;
        }
        $fkExists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'manufacturing_jobs')
            ->where('CONSTRAINT_NAME', 'manufacturing_jobs_product_id_foreign')
            ->exists();
        if (!$fkExists) {
            Schema::table('manufacturing_jobs', function (Blueprint $table) {
                $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('manufacturing_jobs', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });
    }
};
