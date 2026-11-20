<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 6.4 — credit notes (Xero / QuickBooks).
     * type = return      → document for a sale return (accounting already posted by the return)
     * type = adjustment  → price / goodwill credit without goods: posts Dr Sales returns, SSCL, VAT / Cr AR
     */
    public function up(): void
    {
        if (Schema::hasTable('credit_notes')) {
            return;
        }

        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('credit_note_number', 40)->unique();
            $table->unsignedBigInteger('order_id')->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('sale_return_id')->nullable()->index();
            $table->string('type', 20)->default('adjustment');
            $table->date('credit_note_date');
            $table->string('reason', 255)->nullable();
            $table->decimal('goods_amount', 14, 2)->default(0);
            $table->decimal('sscl_amount', 14, 2)->default(0);
            $table->decimal('vat_amount', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->string('status', 20)->default('issued');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->unsignedBigInteger('voided_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
    }
};
