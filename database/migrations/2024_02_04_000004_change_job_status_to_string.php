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
        Schema::table('manufacturing_jobs', function (Blueprint $table) {
            // Change enum to string to allow dynamic statuses like SENT_TO_CUTTING
            $table->string('status')->change(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting would require defining the enum again, skipping for now as string is compatible
    }
};
