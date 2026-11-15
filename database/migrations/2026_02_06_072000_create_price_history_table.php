<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('old_price', 12, 2);
            $table->decimal('new_price', 12, 2);
            $table->enum('adjustment_type', ['fixed', 'percentage']);
            $table->decimal('adjustment_value', 10, 2);
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_history');
    }
};
