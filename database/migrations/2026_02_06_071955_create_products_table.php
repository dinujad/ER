<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('sku')->nullable()->unique();
            $table->string('barcode')->nullable()->unique();
            $table->foreignId('category_id')->constrained('categories')->onDelete('restrict');
            $table->foreignId('brand_id')->nullable()->constrained('brands')->onDelete('set null');
            $table->foreignId('unit_id')->constrained('units')->onDelete('restrict');
            $table->foreignId('warranty_id')->nullable()->constrained('warranties')->onDelete('set null');
            $table->enum('product_type', ['simple', 'variable'])->default('simple')->index();
            $table->foreignId('parent_product_id')->nullable()->constrained('products')->onDelete('cascade');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0); // Percentage
            $table->integer('alert_quantity')->default(0); // Low stock alert
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
            
            // Indexes for performance
            $table->index('parent_product_id');
            $table->index(['category_id', 'brand_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
