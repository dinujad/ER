<?php

namespace Tests\Feature;

use App\Services\Accounting\CashFlowForecastService;
use App\Services\Accounting\CustomReportBuilderService;
use App\Services\Accounting\FinancialDashboardService;
use App\Services\Accounting\FinancialStatementService;
use App\Services\Accounting\ScheduledFinancialReportService;
use App\Services\Accounting\TaxManagementService;
use App\Services\Accounting\YearEndClosingService;
use App\Services\AccountingService;
use App\Services\AccountingWorkspaceMetrics;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class AccountingPhase4CoreTest extends TestCase
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
            'acct_year_end_sessions', 'acct_financial_years',
            'acct_tax_adjustments', 'acct_tax_periods', 'acct_tax_codes',
            'acct_cash_forecast_runs', 'acct_cash_forecast_scenarios',
            'acct_report_executions', 'acct_report_schedules', 'acct_saved_reports',
            'accounting_audit_events', 'accounting_postings', 'journal_entries', 'chart_of_accounts',
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
            $table->string('action')->nullable();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->string('reason')->nullable();
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

        Schema::create('acct_report_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->default(0);
            $table->string('report_type');
            $table->string('frequency');
            $table->json('config')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
        });

        Schema::create('acct_report_executions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('schedule_id')->nullable();
            $table->string('report_type');
            $table->string('status')->default('pending');
            $table->string('storage_disk')->default('local');
            $table->string('storage_path')->nullable();
            $table->string('format')->default('csv');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->string('event_key')->unique();
            $table->timestamps();
        });

        Schema::create('acct_cash_forecast_scenarios', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('scenario_type')->default('base');
            $table->json('assumptions')->nullable();
            $table->unsignedInteger('horizon_days')->default(30);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('acct_cash_forecast_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('scenario_id');
            $table->date('as_of_date');
            $table->decimal('opening_cash', 20, 4)->default(0);
            $table->decimal('total_inflows', 20, 4)->default(0);
            $table->decimal('total_outflows', 20, 4)->default(0);
            $table->decimal('closing_cash', 20, 4)->default(0);
            $table->json('detail')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
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
            $table->unsignedBigInteger('payable_account_id')->nullable();
            $table->unsignedBigInteger('receivable_account_id')->nullable();
            $table->unsignedBigInteger('input_account_id')->nullable();
            $table->unsignedBigInteger('output_account_id')->nullable();
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

        Schema::create('acct_tax_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('event_key')->unique();
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

        Schema::create('acct_year_end_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('financial_year_id');
            $table->string('status')->default('draft');
            $table->string('closing_txn')->nullable();
            $table->unsignedBigInteger('retained_earnings_account_id')->nullable();
            $table->json('preview')->nullable();
            $table->json('checklist')->nullable();
            $table->unsignedBigInteger('executed_by')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->string('event_key')->unique();
            $table->timestamps();
        });
    }

    private function seedCoa(): void
    {
        $rows = [
            [1, AccountingService::GL_CASH, 'Cash', 'asset'],
            [2, AccountingService::GL_ACCOUNTS_RECV, 'AR', 'asset'],
            [3, AccountingService::GL_SALES_REVENUE, 'Sales', 'income'],
            [4, AccountingService::GL_GENERAL_EXPENSE, 'Expense', 'expense'],
            [5, AccountingService::GL_ACCOUNTS_PAY, 'AP', 'liability'],
            [6, AccountingService::GL_OWNER_EQUITY, 'Equity', 'equity'],
            [7, AccountingService::GL_TAX_PAYABLE, 'Tax Payable', 'liability'],
            [8, AccountingService::GL_TAX_RECEIVABLE, 'Tax Receivable', 'asset'],
        ];
        foreach ($rows as $r) {
            DB::table('chart_of_accounts')->insert([
                'id' => $r[0], 'gl_code' => $r[1], 'name' => $r[2], 'account_type' => $r[3], 'active' => 1,
            ]);
        }
    }

  private function postSampleActivity(): void
    {
        AccountingService::postOnce('sale:1', 'order', 1, 2, 3, 1000, '2026-06-15', 'Sale', 'S1', 'sale');
        AccountingService::postOnce('exp:1', 'expense', 1, 4, 1, 250, '2026-06-20', 'Expense', 'E1', 'expense');
    }

    public function test_profit_and_loss_net_from_income_expense_journals(): void
    {
        $this->postSampleActivity();
        $pl = FinancialStatementService::profitAndLoss('2026-01-01', '2026-12-31');
        $this->assertEquals(1000.0, (float) $pl['income']);
        $this->assertEquals(250.0, (float) $pl['expense']);
        $this->assertEquals(750.0, (float) $pl['net']);
    }

    public function test_balance_sheet_assets_approx_liabilities_plus_equity(): void
    {
        $this->postSampleActivity();
        $bs = FinancialStatementService::balanceSheet('2026-12-31');
        $this->assertEqualsWithDelta(
            $bs['assets'],
            $bs['liabilities_plus_equity'],
            0.02,
            'Assets should approximate Liabilities + Equity (with current earnings)'
        );
    }

    public function test_forecast_does_not_create_journals(): void
    {
        $this->postSampleActivity();
        $before = DB::table('journal_entries')->count();
        $scenarioId = DB::table('acct_cash_forecast_scenarios')->insertGetId([
            'name' => 'Base', 'scenario_type' => 'base', 'assumptions' => json_encode(['collection_pct' => 100]),
            'horizon_days' => 30, 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        CashFlowForecastService::runScenario($scenarioId, '2026-06-30');
        $this->assertEquals($before, DB::table('journal_entries')->count());
    }

    public function test_tax_code_create_and_summary(): void
    {
        $periodId = DB::table('acct_tax_periods')->insertGetId([
            'business_id' => 0, 'name' => 'Q2', 'starts_on' => '2026-04-01', 'ends_on' => '2026-06-30',
            'status' => 'open', 'created_at' => now(), 'updated_at' => now(),
        ]);
        TaxManagementService::createTaxCode(0, [
            'code' => 'VAT18', 'name' => 'VAT', 'tax_type' => 'vat', 'rate' => 18,
            'effective_from' => '2026-04-01', 'payable_account_id' => 7, 'output_account_id' => 7,
        ]);
        AccountingService::postOnce('tax:1', 'tax', 1, 1, 7, 100, '2026-05-01', 'VAT out', 'T1', 'tax');
        $summary = TaxManagementService::taxSummary($periodId);
        $this->assertNotEmpty($summary['lines']);
        $this->assertEquals(100.0, $summary['lines'][0]['net_payable']);
    }

    public function test_year_end_closing_idempotent(): void
    {
        $this->postSampleActivity();
        $fyId = DB::table('acct_financial_years')->insertGetId([
            'business_id' => 0, 'name' => 'FY2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31',
            'status' => 'open', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $txn1 = YearEndClosingService::executeClosing($fyId, 6);
        $txn2 = YearEndClosingService::executeClosing($fyId, 6);
        $this->assertSame($txn1, $txn2);
        $this->assertEquals(1, DB::table('accounting_postings')->where('event_key', 'year-end:'.$fyId)->count());
    }

    public function test_saved_report_allowlist_rejects_bad_type(): void
    {
        $this->expectException(RuntimeException::class);
        CustomReportBuilderService::create(0, 'Bad', 'raw_sql_dump', [], 1);
    }

    public function test_scheduled_report_duplicate_event_key(): void
    {
        $scheduleId = DB::table('acct_report_schedules')->insertGetId([
            'business_id' => 0,
            'report_type' => 'trial_balance',
            'frequency' => 'daily',
            'config' => json_encode(['from' => '2026-01-01', 'to' => '2026-06-30', 'format' => 'csv']),
            'next_run_at' => now()->subHour(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        ScheduledFinancialReportService::executeSchedule($scheduleId, '2026-06-15');
        $count = DB::table('acct_report_executions')->where('event_key', 'acct-report-schedule:'.$scheduleId.':2026-06-15')->count();
        ScheduledFinancialReportService::executeSchedule($scheduleId, '2026-06-15');
        $this->assertEquals($count, DB::table('acct_report_executions')->where('event_key', 'acct-report-schedule:'.$scheduleId.':2026-06-15')->count());
    }

    public function test_dashboard_kpi_revenue_calculation(): void
    {
        $this->postSampleActivity();
        $dash = app(FinancialDashboardService::class)->dashboard('2026-01-01', '2026-12-31');
        $this->assertEquals(1000.0, $dash['revenue']);
        $legacy = app(AccountingWorkspaceMetrics::class)->summary('2026-01-01', '2026-12-31');
        $this->assertEquals(1000.0, $legacy['revenue']);
    }
}