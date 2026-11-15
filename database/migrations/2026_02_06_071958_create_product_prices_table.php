<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('price_group_id')->constrained('price_groups')->onDelete('cascade');
            $table->decimal('price', 12, 2);
            $table->softDeletes();
            $table->timestamps();
            
            // Ensure unique combination
            $table->unique(['product_id', 'price_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
