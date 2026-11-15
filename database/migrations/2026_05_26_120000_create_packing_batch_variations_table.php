<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packing_batch_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('packing_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variation_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['packing_batch_id', 'product_variation_id'], 'pbv_batch_variation_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packing_batch_variations');
    }
};
