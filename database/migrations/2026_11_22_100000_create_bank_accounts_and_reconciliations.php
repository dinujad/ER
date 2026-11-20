<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bank accounts (Xero "Bank accounts" / QuickBooks "Banking"):
     *  bank_accounts            — one row per real bank / card account, linked to its ledger account
     *  bank_reconciliations     — statement-to-statement reconciliations (ending date + ending balance, difference 0)
     *  bank_cleared_entries     — ledger lines cleared by a reconciliation (each line cleared once)
     */
    public function up(): void
    {
        if (!Schema::hasTable('bank_accounts')) {
            Schema::create('bank_accounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('chart_of_account_id')->unique();
                $table->string('bank_name', 80);
                $table->string('display_name', 120);
                $table->string('account_type', 20)->default('current'); // current|savings|credit_card
                $table->string('account_number', 40)->nullable();
                $table->string('currency', 3)->default('LKR');
                $table->decimal('opening_balance', 20, 2)->default(0);
                $table->date('opening_balance_date')->nullable();
                $table->string('opening_transaction_number', 50)->nullable();
                $table->string('statement_source', 20)->default('manual'); // manual|feed
                $table->string('feed_status', 20)->nullable();
                $table->string('status', 20)->default('active')->index(); // active|archived
                $table->timestamp('archived_at')->nullable();
                $table->unsignedBigInteger('archived_by')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('bank_reconciliations')) {
            Schema::create('bank_reconciliations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bank_account_id')->index();
                $table->date('statement_date');
                $table->decimal('beginning_balance', 20, 2)->default(0);
                $table->decimal('statement_balance', 20, 2);
                $table->decimal('cleared_total', 20, 2)->default(0);
                $table->unsignedInteger('cleared_count')->default(0);
                $table->string('status', 20)->default('completed'); // completed|undone
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('completed_by')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->unsignedBigInteger('undone_by')->nullable();
                $table->timestamp('undone_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('bank_cleared_entries')) {
            Schema::create('bank_cleared_entries', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bank_reconciliation_id')->index();
                $table->unsignedBigInteger('journal_entry_id')->unique();
                $table->decimal('amount', 20, 2);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_cleared_entries');
        Schema::dropIfExists('bank_reconciliations');
        Schema::dropIfExists('bank_accounts');
    }
};
