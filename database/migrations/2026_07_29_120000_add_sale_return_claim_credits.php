<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_returns', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_returns', 'claimable_amount')) {
                $table->decimal('claimable_amount', 12, 2)->default(0)->after('total');
            }
            if (!Schema::hasColumn('sale_returns', 'claimed_amount')) {
                $table->decimal('claimed_amount', 12, 2)->default(0)->after('claimable_amount');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'claim_credit_amount')) {
                $table->decimal('claim_credit_amount', 12, 2)->default(0)->after('total_amount');
            }
        });

        Schema::create('sale_return_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete(); // target invoice/order
            $table->decimal('amount', 12, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_return_claims');

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'claim_credit_amount')) {
                $table->dropColumn('claim_credit_amount');
            }
        });

        Schema::table('sale_returns', function (Blueprint $table) {
            if (Schema::hasColumn('sale_returns', 'claimed_amount')) {
                $table->dropColumn('claimed_amount');
            }
            if (Schema::hasColumn('sale_returns', 'claimable_amount')) {
                $table->dropColumn('claimable_amount');
            }
        });
    }
};
