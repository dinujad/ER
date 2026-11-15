<?php

namespace Tests\Feature;

use App\Models\Accounting\AcctBudgetVersion;
use App\Models\Accounting\FaAsset;
use App\Models\Accounting\FaAssetCategory;
use App\Models\Accounting\FaDepreciationRun;
use App\Services\Accounting\AdvancedBudgetService;
use App\Services\Accounting\AssetDisposalService;
use App\Services\Accounting\CostCenterService;
use App\Services\Accounting\DepreciationEngine;
use App\Services\Accounting\FixedAssetService;
use App\Services\Accounting\JobCostAccountingService;
use App\Services\Accounting\LoanAccountingService;
use App\Services\AccountingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class AccountingPhase3CoreTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->dropPhase3Tables();
        $this->createCoreAccountingTables();
        $this->createPhase3Tables();
        $this->seedGlAndCategory();
    }

    private function dropPhase3Tables(): void
    {
        foreach ([
            'acct_budget_version_lines', 'acct_budget_versions',
            'acct_cost_allocation_lines', 'acct_cost_allocations', 'acct_cost_centers',
            'acct_loan_payments', 'acct_loan_schedule_lines', 'acct_loans',
            'fa_disposals', 'fa_depreciation_schedules', 'fa_depreciation_runs',
            'fa_asset_events', 'fa_assets', 'fa_asset_categories',
            'accounting_audit_events', 'accounting_postings', 'journal_entries', 'chart_of_accounts',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function createCoreAccountingTables(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('gl_code')->unique();
            $table->string('name')->nullable();
            $table->string('account_type')->nullable();
            $table->boolean('active')->default(true);
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chart_of_account_id');
            $table->string('transaction_number');
            $table->string('transaction_type')->nullable();
            $table->string('name')->nullable();
            $table->date('date')->nullable();
            $table->string('month')->nullable();
            $table->string('year')->nullable();
            $table->string('reference')->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->unsignedBigInteger('job_id')->nullable();
            $table->decimal('debit', 20, 4)->nullable();
            $table->decimal('credit', 20, 4)->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('reversed')->default(false);
            $table->boolean('reversible')->default(true);
            $table->boolean('manual_entry')->default(false);
            $table->timestamps();
        });

        Schema::create('accounting_postings', function (Blueprint $table) {
            $table->id();
            $table->string('event_key')->unique();
            $table->string('transaction_number');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('event_type');
            $table->decimal('amount', 20, 4);
            $table->boolean('reversed')->default(false);
            $table->timestamps();
        });

        Schema::create('accounting_audit_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->default(0);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    private function createPhase3Tables(): void
    {
        Schema::create('fa_asset_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->default(0);
            $table->string('code', 30);
            $table->string('name', 160);
            $table->unsignedBigInteger('asset_account_id')->nullable();
            $table->unsignedBigInteger('accumulated_depreciation_account_id')->nullable();
            $table->unsignedBigInteger('depreciation_expense_account_id')->nullable();
            $table->unsignedBigInteger('disposal_gain_account_id')->nullable();
            $table->unsignedBigInteger('disposal_loss_account_id')->nullable();
            $table->string('default_depreciation_method', 30)->default('straight_line');
            $table->unsignedInteger('default_useful_life_months')->default(12);
            $table->decimal('default_salvage_value', 20, 4)->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('fa_assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->default(0);
            $table->unsignedBigInteger('category_id');
            $table->string('asset_code', 40);
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('acquisition_date')->nullable();
            $table->date('in_service_date')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('purchase_reference', 120)->nullable();
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->unsignedBigInteger('purchase_item_id')->nullable();
            $table->decimal('asset_cost', 20, 4)->default(0);
            $table->decimal('capitalized_costs', 20, 4)->default(0);
            $table->decimal('salvage_value', 20, 4)->default(0);
            $table->unsignedInteger('useful_life_months')->default(12);
            $table->string('depreciation_method', 30)->default('straight_line');
            $table->decimal('accumulated_depreciation', 20, 4)->default(0);
            $table->decimal('net_book_value', 20, 4)->default(0);
            $table->string('status', 30)->default('draft');
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->string('capitalization_txn', 50)->nullable();
            $table->timestamp('capitalized_at')->nullable();
            $table->date('disposal_date')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();
        });

        Schema::create('fa_asset_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asset_id');
            $table->string('event_type', 60);
            $table->date('event_date');
            $table->string('description', 500)->nullable();
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('fa_depreciation_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asset_id');
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->date('period_end');
            $table->decimal('scheduled_amount', 20, 4)->default(0);
            $table->decimal('accumulated_after', 20, 4)->default(0);
            $table->decimal('nbv_after', 20, 4)->default(0);
            $table->string('status', 20)->default('scheduled');
            $table->string('transaction_number', 50)->nullable();
            $table->timestamps();
            $table->unique(['asset_id', 'period_year', 'period_month']);
        });

        Schema::create('fa_depreciation_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->default(0);
            $table->string('run_key', 80)->unique();
            $table->date('run_date');
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->string('status', 20)->default('posted');
            $table->decimal('total_amount', 20, 4)->default(0);
            $table->unsignedInteger('asset_count')->default(0);
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('fa_disposals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asset_id');
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

        Schema::create('acct_loans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->default(0);
            $table->string('loan_number', 40)->unique();
            $table->string('lender_name', 160);
            $table->string('loan_type', 60)->default('term');
            $table->decimal('principal', 20, 4);
            $table->decimal('outstanding_principal', 20, 4);
            $table->decimal('annual_interest_rate', 8, 4)->default(12);
            $table->string('interest_type', 20)->default('reducing');
            $table->string('payment_frequency', 20)->default('monthly');
            $table->unsignedInteger('term_months')->default(12);
            $table->date('maturity_date')->nullable();
            $table->unsignedBigInteger('interest_payable_account_id')->nullable();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->unsignedBigInteger('loan_liability_account_id')->nullable();
            $table->unsignedBigInteger('interest_expense_account_id')->nullable();
            $table->string('status', 20)->default('draft');
            $table->string('disbursement_txn', 50)->nullable();
            $table->date('disbursement_date')->nullable();
            $table->timestamps();
        });

        Schema::create('acct_loan_schedule_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');
            $table->unsignedInteger('line_no');
            $table->date('due_date');
            $table->decimal('opening_principal', 20, 4)->default(0);
            $table->decimal('interest_amount', 20, 4)->default(0);
            $table->decimal('principal_amount', 20, 4)->default(0);
            $table->decimal('total_payment', 20, 4)->default(0);
            $table->decimal('closing_principal', 20, 4)->default(0);
            $table->string('status', 20)->default('scheduled');
            $table->timestamps();
        });

        Schema::create('acct_loan_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');
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

        Schema::create('acct_cost_centers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->default(0);
            $table->string('code', 30);
            $table->string('name', 160);
            $table->string('description')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('manager_user_id')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::create('acct_cost_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->default(0);
            $table->string('name', 160);
            $table->date('allocation_date');
            $table->string('source_type', 80)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->decimal('source_amount', 20, 4);
            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('acct_cost_allocation_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('allocation_id');
            $table->unsignedBigInteger('cost_center_id');
            $table->decimal('amount', 20, 4);
            $table->decimal('percentage', 8, 4)->nullable();
            $table->timestamps();
        });

        Schema::create('acct_budget_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->default(0);
            $table->unsignedBigInteger('budget_id')->nullable();
            $table->unsignedInteger('version_no')->default(1);
            $table->string('name', 160);
            $table->string('financial_year', 10);
            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('job_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('acct_budget_version_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('budget_version_id');
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

    private function seedGlAndCategory(): void
    {
        DB::table('chart_of_accounts')->insert([
            ['id' => 1, 'gl_code' => AccountingService::GL_BANK, 'name' => 'Bank', 'account_type' => 'asset', 'active' => 1],
            ['id' => 2, 'gl_code' => AccountingService::GL_ACCOUNTS_PAY, 'name' => 'AP', 'account_type' => 'liability', 'active' => 1],
            ['id' => 3, 'gl_code' => AccountingService::GL_FIXED_ASSETS, 'name' => 'Fixed Assets', 'account_type' => 'asset', 'active' => 1],
            ['id' => 4, 'gl_code' => AccountingService::GL_ACCUMULATED_DEPRECIATION, 'name' => 'Accum Dep', 'account_type' => 'asset', 'active' => 1],
            ['id' => 5, 'gl_code' => AccountingService::GL_DEPRECIATION_EXPENSE, 'name' => 'Dep Exp', 'account_type' => 'expense', 'active' => 1],
            ['id' => 6, 'gl_code' => AccountingService::GL_GAIN_ON_DISPOSAL, 'name' => 'Gain', 'account_type' => 'income', 'active' => 1],
            ['id' => 7, 'gl_code' => AccountingService::GL_LOSS_ON_DISPOSAL, 'name' => 'Loss', 'account_type' => 'expense', 'active' => 1],
            ['id' => 8, 'gl_code' => AccountingService::GL_LOANS_PAYABLE, 'name' => 'Loans', 'account_type' => 'liability', 'active' => 1],
            ['id' => 9, 'gl_code' => AccountingService::GL_INTEREST_EXPENSE, 'name' => 'Interest', 'account_type' => 'expense', 'active' => 1],
            ['id' => 10, 'gl_code' => AccountingService::GL_GENERAL_EXPENSE, 'name' => 'G&A', 'account_type' => 'expense', 'active' => 1],
        ]);

        FaAssetCategory::create([
            'business_id' => 0,
            'code' => 'OFFICE',
            'name' => 'Office Equipment',
            'asset_account_id' => 3,
            'accumulated_depreciation_account_id' => 4,
            'depreciation_expense_account_id' => 5,
            'disposal_gain_account_id' => 6,
            'disposal_loss_account_id' => 7,
            'default_depreciation_method' => 'straight_line',
            'default_useful_life_months' => 12,
        ]);
    }

    private function makeAsset(float $cost = 12000, int $lifeMonths = 12): FaAsset
    {
        $category = FaAssetCategory::first();
        $asset = FixedAssetService::createDraft([
            'category_id' => $category->id,
            'name' => 'Laptop',
            'asset_cost' => $cost,
            'salvage_value' => 0,
            'useful_life_months' => $lifeMonths,
        ]);
        $asset = FixedAssetService::capitalize($asset, '2026-01-15', AccountingService::GL_ACCOUNTS_PAY);
        $asset->update(['in_service_date' => '2026-01-15']);

        return $asset->fresh();
    }

    public function test_asset_capitalization_posts_balanced_journal(): void
    {
        $asset = $this->makeAsset(10000);
        $this->assertSame('active', $asset->status);
        $rows = DB::table('journal_entries')->where('transaction_number', $asset->capitalization_txn)->get();
        $this->assertCount(2, $rows);
        $this->assertEquals(10000.0, round((float) $rows->sum('debit'), 2));
        $this->assertEquals(10000.0, round((float) $rows->sum('credit'), 2));
    }

    public function test_capitalization_post_once_is_idempotent(): void
    {
        $category = FaAssetCategory::first();
        $asset = FixedAssetService::createDraft(['category_id' => $category->id, 'name' => 'Desk', 'asset_cost' => 5000]);
        FixedAssetService::capitalize($asset, '2026-02-01', AccountingService::GL_ACCOUNTS_PAY);
        $countBefore = DB::table('journal_entries')->count();
        $txn = AccountingService::postOnce(
            'fa:capitalize:'.$asset->id,
            'fa_asset',
            $asset->id,
            3,
            2,
            5000,
            '2026-02-01',
            'retry',
            $asset->fresh()->asset_code,
            'fixed_asset_capitalization'
        );
        $this->assertSame($asset->fresh()->capitalization_txn, $txn);
        $this->assertSame($countBefore, DB::table('journal_entries')->count());
    }

    public function test_straight_line_depreciation_amount(): void
    {
        $amount = DepreciationEngine::periodAmount('straight_line', 12000, 0, 12, 1, 12000, 0);
        $this->assertSame(1000.0, $amount);
        $last = DepreciationEngine::periodAmount('straight_line', 12000, 0, 12, 12, 1000, 11000);
        $this->assertSame(1000.0, $last);
    }

    public function test_depreciation_run_posts_and_is_idempotent(): void
    {
        $asset = $this->makeAsset(12000, 12);
        DepreciationEngine::generateSchedule($asset);

        $run1 = DepreciationEngine::runPeriod(0, 2026, 1, '2026-01-31');
        $this->assertSame(1000.0, (float) $run1->total_amount);
        $this->assertSame(1000.0, (float) $asset->fresh()->accumulated_depreciation);

        $run2 = DepreciationEngine::runPeriod(0, 2026, 1, '2026-01-31');
        $this->assertSame($run1->id, $run2->id);
        $this->assertSame(1, FaDepreciationRun::count());
    }

    public function test_asset_disposal_with_gain(): void
    {
        $asset = $this->makeAsset(10000, 1);
        DepreciationEngine::generateSchedule($asset);
        DepreciationEngine::runPeriod(0, 2026, 1, '2026-01-31');

        $disposal = AssetDisposalService::dispose($asset->fresh(), 'sale', '2026-02-01', 3000);
        $this->assertGreaterThan(0, (float) $disposal->gain_loss);
        $this->assertSame('disposed', $asset->fresh()->status);
        $rows = DB::table('journal_entries')->where('transaction_number', $disposal->transaction_number)->get();
        $debit = round((float) $rows->sum('debit'), 2);
        $credit = round((float) $rows->sum('credit'), 2);
        $this->assertSame($debit, $credit);
    }

    public function test_loan_disbursement_and_payment(): void
    {
        $loan = LoanAccountingService::createLoan([
            'lender_name' => 'Test Bank',
            'principal' => 100000,
            'annual_interest_rate' => 12,
            'term_months' => 12,
            'bank_account_id' => 1,
            'loan_liability_account_id' => 8,
            'interest_expense_account_id' => 9,
        ]);
        LoanAccountingService::buildEqualInstallmentSchedule($loan, '2026-02-01');
        $loan = LoanAccountingService::disburse($loan, '2026-01-01');

        $line = $loan->scheduleLines()->first();
        LoanAccountingService::recordPayment($loan, '2026-02-01', (float) $line->principal_amount, (float) $line->interest_amount, $line->id);

        $this->assertLessThan(100000.0, (float) $loan->fresh()->outstanding_principal);
        $this->assertGreaterThan(0, DB::table('journal_entries')->where('transaction_type', 'loan_payment')->count());
    }

    public function test_duplicate_loan_payment_returns_same_record(): void
    {
        $loan = LoanAccountingService::createLoan([
            'lender_name' => 'Bank',
            'principal' => 50000,
            'bank_account_id' => 1,
            'loan_liability_account_id' => 8,
            'interest_expense_account_id' => 9,
        ]);
        LoanAccountingService::disburse($loan, '2026-01-01');

        $a = LoanAccountingService::recordPayment($loan, '2026-03-01', 4000, 500);
        $b = LoanAccountingService::recordPayment($loan, '2026-03-01', 4000, 500);
        $this->assertSame($a->id, $b->id);
        $this->assertSame(5, DB::table('journal_entries')->count()); // disburse 2 + payment 3 (first only)
    }

    public function test_cost_allocation_must_match_source_amount(): void
    {
        $cc1 = CostCenterService::create(['code' => 'CC1', 'name' => 'Production']);
        $cc2 = CostCenterService::create(['code' => 'CC2', 'name' => 'Admin']);

        $this->expectException(RuntimeException::class);
        CostCenterService::createAllocation([
            'name' => 'Overhead split',
            'allocation_date' => '2026-01-01',
            'source_amount' => 1000,
        ], [
            ['cost_center_id' => $cc1->id, 'amount' => 600],
            ['cost_center_id' => $cc2->id, 'amount' => 300],
        ]);
    }

    public function test_cost_allocation_succeeds_when_balanced(): void
    {
        $cc1 = CostCenterService::create(['code' => 'CC1', 'name' => 'Production']);
        $cc2 = CostCenterService::create(['code' => 'CC2', 'name' => 'Admin']);

        $alloc = CostCenterService::createAllocation([
            'name' => 'Overhead split',
            'allocation_date' => '2026-01-01',
            'source_amount' => 1000,
        ], [
            ['cost_center_id' => $cc1->id, 'amount' => 700],
            ['cost_center_id' => $cc2->id, 'amount' => 300],
        ]);

        $this->assertSame('1000.00', number_format((float) $alloc->lines->sum('amount'), 2, '.', ''));
    }

    public function test_budget_version_workflow_and_vs_actual(): void
    {
        $version = AdvancedBudgetService::createVersion([
            'name' => 'FY2026 OpEx',
            'financial_year' => '2026',
        ], [
            ['chart_of_account_id' => 10, 'month_1' => 1000, 'month_2' => 1000],
        ]);

        AdvancedBudgetService::submit($version);
        AdvancedBudgetService::approve($version);
        AdvancedBudgetService::activate($version);

        AccountingService::postEntry(10, 1, 250, '2026-01-15', 'Office supplies', null, 'expense', [
            'year' => '2026',
            'month' => '1',
        ]);

        $report = AdvancedBudgetService::budgetVsActual(AcctBudgetVersion::first(), 1);
        $this->assertSame('1000.00', $report['rows'][0]['budget']);
        $this->assertSame('250.00', $report['rows'][0]['actual']);
        $this->assertTrue($report['rows'][0]['favorable']);
    }

    public function test_job_ledger_debits_by_job_id(): void
    {
        AccountingService::postEntry(10, 1, 1500, '2026-01-10', 'Job material', null, 'job_cost', [
            'job_id' => 42,
            'year' => '2026',
            'month' => '1',
        ]);

        $this->assertSame(1500.0, JobCostAccountingService::ledgerDebitsForJob(42));
        $lines = JobCostAccountingService::ledgerLinesForJob(42);
        $this->assertCount(2, $lines);
    }

    public function test_depreciation_run_can_be_reversed(): void
    {
        $asset = $this->makeAsset(12000, 12);
        DepreciationEngine::generateSchedule($asset);
        $run = DepreciationEngine::runPeriod(0, 2026, 1, '2026-01-31');
        $this->assertSame(1000.0, (float) $asset->fresh()->accumulated_depreciation);

        DepreciationEngine::reverseRun($run);
        $this->assertSame(0.0, (float) $asset->fresh()->accumulated_depreciation);
        $this->assertSame('reversed', $run->fresh()->status);

        $runAgain = DepreciationEngine::runPeriod(0, 2026, 1, '2026-01-31');
        $this->assertSame(1000.0, (float) $runAgain->total_amount);
        $this->assertSame(1000.0, (float) $asset->fresh()->accumulated_depreciation);
    }

    public function test_loan_early_settle_and_equal_principal_schedule(): void
    {
        $loan = LoanAccountingService::createLoan([
            'lender_name' => 'Settle Bank',
            'principal' => 12000,
            'annual_interest_rate' => 12,
            'term_months' => 12,
            'bank_account_id' => 1,
            'loan_liability_account_id' => 8,
            'interest_expense_account_id' => 9,
        ]);
        LoanAccountingService::buildEqualPrincipalSchedule($loan, '2026-02-01');
        $this->assertCount(12, $loan->fresh()->scheduleLines);
        $loan = LoanAccountingService::disburse($loan, '2026-01-01');

        LoanAccountingService::earlySettle($loan, '2026-03-15', 100);
        $this->assertSame(0.0, (float) $loan->fresh()->outstanding_principal);
        $this->assertSame('closed', $loan->fresh()->status);
    }

    public function test_declining_balance_period_amount_respects_salvage(): void
    {
        $amount = DepreciationEngine::periodAmount('declining_balance', 10000, 1000, 24, 1, 10000, 0);
        $this->assertGreaterThan(0, $amount);
        $this->assertLessThanOrEqual(9000.0, $amount);
    }
}