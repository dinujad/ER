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
        // 1. Materials (Raw Fabrics, Accessories)
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // 'fabric', 'accessory'
            $table->decimal('quantity', 10, 2)->default(0); // Current stock
            $table->string('unit'); // 'yards', 'pieces'
            $table->timestamps();
        });

        // 2. Material Issues (Issuing to Cutting)
        Schema::create('material_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity_issued', 10, 2);
            $table->string('issued_to'); // Section or Person name
            $table->timestamps();
        });

        // 3. Cutting Batches (Output from Cutting Section)
        Schema::create('cutting_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_issue_id')->nullable()->constrained()->onDelete('set null'); // Optional link to issue
            $table->integer('pieces_cut');
            $table->string('style_no')->nullable(); // Optional: Style Number of the garment
            $table->timestamps();
        });

        // 4. Production Logs (Line I and Line II Output)
        Schema::create('production_logs', function (Blueprint $table) {
            $table->id();
            $table->string('line_name'); // 'Line I', 'Line II'
            $table->date('production_date');
            $table->integer('daily_output'); // Quantity produced
            $table->timestamps();
        });

        // 5. Washing Transfers (Gate Pass)
        Schema::create('washing_transfers', function (Blueprint $table) {
            $table->id();
            $table->integer('quantity_sent');
            $table->string('sent_from'); // e.g., 'Line I'
            $table->string('batch_no')->nullable(); // For tracking
            $table->enum('status', ['pending', 'returned'])->default('pending');
            $table->integer('quantity_returned')->nullable(); // Editable later
            $table->timestamps();
        });

        // 6. Quality Control (Finished vs Damaged)
        Schema::create('qc_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('washing_transfer_id')->nullable()->constrained()->onDelete('set null'); // Link to washing batch
            $table->integer('total_checked');
            $table->integer('finished_good_qty');
            $table->integer('damaged_qty');
            $table->timestamps();
        });

        // 7. Sales (Retail, Online, Wholesale)
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->enum('channel', ['retail', 'online', 'wholesale']);
            $table->string('product_name');
            $table->integer('quantity_sold');
            $table->decimal('total_amount', 10, 2);
            $table->date('sale_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
        Schema::dropIfExists('qc_checks');
        Schema::dropIfExists('washing_transfers');
        Schema::dropIfExists('production_logs');
        Schema::dropIfExists('cutting_batches');
        Schema::dropIfExists('material_issues');
        Schema::dropIfExists('materials');
    }
};
