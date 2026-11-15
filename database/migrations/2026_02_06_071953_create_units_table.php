<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Piece", "Kilogram", "Liter"
            $table->string('short_name'); // e.g., "Pcs", "Kg", "L"
            $table->foreignId('base_unit_id')->nullable()->constrained('units')->onDelete('set null');
            $table->decimal('conversion_factor', 10, 4)->nullable(); // e.g., 1 Box = 12 Pieces
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
