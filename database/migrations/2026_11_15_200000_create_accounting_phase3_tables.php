<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 — Fixed Assets, Depreciation, Loans, Cost Centers, Advanced Budget (additive only).
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->seedPhase3GlAccounts();
        $this->createFixedAssetTables();
        $this->createLoanTables();
        $this->createCostCenterTables();
        $this->createAdvancedBudgetTables();
        $this->extendJournalAndBudgetTables();
        $this->seedAssetCategories();
    }

    public function down(): void
    {
        Schema::dropIfExists('acct_budget_version_lines');
        Schema::dropIfExists('acct_budget_versions');
        Schema::dropIfExists('acct_cost_allocation_lines');
        Schema::dropIfExists('acct_cost_allocations');
        Schema::dropIfExists('acct_cost_centers');
        Schema::dropIfExists('acct_loan_payments');
        Schema::dropIfExists('acct_loan_schedule_lines');
        Schema::dropIfExists('acct_loans');
        Schema::dropIfExists('fa_disposals');
        Schema::dropIfExists('fa_depreciation_runs');
        Schema::dropIfExists('fa_depreciation_schedules');
        Schema::dropIfExists('fa_asset_events');
        Schema::dropIfExists('fa_assets');
        Schema::dropIfExists('fa_asset_categories');

        if (Schema::hasTable('journal_entries')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                foreach (['cost_center_id', 'job_id'] as $col) {
                    if (Schema::hasColumn('journal_entries', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('budgets')) {
            Schema::table('budgets', function (Blueprint $table) {
                foreach (['status', 'version_no', 'active_version_id'] as $col) {
                    if (Schema::hasColumn('budgets', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }

    private function seedPhase3GlAccounts(): void
    {
        if (! Schema::hasTable('chart_of_accounts')) {
            return;
        }

        $accounts = [
            ['gl_code' => '1400', 'name' => 'Fixed Assets', 'account_type' => 'asset', 'normal_balance' => 'debit'],
            ['gl_code' => '1450', 'name' => 'Accumulated Depreciation', 'account_type' => 'asset', 'normal_balance' => 'credit'],
            ['gl_code' => '1500', 'name' => 'Plant & Equipment', 'account_type' => 'asset', 'normal_balance' => 'debit'],
            ['gl_code' => '2200', 'name' => 'Loans Payable', 'account_type' => 'liability', 'normal_balance' => 'credit'],
            ['gl_code' => '2210', 'name' => 'Interest Payable', 'account_type' => 'liability', 'normal_balance' => 'credit'],
            ['gl_code' => '4500', 'name' => 'Gain on Asset Disposal', 'account_type' => 'income', 'normal_balance' => 'credit'],
            ['gl_code' => '6400', 'name' => 'Depreciation Expense', 'account_type' => 'expense', 'normal_balance' => 'debit'],
            ['gl_code' => '6410', 'name' => 'Interest Expense', 'account_type' => 'expense', 'normal_balance' => 'debit'],
            ['gl_code' => '6500', 'name' => 'Loss on Asset Disposal', 'account_type' => 'expense', 'normal_balance' => 'debit'],
        ];

        foreach ($accounts as $acc) {
            DB::table('chart_of_accounts')->updateOrInsert(
                ['gl_code' => $acc['gl_code'], 'business_id' => 0],
                array_merge($acc, [
                    'business_id' => 0,
                    'active' => 1,
                    'allow_manual' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    private function createFixedAssetTables(): void
    {
        if (! Schema::hasTable('fa_asset_categories')) {
            Schema::create('fa_asset_categories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->default(0)->index();
                $table->string('code', 30);
                $table->string('name', 160);
                $table->string('description', 500)->nullable();
                $table->unsignedBigInteger('asset_account_id')->nullable();
                $table->unsignedBigInteger('accumulated_depreciation_account_id')->nullable();
                $table->unsignedBigInteger('depreciation_expense_account_id')->nullable();
                $table->unsignedBigInteger('disposal_gain_account_id')->nullable();
                $table->unsignedBigInteger('disposal_loss_account_id')->nullable();
                $table->string('default_depreciation_method', 30)->default('straight_line');
                $table->unsignedInteger('default_useful_life_months')->default(60);
                $table->decimal('default_salvage_value', 20, 4)->default(0);
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->unique(['business_id', 'code'], 'fa_cat_biz_code_uq');
            });
        }

        if (! Schema::hasTable('fa_assets')) {
            Schema::create('fa_assets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->default(0)->index();
                $table->foreignId('category_id')->constrained('fa_asset_categories');
                $table->string('asset_code', 40)->index();
                $table->string('name', 200);
                $table->text('description')->nullable();
                $table->date('purchase_date')->nullable();
                $table->date('acquisition_date')->nullable();
                $table->date('in_service_date')->nullable();
                $table->unsignedBigInteger('supplier_id')->nullable();
                $table->string('purchase_reference', 120)->nullable();
                $table->decimal('asset_cost', 20, 4)->default(0);
                $table->decimal('capitalized_costs', 20, 4)->default(0);
                $table->decimal('salvage_value', 20, 4)->default(0);
                $table->unsignedInteger('useful_life_months')->default(60);
                $table->string('depreciation_method', 30)->default('straight_line');
                $table->decimal('accumulated_depreciation', 20, 4)->default(0);
                $table->decimal('net_book_value', 20, 4)->default(0);
                $table->string('status', 30)->default('draft')->index();
                $table->unsignedBigInteger('location_id')->nullable();
                $table->unsignedBigInteger('cost_center_id')->nullable();
                $table->string('serial_number', 120)->nullable();
                $table->string('warranty_info', 255)->nullable();
                $table->string('capitalization_txn', 50)->nullable();
                $table->date('disposal_date')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('capitalized_at')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'asset_code'], 'fa_asset_biz_code_uq');
            });
        }

        if (! Schema::hasTable('fa_asset_events')) {
            Schema::create('fa_asset_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('asset_id')->constrained('fa_assets')->cascadeOnDelete();
                $table->string('event_type', 60);
                $table->date('event_date');
                $table->string('description', 500)->nullable();
                $table->json('before_state')->nullable();
                $table->json('after_state')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('fa_depreciation_schedules')) {
            Schema::create('fa_depreciation_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('asset_id')->constrained('fa_assets')->cascadeOnDelete();
                $table->unsignedSmallInteger('period_year');
                $table->unsignedTinyInteger('period_month');
                $table->date('period_end');
                $table->decimal('scheduled_amount', 20, 4)->default(0);
                $table->decimal('accumulated_after', 20, 4)->default(0);
                $table->decimal('nbv_after', 20, 4)->default(0);
                $table->string('status', 20)->default('scheduled')->index();
                $table->unsignedBigInteger('depreciation_run_id')->nullable();
                $table->string('transaction_number', 50)->nullable();
                $table->timestamps();
                $table->unique(['asset_id', 'period_year', 'period_month'], 'fa_depr_sched_period_uq');
            });
        }

        if (! Schema::hasTable('fa_depreciation_runs')) {
            Schema::create('fa_depreciation_runs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->default(0)->index();
                $table->string('run_key', 80)->unique();
                $table->date('run_date');
                $table->unsignedSmallInteger('period_year');
                $table->unsignedTinyInteger('period_month');
                $table->string('status', 20)->default('posted')->index();
                $table->decimal('total_amount', 20, 4)->default(0);
                $table->unsignedInteger('asset_count')->default(0);
                $table->unsignedBigInteger('posted_by')->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->unsignedBigInteger('reversed_by')->nullable();
                $table->timestamp('reversed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('fa_disposals')) {
            Schema::create('fa_disposals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('asset_id')->constrained('fa_assets');
                $table->date('disposal_date');
                $table->string('disposal_type', 30);
                $table->decimal('proceeds', 20, 4)->default(0);
                $table->decimal('cost_at_disposal', 20, 4)->default(0);
                $table->decimal('accumulated_at_disposal', 20, 4)->default(0);
                $table->decimal('gain_loss', 20, 4)->default(0);
                $table->string('status', 20)->default('posted');
                $table->string('transaction_number', 50)->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    private function createLoanTables(): void
    {
        if (! Schema::hasTable('acct_loans')) {
            Schema::create('acct_loans', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->default(0)->index();
                $table->string('loan_number', 40)->unique();
                $table->string('lender_name', 160);
                $table->string('loan_type', 60)->default('term');
                $table->decimal('principal', 20, 4);
                $table->decimal('outstanding_principal', 20, 4);
                $table->decimal('annual_interest_rate', 8, 4)->default(0);
                $table->string('interest_type', 20)->default('reducing');
                $table->string('payment_frequency', 20)->default('monthly');
                $table->unsignedInteger('term_months')->default(12);
                $table->date('disbursement_date')->nullable();
                $table->date('maturity_date')->nullable();
                $table->unsignedBigInteger('bank_account_id')->nullable();
                $table->unsignedBigInteger('loan_liability_account_id')->nullable();
                $table->unsignedBigInteger('interest_expense_account_id')->nullable();
                $table->unsignedBigInteger('interest_payable_account_id')->nullable();
                $table->decimal('accrued_interest', 20, 4)->default(0);
                $table->string('disbursement_txn', 50)->nullable();
                $table->string('status', 20)->default('draft')->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_loan_schedule_lines')) {
            Schema::create('acct_loan_schedule_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('loan_id')->constrained('acct_loans')->cascadeOnDelete();
                $table->unsignedInteger('line_no');
                $table->date('due_date');
                $table->decimal('opening_principal', 20, 4)->default(0);
                $table->decimal('interest_amount', 20, 4)->default(0);
                $table->decimal('principal_amount', 20, 4)->default(0);
                $table->decimal('total_payment', 20, 4)->default(0);
                $table->decimal('closing_principal', 20, 4)->default(0);
                $table->string('status', 20)->default('scheduled');
                $table->timestamps();
                $table->unique(['loan_id', 'line_no'], 'acct_loan_sched_line_uq');
            });
        }

        if (! Schema::hasTable('acct_loan_payments')) {
            Schema::create('acct_loan_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('loan_id')->constrained('acct_loans');
                $table->unsignedBigInteger('schedule_line_id')->nullable();
                $table->date('payment_date');
                $table->decimal('principal_paid', 20, 4)->default(0);
                $table->decimal('interest_paid', 20, 4)->default(0);
                $table->decimal('total_paid', 20, 4)->default(0);
                $table->string('transaction_number', 50)->nullable();
                $table->string('event_key', 120)->unique();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    private function createCostCenterTables(): void
    {
        if (! Schema::hasTable('acct_cost_centers')) {
            Schema::create('acct_cost_centers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->default(0)->index();
                $table->string('code', 30);
                $table->string('name', 160);
                $table->string('description', 500)->nullable();
                $table->unsignedBigInteger('parent_id')->nullable()->index();
                $table->unsignedBigInteger('department_id')->nullable();
                $table->unsignedBigInteger('manager_user_id')->nullable();
                $table->string('status', 20)->default('active')->index();
                $table->timestamps();
                $table->unique(['business_id', 'code'], 'acct_cc_biz_code_uq');
            });
        }

        if (! Schema::hasTable('acct_cost_allocations')) {
            Schema::create('acct_cost_allocations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->default(0)->index();
                $table->string('name', 160);
                $table->date('allocation_date');
                $table->string('source_type', 80)->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->decimal('source_amount', 20, 4);
                $table->string('status', 20)->default('draft');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->string('notes', 500)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_cost_allocation_lines')) {
            Schema::create('acct_cost_allocation_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('allocation_id')->constrained('acct_cost_allocations')->cascadeOnDelete();
                $table->foreignId('cost_center_id')->constrained('acct_cost_centers');
                $table->decimal('amount', 20, 4);
                $table->decimal('percentage', 8, 4)->nullable();
                $table->timestamps();
            });
        }
    }

    private function createAdvancedBudgetTables(): void
    {
        if (! Schema::hasTable('acct_budget_versions')) {
            Schema::create('acct_budget_versions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->default(0)->index();
                $table->unsignedBigInteger('budget_id')->nullable();
                $table->unsignedInteger('version_no')->default(1);
                $table->string('name', 160);
                $table->string('financial_year', 10);
                $table->string('status', 20)->default('draft')->index();
                $table->unsignedBigInteger('cost_center_id')->nullable();
                $table->unsignedBigInteger('department_id')->nullable();
                $table->unsignedBigInteger('job_id')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_budget_version_lines')) {
            Schema::create('acct_budget_version_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('budget_version_id')->constrained('acct_budget_versions')->cascadeOnDelete();
                $table->unsignedBigInteger('chart_of_account_id');
                $table->decimal('month_1', 20, 4)->default(0);
                $table->decimal('month_2', 20, 4)->default(0);
                $table->decimal('month_3', 20, 4)->default(0);
                $table->decimal('month_4', 20, 4)->default(0);
                $table->decimal('month_5', 20, 4)->default(0);
                $table->decimal('month_6', 20, 4)->default(0);
                $table->decimal('month_7', 20, 4)->default(0);
                $table->decimal('month_8', 20, 4)->default(0);
                $table->decimal('month_9', 20, 4)->default(0);
                $table->decimal('month_10', 20, 4)->default(0);
                $table->decimal('month_11', 20, 4)->default(0);
                $table->decimal('month_12', 20, 4)->default(0);
                $table->decimal('annual_total', 20, 4)->default(0);
                $table->timestamps();
            });
        }
    }

    private function extendJournalAndBudgetTables(): void
    {
        if (Schema::hasTable('journal_entries')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                if (! Schema::hasColumn('journal_entries', 'cost_center_id')) {
                    $table->unsignedBigInteger('cost_center_id')->nullable()->after('source_id')->index();
                }
                if (! Schema::hasColumn('journal_entries', 'job_id')) {
                    $table->unsignedBigInteger('job_id')->nullable()->after('cost_center_id')->index();
                }
            });
        }

        if (Schema::hasTable('budgets')) {
            Schema::table('budgets', function (Blueprint $table) {
                if (! Schema::hasColumn('budgets', 'status')) {
                    $table->string('status', 20)->default('active')->after('financial_year');
                }
                if (! Schema::hasColumn('budgets', 'version_no')) {
                    $table->unsignedInteger('version_no')->default(1)->after('status');
                }
                if (! Schema::hasColumn('budgets', 'active_version_id')) {
                    $table->unsignedBigInteger('active_version_id')->nullable()->after('version_no');
                }
            });
        }
    }

    private function seedAssetCategories(): void
    {
        if (! Schema::hasTable('fa_asset_categories') || ! Schema::hasTable('chart_of_accounts')) {
            return;
        }

        $gl = fn (string $code): ?int => (int) (DB::table('chart_of_accounts')->where('gl_code', $code)->where('business_id', 0)->value('id') ?: 0) ?: null;

        $assetGl = $gl('1400');
        $accDepGl = $gl('1450');
        $deprExpGl = $gl('6400');
        $gainGl = $gl('4500');
        $lossGl = $gl('6500');

        $categories = [
            ['code' => 'COMPUTERS', 'name' => 'Computers & IT Equipment', 'default_useful_life_months' => 36],
            ['code' => 'FURNITURE', 'name' => 'Furniture', 'default_useful_life_months' => 84],
            ['code' => 'MACHINERY', 'name' => 'Machinery', 'default_useful_life_months' => 120],
            ['code' => 'VEHICLES', 'name' => 'Vehicles', 'default_useful_life_months' => 60],
            ['code' => 'OFFICE', 'name' => 'Office Equipment', 'default_useful_life_months' => 60],
            ['code' => 'BUILDINGS', 'name' => 'Buildings', 'default_useful_life_months' => 240],
            ['code' => 'OTHER', 'name' => 'Other Fixed Assets', 'default_useful_life_months' => 60],
        ];

        foreach ($categories as $cat) {
            DB::table('fa_asset_categories')->updateOrInsert(
                ['business_id' => 0, 'code' => $cat['code']],
                array_merge($cat, [
                    'business_id' => 0,
                    'asset_account_id' => $assetGl,
                    'accumulated_depreciation_account_id' => $accDepGl,
                    'depreciation_expense_account_id' => $deprExpGl,
                    'disposal_gain_account_id' => $gainGl,
                    'disposal_loss_account_id' => $lossGl,
                    'default_depreciation_method' => 'straight_line',
                    'default_salvage_value' => 0,
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
};