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
        if (Schema::hasTable('product_recipes')) {
            return;
        }
        Schema::create('product_recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name')->default('Standard');
            $table->text('instructions')->nullable();
            $table->decimal('total_output_quantity', 10, 2)->nullable()->default(1);
            $table->string('output_unit')->default('pcs'); // e.g. pieces, kg
            $table->decimal('wastage_percent', 5, 2)->default(0);
            $table->decimal('production_cost', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_recipes');
    }
};
