<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'quantity_returned')) {
                $table->decimal('quantity_returned', 12, 2)->default(0)->after('quantity');
            }
        });

        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_no')->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->date('return_date');
            $table->string('channel', 20)->default('retail')->index();
            $table->string('status')->default('confirmed');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_variation_id')->nullable()->constrained('product_variations')->nullOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
        });

        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'entry_type')) {
                $table->string('entry_type')->default('payment')->after('order_id');
            }
            if (!Schema::hasColumn('payments', 'sale_return_id')) {
                $table->foreignId('sale_return_id')->nullable()->after('entry_type')->constrained('sale_returns')->nullOnDelete();
            }
        });

        // Extend stock_ledgers.transaction_type enum with sale_return (MySQL).
        try {
            DB::statement("ALTER TABLE stock_ledgers MODIFY COLUMN transaction_type ENUM('opening','purchase','sale','adjustment','transfer_in','transfer_out','sale_return') NOT NULL");
        } catch (\Throwable $e) {
            // SQLite / non-MySQL: column may already be a string; ignore.
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'sale_return_id')) {
                $table->dropConstrainedForeignId('sale_return_id');
            }
            if (Schema::hasColumn('payments', 'entry_type')) {
                $table->dropColumn('entry_type');
            }
        });

        Schema::dropIfExists('sale_return_items');
        Schema::dropIfExists('sale_returns');

        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'quantity_returned')) {
                $table->dropColumn('quantity_returned');
            }
        });

        try {
            DB::statement("ALTER TABLE stock_ledgers MODIFY COLUMN transaction_type ENUM('opening','purchase','sale','adjustment','transfer_in','transfer_out') NOT NULL");
        } catch (\Throwable $e) {
            // ignore
        }
    }
};
