<?php

namespace Tests\Feature;

use App\Services\Accounting\CashFlowForecastService;
use App\Services\Accounting\CustomReportBuilderService;
use App\Services\Accounting\FinancialStatementService;
use App\Services\Accounting\TaxManagementService;
use App\Services\Accounting\YearEndClosingService;
use App\Services\AccountingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class AccountingPhase41StabilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->rebuildSchema();
        $this->seedCoa();
    }

    private function rebuildSchema(): void
    {
        foreach ([
            'transaction_payments', 'transactions',
            'accounting_manual_journals', 'accounting_audit_events',
            'acct_year_end_sessions', 'acct_financial_years',
            'acct_tax_codes', 'acct_tax_periods',
            'acct_cash_forecast_scenarios', 'acct_saved_reports',
            'accounting_postings', 'journal_entries', 'chart_of_accounts',
        ] as $table) {
            Schema::dropIfExists($table);
        }

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
            $table->string('event_key');
            $table->string('transaction_number');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('event_type');
            $table->decimal('amount', 20, 4);
            $table->boolean('reversed')->default(false);
            $table->timestamps();
        });

        Schema::create('acct_financial_years', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->default(0);
            $table->string('name');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status')->default('open');
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamps();
        });

        Schema::create('accounting_audit_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->default(0);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action')->nullable();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('acct_year_end_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('financial_year_id');
            $table->string('status')->default('draft');
            $table->string('closing_txn')->nullable();
            $table->json('checklist')->nullable();
            $table->json('preview')->nullable();
            $table->unsignedBigInteger('retained_earnings_account_id')->nullable();
            $table->unsignedBigInteger('executed_by')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->string('event_key')->unique();
            $table->timestamps();
        });

        Schema::create('accounting_manual_journals', function (Blueprint $table) {
            $table->id();
            $table->string('journal_number')->unique();
            $table->date('journal_date');
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('acct_tax_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->default(0);
            $table->string('code');
            $table->string('name');
            $table->string('tax_type');
            $table->decimal('rate', 8, 4)->default(0);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('inclusive')->default(false);
            $table->unsignedBigInteger('output_account_id')->nullable();
            $table->unsignedBigInteger('input_account_id')->nullable();
            $table->unsignedBigInteger('payable_account_id')->nullable();
            $table->unsignedBigInteger('receivable_account_id')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('acct_tax_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->default(0);
            $table->string('name');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status')->default('open');
            $table->timestamps();
        });

        Schema::create('acct_saved_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->default(0);
            $table->string('name');
            $table->string('report_type');
            $table->json('config')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('acct_cash_forecast_scenarios', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('assumptions')->nullable();
            $table->unsignedInteger('horizon_days')->default(30);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable();
            $table->string('payment_status')->nullable();
            $table->date('transaction_date')->nullable();
            $table->decimal('final_total', 20, 4)->default(0);
            $table->decimal('amount_paid', 20, 4)->default(0);
            $table->string('invoice_no')->nullable();
        });
    }

    private function seedCoa(): void
    {
        $rows = [
            [1, AccountingService::GL_CASH, 'Cash', 'asset'],
            [3, AccountingService::GL_SALES_REVENUE, 'Sales', 'income'],
            [4, AccountingService::GL_GENERAL_EXPENSE, 'Expense', 'expense'],
            [6, AccountingService::GL_OWNER_EQUITY, 'Equity', 'equity'],
            [7, AccountingService::GL_TAX_PAYABLE, 'Tax Payable', 'liability'],
        ];
        foreach ($rows as $r) {
            DB::table('chart_of_accounts')->insert([
                'id' => $r[0], 'gl_code' => $r[1], 'name' => $r[2], 'account_type' => $r[3], 'active' => 1,
            ]);
        }
    }

    public function test_company_wide_pl_includes_cost_centered_income(): void
    {
        $txn = 'CC-'.uniqid();
        DB::table('journal_entries')->insert([
            [
                'chart_of_account_id' => 3,
                'transaction_number' => $txn,
                'date' => '2026-03-01',
                'cost_center_id' => 99,
                'debit' => 0,
                'credit' => 500,
                'reversed' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'chart_of_account_id' => 1,
                'transaction_number' => $txn,
                'date' => '2026-03-01',
                'cost_center_id' => 99,
                'debit' => 500,
                'credit' => 0,
                'reversed' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $pl = FinancialStatementService::profitAndLoss('2026-01-01', '2026-12-31');
        $this->assertEquals(500.0, (float) $pl['income']);
    }

    public function test_year_end_reopen_then_execute_closes_again(): void
    {
        AccountingService::postOnce('sale:41', 'order', 1, 1, 3, 400, '2026-06-01', 'Sale', 'S41', 'sale');
        AccountingService::postOnce('exp:41', 'expense', 1, 4, 1, 100, '2026-06-02', 'Expense', 'E41', 'expense');

        $fyId = DB::table('acct_financial_years')->insertGetId([
            'business_id' => 0, 'name' => 'FY41', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31',
            'status' => 'open', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $txn1 = YearEndClosingService::executeClosing($fyId, 6);
        YearEndClosingService::reopen($fyId);
        $txn2 = YearEndClosingService::executeClosing($fyId, 6);

        $this->assertNotSame($txn1, $txn2);
        $this->assertEquals('closed', DB::table('acct_financial_years')->where('id', $fyId)->value('status'));
        $this->assertTrue(
            DB::table('accounting_postings')->where('transaction_number', $txn2)->where('reversed', 0)->exists()
        );
    }

    public function test_execute_closing_blocked_when_unposted_manual_exists(): void
    {
        AccountingService::postOnce('sale:42', 'order', 1, 1, 3, 100, '2026-06-01', 'Sale', 'S42', 'sale');
        DB::table('accounting_manual_journals')->insert([
            'journal_number' => 'MJ-DRAFT', 'journal_date' => '2026-06-05', 'status' => 'draft',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $fyId = DB::table('acct_financial_years')->insertGetId([
            'business_id' => 0, 'name' => 'FY42', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31',
            'status' => 'open', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Year-end checklist not ready');
        YearEndClosingService::executeClosing($fyId, 6);
    }

    public function test_tax_post_adjustment_missing_period_throws(): void
    {
        $codeId = TaxManagementService::createTaxCode(0, [
            'code' => 'VAT41', 'name' => 'VAT', 'tax_type' => 'vat', 'rate' => 18,
            'effective_from' => '2026-01-01', 'output_account_id' => 7,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tax period not found.');
        TaxManagementService::postAdjustment(0, 99999, $codeId, 50, 'output', 'tax-adj:missing', null, null);
    }

    public function test_custom_report_delete_scoped_by_business(): void
    {
        $id = CustomReportBuilderService::create(1, 'Scoped', 'trial_balance', ['from' => '2026-01-01', 'to' => '2026-06-30'], 1);

        $this->expectException(RuntimeException::class);
        CustomReportBuilderService::delete($id, 2);
        $this->assertNotNull(DB::table('acct_saved_reports')->where('id', $id)->first());
    }

    public function test_forecast_uses_remaining_when_amount_paid_present(): void
    {
        DB::table('transactions')->insert([
            'type' => 'sell',
            'payment_status' => 'partial',
            'transaction_date' => '2026-06-10',
            'final_total' => 1000,
            'amount_paid' => 400,
            'invoice_no' => 'INV-41',
        ]);
        $scenarioId = DB::table('acct_cash_forecast_scenarios')->insertGetId([
            'name' => 'Base', 'assumptions' => json_encode(['collection_pct' => 100]),
            'horizon_days' => 30, 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $run = CashFlowForecastService::runScenario($scenarioId, '2026-06-01');
        $firstInflow = $run['detail']['inflows'][0] ?? null;

        $this->assertNotNull($firstInflow);
        $this->assertEquals(600.0, $firstInflow['amount']);
    }
}