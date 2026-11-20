<?php

namespace Tests\Feature;

use App\Models\Accounting\BankStatementImport;
use App\Models\Accounting\BankStatementLine;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\SaleReturn;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Accounting\BankAccountService;
use App\Services\Accounting\ReceivablesService;
use App\Services\AccountingService;
use App\Services\Audit\AuditLogger;
use App\Services\SalesAccountingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Concerns\BuildsPhase6Schema;
use Tests\TestCase;

/**
 * Opens every Phase 6 / 7 / Banking page with realistic data — catches runtime errors in views and controllers.
 */
class AccountingUiSmokeTest extends TestCase
{
    use BuildsPhase6Schema;

    private Order $order;
    private Purchase $bill;
    private object $bank;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildPhase6Schema();
        AuditLogger::flushCache();
        Order::flushSchemaCache();
        Purchase::flushSchemaCache();
        $user = new User(['role' => 'admin', 'role_id' => 1, 'name' => 'Smoke Admin']);
        $user->id = 7;
        $user->setRelation('roleModel', new Role(['name' => 'admin', 'permissions' => ['accounting.view', 'accounting.manage']]));
        $this->actingAs($user);
        $this->recurringTables();
        $this->seedData();
    }

    protected function tearDown(): void
    {
        AuditLogger::flushCache();
        Order::flushSchemaCache();
        Purchase::flushSchemaCache();
        parent::tearDown();
    }

    private function recurringTables(): void
    {
        foreach (['accounting_recurring_runs', 'accounting_recurring_templates', 'accounting_manual_journals'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::create('accounting_recurring_templates', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('business_id')->default(0);
            $t->string('name');
            $t->string('frequency')->default('monthly');
            $t->string('document_type')->nullable();
            $t->date('starts_on')->nullable();
            $t->date('ends_on')->nullable();
            $t->date('next_run_on')->nullable();
            $t->string('status')->default('active');
            $t->string('execution_mode')->default('draft');
            $t->json('payload')->nullable();
            $t->unsignedInteger('occurrence_count')->default(0);
            $t->unsignedInteger('max_occurrences')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
        });
        Schema::create('accounting_recurring_runs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('template_id');
            $t->date('occurrence_date');
            $t->string('status')->default('created');
            $t->unsignedBigInteger('manual_journal_id')->nullable();
            $t->string('message', 500)->nullable();
            $t->string('generated_type')->nullable();
            $t->unsignedBigInteger('generated_id')->nullable();
            $t->string('execution_key')->nullable();
            $t->timestamps();
        });
        Schema::create('accounting_manual_journals', function (Blueprint $t) {
            $t->id();
            $t->string('journal_number')->nullable();
            $t->date('journal_date')->nullable();
            $t->string('status')->default('draft');
            $t->timestamps();
        });
    }

    private function seedData(): void
    {
        $date = now()->startOfMonth()->addDays(2)->toDateString();
        $cust = Customer::query()->create(['name' => 'Smoke Shop', 'business_name' => 'Smoke Shop', 'phone' => '0771234567', 'tin' => '123456789', 'credit_limit' => 5000]);
        $o = Order::query()->create(['order_number' => 'ORD-S1', 'customer_id' => $cust->id, 'user_id' => 7, 'subtotal' => 10000, 'discount_amount' => 500,
            'shipping' => 250, 'total_amount' => 9750, 'status' => Order::STATUS_PENDING, 'order_date' => $date]);
        OrderItem::query()->create(['order_id' => $o->id, 'product_id' => 1, 'quantity' => 10, 'unit_price' => 1000, 'total_price' => 10000]);
        $o->refresh()->load('customer');
        $o->invoice_number = Order::nextInvoiceNumber();
        $o->status = Order::STATUS_COMPLETED;
        $o->applyInvoiceTax();
        $o->stampInvoiceDates(now()->subDays(45));
        $o->save();
        app(SalesAccountingService::class)->synchronizeOrder($o->fresh());
        $p = Payment::query()->create(['order_id' => $o->id, 'amount' => 3000, 'payment_method' => 'bank_transfer', 'payment_date' => $date, 'reference_number' => 'R-1']);
        app(SalesAccountingService::class)->synchronizePayment($p->fresh());
        $sr = SaleReturn::query()->create(['return_no' => 'SR-S1', 'order_id' => $o->id, 'customer_id' => $cust->id, 'return_date' => $date, 'status' => SaleReturn::STATUS_CONFIRMED, 'subtotal' => 1000, 'total' => 1000]);
        app(SalesAccountingService::class)->synchronizeSaleReturn($sr->fresh());
        app(ReceivablesService::class)->issueAdjustmentCreditNote($o->fresh(), 500, 'Goodwill');
        $this->order = $o->fresh();

        $sup = Supplier::query()->create(['name' => 'Smoke Mills', 'company_name' => 'Smoke Mills', 'tax_vat_no' => '114455667', 'bank_account_no' => '0012345678', 'bank_name' => 'BOC', 'bank_code' => '7010', 'branch_code' => '001']);
        $b = Purchase::query()->create(['purchase_no' => 'PUR-S1', 'date' => now()->subDays(40)->toDateString(), 'supplier_id' => $sup->id, 'purchase_type' => 'credit',
            'status' => Purchase::STATUS_CONFIRMED, 'subtotal' => 20000, 'tax_amount' => 3600, 'total' => 23600, 'paid_amount' => 0, 'input_vat_claimable' => true, 'reference_no' => 'SM-77', 'created_by' => 7]);
        app(\App\Observers\PurchaseObserver::class)->backfill($b->fresh());
        $this->bill = $b->fresh();
        $runs = app(\App\Services\Accounting\PaymentRunService::class);
        $run = $runs->create([$b->id], now()->toDateString(), $this->coa[AccountingService::GL_BANK], 'Smoke run', 7);
        $runs->approve($run->id, 7);

        $this->bank = BankAccountService::create(['bank_name' => 'Commercial Bank', 'display_name' => 'ComBank Main', 'account_type' => 'current', 'account_number' => '8001234566789',
            'ledger_mode' => 'existing', 'chart_of_account_id' => $this->coa[AccountingService::GL_BANK]], 7);
        $imp = BankStatementImport::query()->create(['chart_of_account_id' => $this->coa[AccountingService::GL_BANK], 'status' => 'imported', 'row_count' => 2, 'closing_balance' => 2950, 'statement_to' => $date, 'original_filename' => 'nov.csv']);
        BankStatementLine::query()->create(['bank_statement_import_id' => $imp->id, 'txn_date' => $date, 'description' => 'CEFT SMOKE SHOP', 'credit' => 3000, 'debit' => 0, 'amount' => 3000, 'fingerprint' => 'a']);
        BankStatementLine::query()->create(['bank_statement_import_id' => $imp->id, 'txn_date' => $date, 'description' => 'SMS FEE', 'credit' => 0, 'debit' => 50, 'amount' => -50, 'fingerprint' => 'b']);
        $ids = DB::table('journal_entries')->where('chart_of_account_id', $this->coa[AccountingService::GL_BANK])->pluck('id')->all();
        BankAccountService::finishReconciliation(BankAccountService::find($this->bank->id), $date, 3000, $ids, 'smoke', 7);

        $tid = DB::table('accounting_recurring_templates')->insertGetId(['name' => 'Monthly rent', 'document_type' => 'bill', 'payload' => '{}', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('accounting_recurring_runs')->insert(['template_id' => $tid, 'occurrence_date' => $date, 'status' => 'failed', 'message' => 'Bill amount must be positive.', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('accounting_recurring_runs')->insert(['template_id' => $tid, 'occurrence_date' => now()->subMonth()->toDateString(), 'status' => 'created', 'generated_type' => 'purchase', 'generated_id' => $b->id, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('acct_reminder_logs')->insert(['order_id' => $o->id, 'customer_id' => $cust->id, 'recipient' => '0771234567', 'message' => 'Dear…', 'status' => 'sent', 'trigger' => 'manual', 'days_overdue' => 15, 'created_at' => now()]);
    }

    public function test_every_accounting_page_renders(): void
    {
        $o = $this->order->id;
        $b = $this->bill->id;
        $bank = $this->bank->id;
        $ledger = $this->coa[AccountingService::GL_BANK];
        $txn = DB::table('journal_entries')->value('transaction_number');
        $cn = DB::table('credit_notes')->where('type', 'adjustment')->value('id');
        $run = DB::table('acct_payment_runs')->value('id');
        $rec = DB::table('bank_reconciliations')->value('id');
        $line = BankStatementLine::query()->where('match_status', 'unmatched')->value('id');
        $q = 'from=' . now()->startOfMonth()->toDateString() . '&to=' . now()->endOfMonth()->toDateString();

        $urls = [
            '/accounting/phase6/audit-trail', '/accounting/phase6/audit-trail?type=invoice&event=created', "/accounting/phase6/audit-trail/document/invoice/{$o}",
            '/accounting/phase6/invoices', '/accounting/phase6/invoices?status=draft', '/accounting/phase6/invoices?status=awaiting_payment',
            '/accounting/phase6/invoices?status=overdue', '/accounting/phase6/invoices?status=paid', '/accounting/phase6/invoices?status=voided',
            "/accounting/phase6/invoices/{$o}", "/accounting/phase6/journal/{$txn}", "/accounting/phase6/account-transactions?account_id={$ledger}",
            '/accounting/phase6/account-transactions', "/accounting/phase6/vat-return?{$q}", "/accounting/phase6/vat-return/sales?{$q}",
            "/accounting/phase6/vat-return/returns?{$q}", "/accounting/phase6/vat-return/credit_notes?{$q}", "/accounting/phase6/vat-return/bills?{$q}",
            "/accounting/phase6/vat-return/bill_returns?{$q}", "/accounting/phase6/tax-invoice-register?{$q}",
            '/accounting/phase6/credit-notes', '/accounting/phase6/credit-notes?type=return', "/accounting/phase6/credit-notes/create?order_id={$o}",
            "/accounting/phase6/credit-notes/{$cn}/pdf", '/accounting/phase6/receivables/overdue',
            '/accounting/phase6/bills', '/accounting/phase6/bills?status=unpaid', '/accounting/phase6/bills?status=overdue', '/accounting/phase6/bills?status=paid',
            "/accounting/phase6/bills/{$b}", '/accounting/phase6/bills/payment-run', "/accounting/phase6/recode?account_id={$ledger}",
            '/accounting/phase6/recode', '/accounting/phase6/search?q=Smoke', '/accounting/phase6/search?q=9750',
            '/accounting/phase7/reconcile', "/accounting/phase7/reconcile?account_id={$ledger}&line={$line}", '/accounting/phase7/payment-runs',
            "/accounting/phase7/payment-runs/{$run}", "/accounting/phase7/payment-runs/{$run}/export?format=generic_csv",
            '/accounting/phase7/close', '/accounting/phase7/close?period=' . now()->format('Y-m'), '/accounting/phase7/reminders',
            '/accounting/phase7/reminders?status=sent', '/accounting/phase7/recurring-queue',
            '/accounting/banking', '/accounting/banking?archived=1', '/accounting/banking/create',
            "/accounting/banking/{$bank}", "/accounting/banking/{$bank}?tab=import", "/accounting/banking/{$bank}?tab=reconcile",
            "/accounting/banking/{$bank}?tab=reconcile&statement_date=" . now()->toDateString() . '&statement_balance=2950',
            "/accounting/banking/{$bank}?tab=history", "/accounting/banking/{$bank}?tab=settings", "/accounting/banking/{$bank}/reconciliations/{$rec}",
        ];

        $failures = [];
        foreach ($urls as $url) {
            $res = $this->get($url);
            if ($res->getStatusCode() !== 200) {
                $msg = $res->exception ? $res->exception->getMessage() : substr(strip_tags((string) $res->getContent()), 0, 200);
                $failures[] = $url . ' → ' . $res->getStatusCode() . ' ' . $msg;
            }
        }
        $this->assertSame([], $failures, implode("\n", $failures));
    }
}
