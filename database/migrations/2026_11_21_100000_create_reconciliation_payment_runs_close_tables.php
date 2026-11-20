<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 7 — Xero / QuickBooks workflows:
     *  - Bank reconciliation workspace: saved import mapping per bank account, statement balances,
     *    one bank line matched to many ERP transactions, bank fees on net settlements.
     *  - Supplier payment runs (batch → approve → bank file → confirm / fail).
     *  - Month-end close checklist with sign-off.
     *  - Payment reminder delivery history.
     */
    public function up(): void
    {
        if (!Schema::hasTable('bank_import_mappings')) {
            Schema::create('bank_import_mappings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('chart_of_account_id')->unique();
                $table->json('column_map');
                $table->string('label', 120)->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('bank_statement_imports')) {
            Schema::table('bank_statement_imports', function (Blueprint $table) {
                foreach (['statement_from' => 'date', 'statement_to' => 'date'] as $col => $type) {
                    if (!Schema::hasColumn('bank_statement_imports', $col)) {
                        $table->date($col)->nullable();
                    }
                }
                foreach (['opening_balance', 'closing_balance'] as $col) {
                    if (!Schema::hasColumn('bank_statement_imports', $col)) {
                        $table->decimal($col, 20, 2)->nullable();
                    }
                }
            });
        }

        if (Schema::hasTable('bank_statement_lines')) {
            Schema::table('bank_statement_lines', function (Blueprint $table) {
                if (!Schema::hasColumn('bank_statement_lines', 'fee_amount')) {
                    $table->decimal('fee_amount', 20, 2)->nullable();
                }
                if (!Schema::hasColumn('bank_statement_lines', 'fee_transaction_number')) {
                    $table->string('fee_transaction_number', 50)->nullable();
                }
            });
        }

        if (!Schema::hasTable('bank_statement_line_matches')) {
            Schema::create('bank_statement_line_matches', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bank_statement_line_id')->index();
                $table->string('source_type', 40);
                $table->unsignedBigInteger('source_id');
                $table->decimal('amount', 20, 2);
                $table->unsignedBigInteger('matched_by')->nullable();
                $table->timestamps();
                $table->unique(['source_type', 'source_id'], 'bank_line_match_source_uq');
            });
        }

        if (!Schema::hasTable('acct_payment_runs')) {
            Schema::create('acct_payment_runs', function (Blueprint $table) {
                $table->id();
                $table->string('run_no', 30)->unique();
                $table->unsignedBigInteger('bank_account_id')->nullable();
                $table->date('payment_date');
                $table->string('status', 20)->default('draft')->index(); // draft|approved|exported|completed|cancelled
                $table->decimal('total', 20, 2)->default(0);
                $table->string('notes', 255)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('exported_at')->nullable();
                $table->string('export_format', 30)->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('acct_payment_run_items')) {
            Schema::create('acct_payment_run_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('payment_run_id')->index();
                $table->unsignedBigInteger('purchase_id')->index();
                $table->unsignedBigInteger('supplier_id')->nullable();
                $table->decimal('amount', 20, 2);
                $table->string('status', 20)->default('pending'); // pending|paid|failed|removed
                $table->string('failure_reason', 255)->nullable();
                $table->unsignedBigInteger('purchase_payment_id')->nullable()->unique();
                $table->timestamps();
                $table->unique(['payment_run_id', 'purchase_id'], 'pay_run_item_bill_uq');
            });
        }

        // Supplier bank details for payment-run bank files.
        if (Schema::hasTable('suppliers')) {
            Schema::table('suppliers', function (Blueprint $table) {
                foreach (['bank_account_name' => 120, 'bank_account_no' => 40, 'bank_name' => 80, 'bank_code' => 10, 'branch_code' => 10] as $col => $len) {
                    if (!Schema::hasColumn('suppliers', $col)) {
                        $table->string($col, $len)->nullable();
                    }
                }
            });
        }

        if (!Schema::hasTable('acct_period_close_reviews')) {
            Schema::create('acct_period_close_reviews', function (Blueprint $table) {
                $table->id();
                $table->string('period_key', 7)->unique(); // YYYY-MM
                $table->json('checks')->nullable();
                $table->string('status', 20)->default('in_review'); // in_review|signed_off|locked|reopened
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('signed_off_by')->nullable();
                $table->timestamp('signed_off_at')->nullable();
                $table->unsignedBigInteger('locked_by')->nullable();
                $table->timestamp('locked_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('acct_reminder_logs')) {
            Schema::create('acct_reminder_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id')->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->string('channel', 20)->default('sms');
                $table->string('recipient', 60)->nullable();
                $table->text('message')->nullable();
                $table->string('status', 20); // sent|failed|skipped
                $table->string('error', 255)->nullable();
                $table->string('trigger', 20)->default('manual'); // manual|schedule
                $table->unsignedSmallInteger('days_overdue')->nullable();
                $table->unsignedBigInteger('sent_by')->nullable();
                $table->timestamp('created_at')->nullable()->index();
            });
        }

        if (Schema::hasTable('chart_of_accounts') && !DB::table('chart_of_accounts')->where('gl_code', '6050')->exists()) {
            $payload = ['gl_code' => '6050', 'name' => 'Bank Charges', 'account_type' => 'expense', 'active' => 1];
            if (Schema::hasColumn('chart_of_accounts', 'normal_balance')) {
                $payload['normal_balance'] = 'debit';
            }
            if (Schema::hasColumn('chart_of_accounts', 'business_id')) {
                $payload['business_id'] = 0;
            }
            if (Schema::hasColumn('chart_of_accounts', 'created_at')) {
                $payload['created_at'] = now();
                $payload['updated_at'] = now();
            }
            DB::table('chart_of_accounts')->insert($payload);
        }
    }

    public function down(): void
    {
        foreach (['acct_reminder_logs', 'acct_period_close_reviews', 'acct_payment_run_items', 'acct_payment_runs', 'bank_statement_line_matches', 'bank_import_mappings'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
