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
        Schema::table('packing_batches', function (Blueprint $table) {
            $table->foreignId('washing_transfer_id')->nullable()->after('manufacturing_job_id')->constrained('washing_transfers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packing_batches', function (Blueprint $table) {
            $table->dropForeign(['washing_transfer_id']);
            $table->dropColumn('washing_transfer_id');
        });
    }
};
