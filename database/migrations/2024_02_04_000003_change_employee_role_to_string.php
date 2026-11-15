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
        Schema::table('employees', function (Blueprint $table) {
            // Change enum to string to allow dynamic roles
            $table->string('role')->change(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting not easily possible due to potential data mismatch, 
        // but we can try setting it back to text or keeping it string.
    }
};
