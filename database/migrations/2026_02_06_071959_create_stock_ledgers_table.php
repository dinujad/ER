<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->enum('transaction_type', ['opening', 'purchase', 'sale', 'adjustment', 'transfer_in', 'transfer_out'])->index();
            $table->decimal('quantity', 12, 2); // Positive for IN, Negative for OUT
            $table->string('reference_type')->nullable()->index(); // Polymorphic - e.g., "App\Models\Sale"
            $table->unsignedBigInteger('reference_id')->nullable()->index(); // Polymorphic ID
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Composite index for performance
            $table->index(['product_id', 'transaction_type']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_ledgers');
    }
};
