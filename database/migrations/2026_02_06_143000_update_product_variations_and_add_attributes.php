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
        // 1. Create Attributes Tables
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. Size, Color
            $table->string('slug')->nullable();
            $table->integer('business_id')->nullable(); // Multi-tenancy prep
            $table->timestamps();
        });

        // 2. Enhance Product Variations Table (Flat structure)
        // We drop the constraint first if exists, because we might change logic
        Schema::table('product_variations', function (Blueprint $table) {
            $table->dropForeign(['variation_product_id']); // Drop link to 'products' table
            $table->dropColumn('variation_product_id'); 
            
            // Add actual fields
            $table->string('sub_sku')->nullable()->after('product_id');
            $table->decimal('default_purchase_price', 22, 4)->default(0)->after('sub_sku');
            $table->decimal('default_sell_price', 22, 4)->default(0)->after('default_purchase_price');
            $table->json('combo_variations')->nullable(); // Store { "Color": "Red", "Size": "M" }
            $table->string('barcode_type')->default('C128');
            $table->string('barcode')->nullable();
        });

        // 3. Update Stock Ledger to support Variation ID
        Schema::table('stock_ledgers', function (Blueprint $table) {
            $table->unsignedBigInteger('product_variation_id')->nullable()->after('product_id');
            // Assuming we still keep product_id as the parent
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attributes');
        
        Schema::table('stock_ledgers', function (Blueprint $table) {
            $table->dropColumn('product_variation_id');
        });

        // Reverting product_variations is complex, assuming one-way forward for MVP
    }
};
