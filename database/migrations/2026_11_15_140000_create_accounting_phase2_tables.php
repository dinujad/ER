<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 accounting foundation — additive only, no destructive drops of financial history.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('accounting_periods')) {
            Schema::create('accounting_periods', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->default(0)->index();
                $table->string('name', 120);
                $table->date('starts_on');
                $table->date('ends_on');
                $table->string('status', 20)->default('open')->index(); // open|closed
                $table->timestamp('closed_at')->nullable();
                $table->unsignedBigInteger('closed_by')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'starts_on', 'ends_on'], 'acct_periods_biz_range_uq');
            });
        }

        if (! Schema::hasTable('accounting_manual_journals')) {
            Schema::create('accounting_manual_journals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->default(0)->index();
                $table->string('journal_number', 40)->unique();
                $table->date('journal_date');
                $table->date('posting_date')->nullable();
                $table->string('status', 20)->default('draft')->index(); // draft|submitted|approved|posted|rejected|reversed
                $table->string('description', 500)->nullable();
                $table->string('reference', 120)->nullable();
                $table->string('transaction_number', 50)->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('submitted_by')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->unsignedBigInteger('posted_by')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->string('rejection_reason', 500)->nullable();
                $table->string('reversed_by_txn', 50)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('accounting_manual_journal_lines')) {
            Schema::create('accounting_manual_journal_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('manual_journal_id')->constrained('accounting_manual_journals')->cascadeOnDelete();
                $table->unsignedBigInteger('chart_of_account_id')->index();
                $table->decimal('debit', 20, 4)->default(0);
                $table->decimal('credit', 20, 4)->default(0);
                $table->string('memo', 500)->nullable();
                $table->unsignedInteger('line_no')->default(1);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('bank_statement_imports')) {
            Schema::create('bank_statement_imports', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->default(0)->index();
                $table->unsignedBigInteger('chart_of_account_id')->index();
                $table->string('original_filename', 255)->nullable();
                $table->string('status', 20)->default('preview')->index(); // preview|imported|cancelled
                $table->unsignedBigInteger('imported_by')->nullable();
                $table->unsignedInteger('row_count')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('bank_statement_lines')) {
            Schema::create('bank_statement_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bank_statement_import_id')->constrained('bank_statement_imports')->cascadeOnDelete();
                $table->date('txn_date')->nullable();
                $table->string('description', 500)->nullable();
                $table->string('reference', 120)->nullable();
                $table->decimal('debit', 20, 4)->default(0);
                $table->decimal('credit', 20, 4)->default(0);
                $table->decimal('amount', 20, 4)->default(0);
                $table->string('fingerprint', 64)->index();
                $table->string('match_status', 20)->default('unmatched')->index(); // unmatched|matched|ignored
                $table->string('matched_source_type')->nullable();
                $table->unsignedBigInteger('matched_source_id')->nullable();
                $table->timestamps();
                $table->unique(['bank_statement_import_id', 'fingerprint'], 'bank_stmt_line_fp_uq');
            });
        }

        if (! Schema::hasTable('accounting_recurring_templates')) {
            Schema::create('accounting_recurring_templates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->default(0)->index();
                $table->string('name', 160);
                $table->string('frequency', 20); // daily|weekly|monthly|quarterly|yearly
                $table->date('starts_on');
                $table->date('ends_on')->nullable();
                $table->date('next_run_on')->nullable()->index();
                $table->string('status', 20)->default('active')->index(); // active|paused|ended
                $table->string('execution_mode', 30)->default('draft'); // draft|draft_notify|auto_post
                $table->json('payload'); // journal lines / meta
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('accounting_recurring_runs')) {
            Schema::create('accounting_recurring_runs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('template_id')->constrained('accounting_recurring_templates')->cascadeOnDelete();
                $table->date('occurrence_date');
                $table->string('status', 20)->default('created');
                $table->unsignedBigInteger('manual_journal_id')->nullable();
                $table->string('message', 500)->nullable();
                $table->timestamps();
                $table->unique(['template_id', 'occurrence_date'], 'acct_recurring_occ_uq');
            });
        }

        if (! Schema::hasTable('accounting_audit_events')) {
            Schema::create('accounting_audit_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->default(0)->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('action', 80)->index();
                $table->string('subject_type', 120)->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->json('before_state')->nullable();
                $table->json('after_state')->nullable();
                $table->string('reason', 500)->nullable();
                $table->timestamps();
                $table->index(['subject_type', 'subject_id']);
            });
        }

        if (! Schema::hasTable('customer_payment_allocations')) {
            Schema::create('customer_payment_allocations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('payment_id')->index();
                $table->unsignedBigInteger('order_id')->index();
                $table->decimal('amount', 20, 4);
                $table->unsignedBigInteger('allocated_by')->nullable();
                $table->timestamps();
                $table->unique(['payment_id', 'order_id'], 'cust_pay_alloc_uq');
            });
        }

        if (! Schema::hasTable('supplier_payment_allocations')) {
            Schema::create('supplier_payment_allocations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('purchase_payment_id')->index();
                $table->unsignedBigInteger('purchase_id')->index();
                $table->decimal('amount', 20, 4);
                $table->unsignedBigInteger('allocated_by')->nullable();
                $table->timestamps();
                $table->unique(['purchase_payment_id', 'purchase_id'], 'supp_pay_alloc_uq');
            });
        }

        if (Schema::hasTable('chart_of_accounts') && ! Schema::hasColumn('chart_of_accounts', 'normal_balance')) {
            Schema::table('chart_of_accounts', function (Blueprint $table) {
                $table->string('normal_balance', 10)->nullable()->after('account_type'); // debit|credit
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payment_allocations');
        Schema::dropIfExists('customer_payment_allocations');
        Schema::dropIfExists('accounting_audit_events');
        Schema::dropIfExists('accounting_recurring_runs');
        Schema::dropIfExists('accounting_recurring_templates');
        Schema::dropIfExists('bank_statement_lines');
        Schema::dropIfExists('bank_statement_imports');
        Schema::dropIfExists('accounting_manual_journal_lines');
        Schema::dropIfExists('accounting_manual_journals');
        Schema::dropIfExists('accounting_periods');

        if (Schema::hasTable('chart_of_accounts') && Schema::hasColumn('chart_of_accounts', 'normal_balance')) {
            Schema::table('chart_of_accounts', function (Blueprint $table) {
                $table->dropColumn('normal_balance');
            });
        }
    }
};
