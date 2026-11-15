<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\SaleReturn;
use App\Services\AccountingService;
use App\Services\SalesAccountingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 1 final verification: DB-backed journal workflows + accounting equations.
 * Uses sqlite :memory: (phpunit.xml) — never production.
 */
class AccountingPhase1VerificationTest extends TestCase
{
    /** @var array<string, int> */
    private array $coa = [];

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('accounting_postings');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('chart_of_accounts');

        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('gl_code')->unique();
            $table->string('name')->nullable();
            $table->string('account_type')->nullable();
            $table->unsignedBigInteger('business_id')->default(0);
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
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
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
            $table->nullableMorphs('source');
            $table->string('event_type');
            $table->decimal('amount', 20, 4);
            $table->boolean('reversed')->default(false);
            $table->timestamps();
        });

        $accounts = [
            ['gl' => AccountingService::GL_CASH, 'name' => 'Cash', 'type' => 'asset'],
            ['gl' => AccountingService::GL_BANK, 'name' => 'Bank', 'type' => 'asset'],
            ['gl' => AccountingService::GL_PDC_RECEIVABLE, 'name' => 'PDC', 'type' => 'asset'],
            ['gl' => AccountingService::GL_ACCOUNTS_RECV, 'name' => 'AR', 'type' => 'asset'],
            ['gl' => AccountingService::GL_RAW_MATERIALS, 'name' => 'Raw materials', 'type' => 'asset'],
            ['gl' => AccountingService::GL_WORK_IN_PROGRESS, 'name' => 'WIP', 'type' => 'asset'],
            ['gl' => AccountingService::GL_FINISHED_GOODS, 'name' => 'Finished goods', 'type' => 'asset'],
            ['gl' => AccountingService::GL_ACCOUNTS_PAY, 'name' => 'AP', 'type' => 'liability'],
            ['gl' => AccountingService::GL_PAYROLL_PAYABLE, 'name' => 'Payroll payable', 'type' => 'liability'],
            ['gl' => AccountingService::GL_OWNER_EQUITY, 'name' => 'Equity', 'type' => 'equity'],
            ['gl' => AccountingService::GL_SALES_REVENUE, 'name' => 'Sales', 'type' => 'income'],
            ['gl' => AccountingService::GL_SALES_RETURNS, 'name' => 'Sales returns', 'type' => 'income'],
            ['gl' => AccountingService::GL_COST_OF_GOODS, 'name' => 'COGS', 'type' => 'expense'],
            ['gl' => AccountingService::GL_SALARY_EXPENSE, 'name' => 'Salary', 'type' => 'expense'],
            ['gl' => AccountingService::GL_GENERAL_EXPENSE, 'name' => 'Expense', 'type' => 'expense'],
            ['gl' => AccountingService::GL_TRANSPORT_EXPENSE, 'name' => 'Transport', 'type' => 'expense'],
        ];

        foreach ($accounts as $i => $row) {
            $id = $i + 1;
            DB::table('chart_of_accounts')->insert([
                'id' => $id,
                'gl_code' => $row['gl'],
                'name' => $row['name'],
                'account_type' => $row['type'],
                'business_id' => 0,
                'active' => true,
            ]);
            $this->coa[$row['gl']] = $id;
        }
    }

    public function test_end_to_end_sales_purchase_expense_payroll_inventory_cheque_workflows_balance(): void
    {
        $sales = app(SalesAccountingService::class);

        // 1) Credit sale (invoice completed) — POS/wholesale revenue recognition
        $order = new Order([
            'status' => Order::STATUS_COMPLETED,
            'total_amount' => 10000,
            'order_date' => '2026-11-01',
            'order_number' => 'ORD-E2E-1',
        ]);
        $order->id = 501;
        $order->exists = true;
        $order->setRelation('items', collect());
        $sales->synchronizeOrder($order);

        // Manual COGS (items empty → no auto COGS); simulate stock deduction cost
        AccountingService::postOnce(
            'order:501:cogs',
            Order::class,
            501,
            $this->coa[AccountingService::GL_COST_OF_GOODS],
            $this->coa[AccountingService::GL_FINISHED_GOODS],
            4000,
            '2026-11-01',
            'COGS ORD-E2E-1',
            'ORD-E2E-1',
            'sale_cogs'
        );

        // 2) Mixed payments: cash + bank
        $cashPay = new Payment([
            'entry_type' => Payment::ENTRY_PAYMENT,
            'amount' => 3000,
            'payment_method' => 'cash',
            'payment_date' => '2026-11-02',
        ]);
        $cashPay->id = 601;
        $cashPay->order_id = 501;
        $cashPay->exists = true;
        $cashPay->setRelation('order', $order);
        $sales->synchronizePayment($cashPay);

        $bankPay = new Payment([
            'entry_type' => Payment::ENTRY_PAYMENT,
            'amount' => 2000,
            'payment_method' => 'bank',
            'payment_date' => '2026-11-02',
        ]);
        $bankPay->id = 602;
        $bankPay->order_id = 501;
        $bankPay->exists = true;
        $bankPay->setRelation('order', $order);
        $sales->synchronizePayment($bankPay);

        // 3) Cheque receipt + clearance (lifecycle ledger shape)
        AccountingService::postOnce(
            'payment:603:cheque-receipt',
            Payment::class,
            603,
            $this->coa[AccountingService::GL_PDC_RECEIVABLE],
            $this->coa[AccountingService::GL_ACCOUNTS_RECV],
            5000,
            '2026-11-03',
            'Cheque received',
            'CHQ-1',
            'cheque_receipt'
        );
        AccountingService::postOnce(
            'payment:603:cheque-clearance',
            Payment::class,
            603,
            $this->coa[AccountingService::GL_BANK],
            $this->coa[AccountingService::GL_PDC_RECEIVABLE],
            5000,
            '2026-11-05',
            'Cheque cleared',
            'CHQ-1',
            'cheque_clearance'
        );

        // 4) Customer refund (cash)
        $refund = new Payment([
            'entry_type' => Payment::ENTRY_REFUND,
            'amount' => 500,
            'payment_method' => 'cash',
            'payment_date' => '2026-11-06',
        ]);
        $refund->id = 604;
        $refund->order_id = 501;
        $refund->exists = true;
        $refund->setRelation('order', $order);
        $refund->setRelation('saleReturn', null);
        $sales->synchronizePayment($refund);

        // 5) Sale return (confirmed)
        $saleReturn = new SaleReturn([
            'status' => SaleReturn::STATUS_CONFIRMED,
            'total' => 1000,
            'return_date' => '2026-11-07',
            'return_no' => 'SR-1',
            'order_id' => 501,
        ]);
        $saleReturn->id = 701;
        $saleReturn->exists = true;
        $saleReturn->setRelation('items', collect());
        $sales->synchronizeSaleReturn($saleReturn);

        // 6) Purchase + supplier payment
        AccountingService::postOnce(
            'purchase:801:confirmed',
            'purchase',
            801,
            $this->coa[AccountingService::GL_RAW_MATERIALS],
            $this->coa[AccountingService::GL_ACCOUNTS_PAY],
            8000,
            '2026-11-01',
            'Purchase confirmed',
            'PO-801',
            'purchase'
        );
        AccountingService::postOnce(
            'purchase-payment:901:entry',
            'purchase_payment',
            901,
            $this->coa[AccountingService::GL_ACCOUNTS_PAY],
            $this->coa[AccountingService::GL_BANK],
            3000,
            '2026-11-04',
            'Supplier payment',
            'PP-901',
            'purchase_payment'
        );
        AccountingService::postOnce(
            'purchase:802:return',
            'purchase_return',
            802,
            $this->coa[AccountingService::GL_ACCOUNTS_PAY],
            $this->coa[AccountingService::GL_RAW_MATERIALS],
            500,
            '2026-11-08',
            'Purchase return',
            'PR-802',
            'purchase_return'
        );

        // 7) Expense + field transport
        AccountingService::postOnce(
            'expense:1001:entry',
            'expense',
            1001,
            $this->coa[AccountingService::GL_GENERAL_EXPENSE],
            $this->coa[AccountingService::GL_CASH],
            750,
            '2026-11-04',
            'Office expense',
            'EXP-1001',
            'expense'
        );
        AccountingService::postOnce(
            'spe:1101:entry',
            'sales_person_expense',
            1101,
            $this->coa[AccountingService::GL_TRANSPORT_EXPENSE],
            $this->coa[AccountingService::GL_CASH],
            250,
            '2026-11-04',
            'Field expense',
            'SPE-1101',
            'sales_person_expense'
        );

        // 8) Payroll accrual + payment
        AccountingService::postOnce(
            'payroll-run:1201:accrual',
            'payroll_run',
            1201,
            $this->coa[AccountingService::GL_SALARY_EXPENSE],
            $this->coa[AccountingService::GL_PAYROLL_PAYABLE],
            12000,
            '2026-11-30',
            'Payroll accrual',
            'PR-1201',
            'payroll_accrual'
        );
        AccountingService::postOnce(
            'payroll-item:1202:payment',
            'payroll_run_item',
            1202,
            $this->coa[AccountingService::GL_PAYROLL_PAYABLE],
            $this->coa[AccountingService::GL_CASH],
            12000,
            '2026-12-01',
            'Payroll payment',
            'PR-1201',
            'payroll_payment'
        );

        // 9) Manufacturing: issue RM → WIP, additional cost, FG receipt, overhead
        AccountingService::postOnce(
            'material-issue:1301:entry',
            'material_issue',
            1301,
            $this->coa[AccountingService::GL_WORK_IN_PROGRESS],
            $this->coa[AccountingService::GL_RAW_MATERIALS],
            2000,
            '2026-11-10',
            'Material issue',
            'JOB-1',
            'manufacturing_material_issue'
        );
        AccountingService::postOnce(
            'mfg-cost:1302:entry',
            'manufacturing_job_additional_cost',
            1302,
            $this->coa[AccountingService::GL_WORK_IN_PROGRESS],
            $this->coa[AccountingService::GL_CASH],
            500,
            '2026-11-10',
            'Mfg additional cost',
            'JOB-1',
            'manufacturing_additional_cost'
        );
        AccountingService::postOnce(
            'stock-ledger:1303:fg',
            'stock_ledger',
            1303,
            $this->coa[AccountingService::GL_FINISHED_GOODS],
            $this->coa[AccountingService::GL_WORK_IN_PROGRESS],
            2500,
            '2026-11-12',
            'FG receipt',
            'JOB-1',
            'manufacturing_finished_goods'
        );

        // 10) Cash → Bank transfer (banking)
        AccountingService::postOnce(
            'transfer:1401:cash-to-bank',
            'transfer',
            1401,
            $this->coa[AccountingService::GL_BANK],
            $this->coa[AccountingService::GL_CASH],
            1000,
            '2026-11-15',
            'Cash to bank',
            'TR-1401',
            'transfer'
        );

        // 11) Bounced cheque path: reverse receipt (AR restored)
        AccountingService::reverseEvent('payment:603:cheque-receipt');
        AccountingService::reverseEvent('payment:603:cheque-clearance');

        $this->assertBooksBalanced();
        $this->assertTrialBalanceEquation();
        $this->assertBalanceSheetEquation();
        $this->assertNoDuplicateActiveEventKeys();
        $this->assertExpectedTransactionTypesPresent();

        // AR remaining after sale 10000 - cash 3000 - bank 2000 - cheque reverse restores AR
        // After bounce: payments still reduce AR by 5000 cash/bank only + refund + sale return
        $ar = $this->accountNet(AccountingService::GL_ACCOUNTS_RECV);
        // Sale DR 10000; cash CR 3000; bank CR 2000; refund DR 500; sale return CR 1000; cheque receipt reversed
        $this->assertEqualsWithDelta(4500.0, $ar, 0.01, 'AR net should reflect sale less settlements/returns/refund');

        $ap = $this->accountNet(AccountingService::GL_ACCOUNTS_PAY);
        // Purchase CR 8000; payment DR 3000; return DR 500 → liability net credit 4500 → net = credit-debit = 4500 for liability as credit-heavy
        // accountNet = debit - credit for assets; for AP we expect debit-credit = 3000+500-8000 = -4500
        $this->assertEqualsWithDelta(-4500.0, $ap, 0.01);

        Artisan::call('accounting:diagnose', ['--json' => true]);
        $json = json_decode(Artisan::output(), true);
        $this->assertIsArray($json);
        $this->assertSame([], $json['unbalanced_transaction_numbers'] ?? ['missing']);
        $this->assertSame([], $json['duplicate_active_event_keys'] ?? ['missing']);
    }

    public function test_quotation_or_draft_order_does_not_post_revenue(): void
    {
        $order = new Order([
            'status' => Order::STATUS_PENDING,
            'total_amount' => 9999,
            'order_date' => '2026-11-01',
            'order_number' => 'QT-1',
        ]);
        $order->id = 999;
        $order->exists = true;
        $order->setRelation('items', collect());

        app(SalesAccountingService::class)->synchronizeOrder($order);

        $this->assertSame(0, DB::table('journal_entries')->count());
    }

    public function test_duplicate_post_once_does_not_double_count_in_trial_balance(): void
    {
        for ($i = 0; $i < 3; $i++) {
            AccountingService::postOnce(
                'expense:55:entry',
                'expense',
                55,
                $this->coa[AccountingService::GL_GENERAL_EXPENSE],
                $this->coa[AccountingService::GL_CASH],
                100.10,
                '2026-11-01',
                'Dup expense',
                'EXP-55',
                'expense'
            );
        }

        $this->assertSame(2, DB::table('journal_entries')->where('reversed', 0)->count());
        $this->assertTrialBalanceEquation();
    }

    public function test_profit_and_loss_matches_income_and_expense_nets(): void
    {
        AccountingService::postOnce(
            'order:1:sale',
            Order::class,
            1,
            $this->coa[AccountingService::GL_ACCOUNTS_RECV],
            $this->coa[AccountingService::GL_SALES_REVENUE],
            5000,
            '2026-11-01',
            'Sale',
            'ORD-1',
            'sale'
        );
        AccountingService::postOnce(
            'expense:1:entry',
            'expense',
            1,
            $this->coa[AccountingService::GL_GENERAL_EXPENSE],
            $this->coa[AccountingService::GL_CASH],
            1200,
            '2026-11-01',
            'Expense',
            'EXP-1',
            'expense'
        );

        $pl = $this->profitAndLoss();
        $this->assertEqualsWithDelta(5000.0, $pl['income'], 0.01);
        $this->assertEqualsWithDelta(1200.0, $pl['expense'], 0.01);
        $this->assertEqualsWithDelta(3800.0, $pl['net'], 0.01);
    }

    private function assertBooksBalanced(): void
    {
        $debit = (float) DB::table('journal_entries')->where('reversed', 0)->sum('debit');
        $credit = (float) DB::table('journal_entries')->where('reversed', 0)->sum('credit');
        $this->assertEqualsWithDelta($debit, $credit, 0.01, 'Total Debits must equal Total Credits');
    }

    private function assertTrialBalanceEquation(): void
    {
        $rows = $this->trialBalanceRows();
        $debit = round(array_sum(array_column($rows, 'debit')), 2);
        $credit = round(array_sum(array_column($rows, 'credit')), 2);
        $this->assertEqualsWithDelta($debit, $credit, 0.01, 'Trial Balance DR = CR');
    }

    private function assertBalanceSheetEquation(): void
    {
        // Assets = Liabilities + Equity + Net Income (closed to equity conceptually)
        $assets = 0.0;
        $liabilities = 0.0;
        $equity = 0.0;
        foreach ($this->trialBalanceRows() as $row) {
            $net = round($row['debit'] - $row['credit'], 2);
            match ($row['account_type']) {
                'asset' => $assets += $net,
                'liability' => $liabilities += -$net, // credit-normal
                'equity' => $equity += -$net,
                default => null,
            };
        }
        $pl = $this->profitAndLoss();
        $right = round($liabilities + $equity + $pl['net'], 2);
        $this->assertEqualsWithDelta(round($assets, 2), $right, 0.02, 'Assets = Liabilities + Equity + Net Income');
    }

    private function assertNoDuplicateActiveEventKeys(): void
    {
        $dupes = DB::table('accounting_postings')
            ->select('event_key', DB::raw('COUNT(*) as c'))
            ->where('reversed', 0)
            ->groupBy('event_key')
            ->havingRaw('COUNT(*) > 1')
            ->count();
        $this->assertSame(0, $dupes);
    }

    private function assertExpectedTransactionTypesPresent(): void
    {
        $types = DB::table('journal_entries')->where('reversed', 0)->distinct()->pluck('transaction_type')->all();
        foreach ([
            'sale', 'sale_cogs', 'customer_payment', 'customer_refund', 'sale_return',
            'purchase', 'purchase_payment', 'purchase_return',
            'expense', 'sales_person_expense',
            'payroll_accrual', 'payroll_payment',
            'manufacturing_material_issue', 'manufacturing_additional_cost', 'manufacturing_finished_goods',
            'transfer',
        ] as $type) {
            $this->assertContains($type, $types, "Missing journal type {$type}");
        }
    }

    /** @return list<array{gl_code:string,account_type:string,debit:float,credit:float}> */
    private function trialBalanceRows(): array
    {
        return DB::table('chart_of_accounts')
            ->join('journal_entries', 'journal_entries.chart_of_account_id', '=', 'chart_of_accounts.id')
            ->where('journal_entries.active', 1)
            ->where('journal_entries.reversed', 0)
            ->where('chart_of_accounts.active', 1)
            ->groupBy('chart_of_accounts.id', 'chart_of_accounts.gl_code', 'chart_of_accounts.account_type')
            ->selectRaw('chart_of_accounts.gl_code, chart_of_accounts.account_type, SUM(journal_entries.debit) as debit, SUM(journal_entries.credit) as credit')
            ->get()
            ->map(fn ($r) => [
                'gl_code' => $r->gl_code,
                'account_type' => $r->account_type,
                'debit' => (float) $r->debit,
                'credit' => (float) $r->credit,
            ])
            ->all();
    }

    /** @return array{income:float,expense:float,net:float} */
    private function profitAndLoss(): array
    {
        $income = 0.0;
        $expense = 0.0;
        foreach ($this->trialBalanceRows() as $row) {
            $net = $row['debit'] - $row['credit'];
            if ($row['account_type'] === 'income') {
                $income += -$net; // credit-normal
            }
            if ($row['account_type'] === 'expense') {
                $expense += $net; // debit-normal
            }
        }

        return [
            'income' => round($income, 2),
            'expense' => round($expense, 2),
            'net' => round($income - $expense, 2),
        ];
    }

    private function accountNet(string $glCode): float
    {
        $id = $this->coa[$glCode];
        $debit = (float) DB::table('journal_entries')->where('reversed', 0)->where('chart_of_account_id', $id)->sum('debit');
        $credit = (float) DB::table('journal_entries')->where('reversed', 0)->where('chart_of_account_id', $id)->sum('credit');

        return round($debit - $credit, 2);
    }
}
