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
        Schema::table('washing_transfers', function (Blueprint $table) {
            $table->foreignId('manufacturing_job_id')->nullable()->constrained('manufacturing_jobs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('washing_transfers', function (Blueprint $table) {
            $table->dropForeign(['manufacturing_job_id']);
            $table->dropColumn('manufacturing_job_id');
        });
    }
};
