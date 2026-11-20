<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use App\Services\Accounting\SourceDocumentResolver;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Concerns\BuildsPhase6Schema;
use Tests\TestCase;

/**
 * Phase 6 — Xero / QuickBooks parity.
 */
class AccountingPhase6Test extends TestCase
{
    use BuildsPhase6Schema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildPhase6Schema();
        AuditLogger::flushCache();
        \App\Models\Order::flushSchemaCache();
        \App\Models\Purchase::flushSchemaCache();
        SourceDocumentResolver::flushCache();
    }

    protected function tearDown(): void
    {
        AuditLogger::flushCache();
        \App\Models\Order::flushSchemaCache();
        \App\Models\Purchase::flushSchemaCache();
        parent::tearDown();
    }

    private function admin(): User
    {
        $user = new User(['role' => 'admin', 'role_id' => 1, 'name' => 'Audit Admin']);
        $user->id = 7;
        $user->setRelation('roleModel', new Role(['name' => 'admin', 'permissions' => ['accounting.view', 'accounting.manage']]));

        return $user;
    }

    // ---------------------------------------------------------------- 6.1 Audit trail

    public function test_audit_trail_records_create_update_delete_with_user_and_field_changes(): void
    {
        $this->actingAs($this->admin());

        $customer = Customer::query()->create(['name' => 'Nimal', 'phone' => '0771234567', 'credit_limit' => 50000]);
        $customer->update(['credit_limit' => 75000, 'tin' => '123456789']);
        $customer->delete();

        $logs = AuditLog::query()->where('auditable_type', Customer::class)->orderBy('id')->get();
        $this->assertSame(['created', 'updated', 'deleted'], $logs->pluck('event')->all());
        $this->assertSame(7, (int) $logs[1]->user_id);
        $this->assertSame('Audit Admin', $logs[1]->user_name);
        $this->assertSame('Customer Nimal', $logs[1]->document_label);

        $changes = collect($logs[1]->changes())->keyBy('field');
        $this->assertSame('50000', (string) (int) $changes['Credit limit']['old']);
        $this->assertSame('75000', (string) (int) $changes['Credit limit']['new']);
        $this->assertSame('—', $changes['Tin']['old']);
        $this->assertSame('123456789', $changes['Tin']['new']);
        $this->assertArrayNotHasKey('Updated at', $changes->all());
    }

    public function test_update_without_real_change_writes_no_audit_row(): void
    {
        $customer = Customer::query()->create(['name' => 'Kamal']);
        $before = AuditLog::query()->count();
        $customer->touch();
        $this->assertSame($before, AuditLog::query()->count());
    }

    public function test_audit_failure_never_breaks_the_business_save(): void
    {
        Schema::drop('audit_logs');
        // cache still says the table exists → write fails → must be swallowed
        $customer = Customer::query()->create(['name' => 'Saman']);
        $this->assertNotNull($customer->id);
    }

    // ---------------------------------------------------------------- helpers

    /** Issued invoice: goods 10,000 → SSCL 250 + VAT 1,845 = 12,095, posted to the ledger. */
    protected function issuedInvoice(array $customer = [], float $goods = 10000, ?string $issuedOn = null): \App\Models\Order
    {
        $cust = Customer::query()->create(array_merge(['name' => 'Perera Stores', 'business_name' => 'Perera Stores', 'phone' => '0771111111'], $customer));
        $order = \App\Models\Order::query()->create([
            'order_number' => 'ORD' . random_int(1000, 99999),
            'customer_id' => $cust->id,
            'user_id' => 7,
            'subtotal' => $goods,
            'discount_amount' => 0,
            'shipping' => 0,
            'invoice_other_expenses' => 0,
            'total_amount' => $goods,
            'status' => \App\Models\Order::STATUS_PENDING,
            'order_date' => $issuedOn ?? now()->toDateString(),
        ]);
        \App\Models\OrderItem::query()->create([
            'order_id' => $order->id, 'product_id' => 1, 'quantity' => 10, 'unit_price' => $goods / 10, 'total_price' => $goods,
        ]);
        $order->refresh()->load('customer');
        $order->invoice_number = \App\Models\Order::nextInvoiceNumber();
        $order->status = \App\Models\Order::STATUS_COMPLETED;
        $order->applyInvoiceTax();
        $order->stampInvoiceDates($issuedOn ? \Illuminate\Support\Carbon::parse($issuedOn) : null);
        $order->save();
        app(\App\Services\SalesAccountingService::class)->synchronizeOrder($order->fresh());

        return $order->fresh();
    }

    protected function pay(\App\Models\Order $order, float $amount, string $method = 'cash'): \App\Models\Payment
    {
        $payment = \App\Models\Payment::query()->create([
            'order_id' => $order->id, 'amount' => $amount, 'payment_method' => $method, 'payment_date' => now()->toDateString(),
        ]);
        app(\App\Services\SalesAccountingService::class)->synchronizePayment($payment->fresh());

        return $payment;
    }

    // ---------------------------------------------------------------- 6.2 Invoice drill-down

    public function test_invoice_status_moves_from_awaiting_to_partially_paid_to_paid_and_overdue(): void
    {
        $order = $this->issuedInvoice();
        $this->assertSame(12095.0, $order->invoice_total);
        $this->assertSame('awaiting_payment', $order->invoiceStatus());
        $this->assertSame(now()->addDays(30)->toDateString(), $order->invoiceDueDate()->toDateString());

        $this->pay($order, 5000);
        $this->assertSame('partially_paid', $order->fresh()->invoiceStatus());
        $this->pay($order, 7095);
        $this->assertSame('paid', $order->fresh()->invoiceStatus());

        $late = $this->issuedInvoice([], 2000, now()->subDays(45)->toDateString());
        $this->assertSame('overdue', $late->invoiceStatus());
        $this->assertSame(15, $late->daysOverdue());

        $terms = $this->issuedInvoice(['payment_terms_days' => 7], 1000, now()->subDays(10)->toDateString());
        $this->assertSame(7, (int) $terms->payment_terms_days);
        $this->assertSame('overdue', $terms->invoiceStatus());
    }

    public function test_invoice_page_shows_lines_tax_payments_journal_source_and_history(): void
    {
        $this->actingAs($this->admin());
        $order = $this->issuedInvoice(['tin' => '987654321']);
        $payment = $this->pay($order, 2095);

        $page = $this->get('/accounting/phase6/invoices/' . $order->id);
        $page->assertOk()
            ->assertSee($order->invoice_number)
            ->assertSee('TAX INVOICE')
            ->assertSee('12,095.00')
            ->assertSee('250.00')            // SSCL
            ->assertSee('1,845.00')          // VAT
            ->assertSee('10,000.00')         // balance due after 2,095 payment
            ->assertSee('Customer payment #' . $payment->id)
            ->assertSee('Where it came from')
            ->assertSee('Ledger agrees with the balance due')
            ->assertSee('Audit Admin');      // history: who recorded the payment

        // journal lines: AR Dr 10,000 + 2,095; Sales Cr 10,000; Tax payable Cr 2,095; Cash Dr 2,095
        $this->assertSame(10000.0, $this->glBalance(\App\Services\AccountingService::GL_ACCOUNTS_RECV));
        $this->assertSame(-10000.0, $this->glBalance(\App\Services\AccountingService::GL_SALES_REVENUE));
    }

    public function test_invoice_list_filters_by_status_and_links_to_detail(): void
    {
        $this->actingAs($this->admin());
        $open = $this->issuedInvoice(['name' => 'Open Shop', 'business_name' => 'Open Shop']);
        $late = $this->issuedInvoice(['name' => 'Late Shop', 'business_name' => 'Late Shop'], 2000, now()->subDays(60)->toDateString());
        $paid = $this->issuedInvoice(['name' => 'Paid Shop', 'business_name' => 'Paid Shop'], 1000);
        $this->pay($paid, $paid->invoice_total);

        [$o, $l, $p] = [$open->invoice_number, $late->invoice_number, $paid->invoice_number];
        $this->get('/accounting/phase6/invoices')->assertOk()->assertSee($o)->assertSee($l)->assertSee($p)
            ->assertSee('/accounting/phase6/invoices/' . $open->id, false);
        $this->get('/accounting/phase6/invoices?status=overdue')->assertOk()->assertSee($l)->assertDontSee($o)->assertDontSee($p);
        $this->get('/accounting/phase6/invoices?status=paid')->assertOk()->assertSee($p)->assertDontSee($l)->assertDontSee($o);
        $this->get('/accounting/phase6/invoices?status=awaiting_payment')->assertOk()->assertSee($o)->assertSee($l)->assertDontSee($p);
        $this->get('/accounting/phase6/invoices?q=' . urlencode($o))->assertOk()->assertSee($o)->assertDontSee($l);
    }

    public function test_journal_and_account_transactions_drill_down_to_source_document(): void
    {
        $this->actingAs($this->admin());
        $order = $this->issuedInvoice();
        $txn = \Illuminate\Support\Facades\DB::table('accounting_postings')->where('event_key', 'order:' . $order->id . ':vat')->value('transaction_number');
        $this->assertNotEmpty($txn);

        $this->get('/accounting/phase6/journal/' . $txn)->assertOk()
            ->assertSee('Invoice ' . $order->invoice_number)
            ->assertSee('Balanced')
            ->assertSee('1,845.00');

        $this->get('/accounting/journal_entry/' . $txn . '/show')->assertOk()
            ->assertSee('Open source record')
            ->assertSee('/accounting/phase6/invoices/' . $order->id, false);

        $this->get('/accounting/phase6/account-transactions?gl=' . \App\Services\AccountingService::GL_VAT_OUTPUT . '&from=' . now()->startOfYear()->toDateString() . '&to=' . now()->toDateString())
            ->assertOk()
            ->assertSee('Invoice ' . $order->invoice_number)
            ->assertSee('/accounting/phase6/invoices/' . $order->id, false);
    }

    public function test_erp_expense_journal_shows_original_expense_details(): void
    {
        (require database_path('migrations/2026_02_26_000001_create_expense_categories_table.php'))->up();
        $this->actingAs($this->admin());
        $order = $this->issuedInvoice();
        $txn = \Illuminate\Support\Facades\DB::table('accounting_postings')->where('event_key', 'order:' . $order->id . ':vat')->value('transaction_number');
        $this->assertNotEmpty($txn);
        $category = \App\Models\ExpenseCategory::query()->create(['name' => 'Office supplies']);
        $expense = \App\Models\Expense::withoutEvents(fn () => \App\Models\Expense::query()->create([
            'expense_category_id' => $category->id,
            'expense_date' => now()->toDateString(),
            'amount' => 1234.50,
            'payment_method' => 'cash',
            'reference' => 'OFFICE-112',
            'note' => 'Printer paper for the office',
            'accounting_txn_number' => $txn,
        ]));
        \Illuminate\Support\Facades\DB::table('journal_entries')->where('transaction_number', $txn)->update([
            'transaction_type' => 'expense',
            'source_type' => null,
            'source_id' => null,
        ]);

        $url = '/accounting/journal_entry/' . $expense->accounting_txn_number . '/show';
        $this->get('/accounting/transactions/expenses')->assertOk()->assertSee($url, false);
        $this->get($url)->assertOk()
            ->assertSee('Office supplies')
            ->assertSee('LKR 1,234.50')
            ->assertSee('Printer paper for the office')
            ->assertSee(route('expenses.edit', $expense->id), false);
    }

    // ---------------------------------------------------------------- 6.3 VAT engine

    protected function bill(float $net, float $vat, bool $claim = true, ?string $date = null): \App\Models\Purchase
    {
        $supplier = \App\Models\Supplier::query()->create(['name' => 'Fabric Co', 'company_name' => 'Fabric Co', 'tax_vat_no' => '114455667-7000']);
        $purchase = \App\Models\Purchase::query()->create([
            'purchase_no' => 'PUR-' . random_int(1000, 99999),
            'date' => $date ?? now()->toDateString(),
            'supplier_id' => $supplier->id,
            'purchase_type' => 'credit',
            'status' => \App\Models\Purchase::STATUS_CONFIRMED,
            'subtotal' => $net,
            'discount_amount' => 0,
            'tax_amount' => $vat,
            'shipping_cost' => 0,
            'total' => round($net + $vat, 2),
            'paid_amount' => 0,
            'input_vat_claimable' => $claim,
        ]);
        app(\App\Observers\PurchaseObserver::class)->backfill($purchase->fresh());

        return $purchase->fresh();
    }

    public function test_zero_rated_and_exempt_invoices_charge_sscl_but_no_vat(): void
    {
        $order = $this->issuedInvoice();
        $order->invoice_vat_code = \App\Support\VatTreatment::ZERO_RATED;
        $order->save(); // saving hook re-computes tax for the new treatment
        $order->refresh();
        $this->assertSame(250.0, (float) $order->invoice_sscl_amount);
        $this->assertSame(0.0, (float) $order->invoice_vat_amount);
        $this->assertSame(10250.0, $order->invoice_total);
        $this->assertSame(0.0, $order->taxInvoiceVatBreakdown()['rate']);
    }

    /** VAT and SSCL chosen separately: added on top, or already inside the item prices. */
    public function test_vat_and_sscl_can_each_be_added_on_top_or_included_in_prices(): void
    {
        $cases = [
            // [ssclIncluded, vatIncluded, goods, sscl, vat, customer pays]
            [false, false, 10000.00, 250.00, 1845.00, 12095.00],
            [true, false, 9756.10, 243.90, 1800.00, 11800.00],
            [false, true, 8474.58, 211.86, 1563.56, 10250.00],
            [true, true, 8267.88, 206.70, 1525.42, 10000.00],
        ];
        foreach ($cases as [$sIn, $vIn, $goods, $sscl, $vat, $total]) {
            $t = \App\Models\Order::computeInvoiceTax(10000, 18, $sIn, $vIn);
            $label = 'sscl ' . ($sIn ? 'incl' : 'add') . ' / vat ' . ($vIn ? 'incl' : 'add');
            $this->assertSame($goods, $t['goods'], $label);
            $this->assertSame($sscl, $t['sscl'], $label);
            $this->assertSame($vat, $t['vat'], $label);
            $this->assertSame($total, $t['inclusive'], $label);
            $this->assertEqualsWithDelta($t['goods'] * 0.025, $t['sscl'], 0.01, $label . ' SSCL = 2.5% of goods');
            $this->assertEqualsWithDelta(($t['goods'] + $t['sscl']) * 0.18, $t['vat'], 0.01, $label . ' VAT = 18% of goods + SSCL');
        }
    }

    public function test_tax_included_in_prices_posts_correctly_and_returns_split_proportionally(): void
    {
        $this->actingAs($this->admin());
        $order = $this->issuedInvoice();
        $order->invoice_sscl_inclusive = true;
        $order->invoice_vat_inclusive = true;
        $order->save();
        app(\App\Services\SalesAccountingService::class)->synchronizeOrder($order->fresh(), true);
        $order->refresh();

        $this->assertSame(10000.0, $order->invoice_total);          // customer pays only the item total
        $this->assertSame(8267.88, $order->goodsValue());
        $this->assertSame(1732.12, $order->invoice_tax_included);
        $this->assertSame(10000.0, $this->glBalance(\App\Services\AccountingService::GL_ACCOUNTS_RECV));
        $this->assertSame(-8267.88, $this->glBalance(\App\Services\AccountingService::GL_SALES_REVENUE));
        $this->assertSame(-206.7, $this->glBalance(\App\Services\AccountingService::GL_SSCL_PAYABLE));
        $this->assertSame(-1525.42, $this->glBalance(\App\Services\AccountingService::GL_VAT_OUTPUT));

        // return 1,000 of item value → 1,000 credited, of which SSCL 20.67 + VAT 152.54, revenue 826.79
        $return = \App\Models\SaleReturn::query()->create([
            'return_no' => 'SR-IN', 'order_id' => $order->id, 'customer_id' => $order->customer_id,
            'return_date' => now()->toDateString(), 'status' => \App\Models\SaleReturn::STATUS_CONFIRMED,
            'subtotal' => 1000, 'total' => 1000,
        ]);
        app(\App\Services\SalesAccountingService::class)->synchronizeSaleReturn($return->fresh());
        $this->assertSame(9000.0, (float) $order->fresh()->due_amount);
        $this->assertSame(9000.0, $this->glBalance(\App\Services\AccountingService::GL_ACCOUNTS_RECV));
        $this->assertSame(826.79, $this->glBalance(\App\Services\AccountingService::GL_SALES_RETURNS));
        $this->assertSame(-186.03, $this->glBalance(\App\Services\AccountingService::GL_SSCL_PAYABLE));
        $this->assertSame(-1372.88, $this->glBalance(\App\Services\AccountingService::GL_VAT_OUTPUT));

        $r = \App\Services\Accounting\VatReturnService::build(now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString());
        $this->assertSame(8267.88, $r['sales']['goods']);
        $this->assertSame($r['output_vat'], $r['ledger']['vat_output']);
        $this->assertSame($r['sscl_payable'], $r['ledger']['sscl']);

        // printed invoices still add up
        $items = collect([['reference' => 'A', 'description' => 'A', 'quantity' => 10.0, 'unit_price' => 1000.0, 'amount' => 10000.0]]);
        $order->setRelation('customer', new Customer(['tin' => '123456789']));
        $tax = $order->invoicePresentation($items);
        $this->assertSame(8267.88, round($tax['lines_total'] + $tax['rounding'], 2));   // tax invoice lines excl. tax
        $order->setRelation('customer', new Customer(['tin' => null]));
        $normal = $order->invoicePresentation($items);
        $this->assertSame(10000.0, round($normal['lines_total'] + $normal['sscl'] + $normal['rounding'], 2));
    }

    public function test_issue_invoice_form_maps_separate_vat_and_sscl_choices(): void
    {
        $this->actingAs($this->admin());
        $order = $this->issuedInvoice();
        $this->put(route('orders.update_invoice', $order->id), [
            'discount_type' => 'fixed', 'discount_value' => 0, 'shipping' => 0, 'invoice_other_expenses' => 0,
            'invoice_vat_mode' => 'included', 'invoice_sscl_mode' => 'add',
        ]);
        $order->refresh();
        $this->assertTrue($order->invoice_vat_inclusive);
        $this->assertFalse($order->invoice_sscl_inclusive);
        $this->assertSame(\App\Support\VatTreatment::STANDARD, $order->invoice_vat_code);
        $this->assertSame(10250.0, $order->invoice_total);

        $this->put(route('orders.update_invoice', $order->id), [
            'discount_type' => 'fixed', 'discount_value' => 0, 'shipping' => 0, 'invoice_other_expenses' => 0,
            'invoice_vat_mode' => 'exempt', 'invoice_sscl_mode' => 'included',
        ]);
        $order->refresh();
        $this->assertSame(\App\Support\VatTreatment::EXEMPT, $order->invoice_vat_code);
        $this->assertSame(0.0, (float) $order->invoice_vat_amount);
        $this->assertSame(10000.0, $order->invoice_total);
    }

    public function test_sales_tax_posts_to_separate_vat_and_sscl_accounts_and_returns_reverse_their_share(): void
    {
        $order = $this->issuedInvoice();
        $this->assertSame(-1845.0, $this->glBalance(\App\Services\AccountingService::GL_VAT_OUTPUT));
        $this->assertSame(-250.0, $this->glBalance(\App\Services\AccountingService::GL_SSCL_PAYABLE));
        $this->assertSame(0.0, $this->glBalance(\App\Services\AccountingService::GL_TAX_PAYABLE));

        $return = \App\Models\SaleReturn::query()->create([
            'return_no' => 'SR-1', 'order_id' => $order->id, 'customer_id' => $order->customer_id,
            'return_date' => now()->toDateString(), 'status' => \App\Models\SaleReturn::STATUS_CONFIRMED,
            'subtotal' => 1000, 'total' => 1000,
        ]);
        app(\App\Services\SalesAccountingService::class)->synchronizeSaleReturn($return->fresh());

        // 1,000 goods returned → billed 1,209.50 → SSCL 25.00 + VAT 184.50
        $this->assertSame(-1660.5, $this->glBalance(\App\Services\AccountingService::GL_VAT_OUTPUT));
        $this->assertSame(-225.0, $this->glBalance(\App\Services\AccountingService::GL_SSCL_PAYABLE));
        $this->assertSame(10885.5, $order->fresh()->due_amount);
        $this->assertSame(10885.5, $this->glBalance(\App\Services\AccountingService::GL_ACCOUNTS_RECV));
    }

    public function test_claimable_bill_posts_input_vat_and_purchase_return_reverses_it(): void
    {
        $bill = $this->bill(50000, 9000);
        $this->assertSame(9000.0, $this->glBalance(\App\Services\AccountingService::GL_VAT_INPUT));
        $this->assertSame(50000.0, $this->glBalance(\App\Services\AccountingService::GL_RAW_MATERIALS));
        $this->assertSame(-59000.0, $this->glBalance(\App\Services\AccountingService::GL_ACCOUNTS_PAY));

        $notClaimed = $this->bill(1000, 180, false);
        $this->assertSame(9000.0, $this->glBalance(\App\Services\AccountingService::GL_VAT_INPUT));
        $this->assertSame(51180.0, $this->glBalance(\App\Services\AccountingService::GL_RAW_MATERIALS));

        $return = \App\Models\PurchaseReturn::query()->create([
            'return_no' => 'PR-1', 'purchase_id' => $bill->id, 'supplier_id' => $bill->supplier_id,
            'return_date' => now()->toDateString(), 'status' => \App\Models\PurchaseReturn::STATUS_CONFIRMED,
            'subtotal' => 5000, 'total' => 5000,
        ]);
        \App\Services\PurchaseReturnService::postInputVatReversal($return->fresh('purchase'));
        $this->assertSame(8100.0, $this->glBalance(\App\Services\AccountingService::GL_VAT_INPUT));
        $this->assertSame(5900.0, $bill->fresh()->total_returned_billed);
        $this->assertSame(53100.0, $bill->fresh()->net_total);
        $this->assertSame(0.0, $notClaimed->inputVatAmount());
    }

    public function test_vat_return_totals_drill_down_and_agree_with_the_ledger(): void
    {
        $this->actingAs($this->admin());
        $a = $this->issuedInvoice(['tin' => '111222333']);          // 10,000 → SSCL 250, VAT 1,845
        $b = $this->issuedInvoice([], 4000);                         // 4,000 → SSCL 100, VAT 738
        $b->invoice_vat_code = \App\Support\VatTreatment::EXEMPT;
        $b->save();
        app(\App\Services\SalesAccountingService::class)->synchronizeOrder($b->fresh(), true);
        $this->bill(20000, 3600);

        $r = \App\Services\Accounting\VatReturnService::build(now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString());
        $this->assertSame(1845.0, $r['output_vat']);
        $this->assertSame(3600.0, $r['input_vat']);
        $this->assertSame(-1755.0, $r['net_vat']);
        $this->assertSame(350.0, $r['sscl_payable']);
        $this->assertSame(4000.0, $r['by_treatment']['exempt']['goods']);
        $this->assertSame($r['output_vat'], $r['ledger']['vat_output']);
        $this->assertSame($r['input_vat'], $r['ledger']['vat_input']);
        $this->assertSame($r['sscl_payable'], $r['ledger']['sscl']);

        $q = 'from=' . now()->startOfMonth()->toDateString() . '&to=' . now()->endOfMonth()->toDateString();
        $this->get('/accounting/phase6/vat-return?' . $q)->assertOk()->assertSee('1,845.00')->assertSee('Net VAT refundable');
        $this->get('/accounting/phase6/vat-return/sales?' . $q)->assertOk()->assertSee($a->invoice_number)->assertSee('111222333');
        $this->get('/accounting/phase6/vat-return/bills?' . $q)->assertOk()->assertSee('3,600.00');
        $csv = $this->get('/accounting/phase6/vat-return?' . $q . '&export=csv');
        $this->assertStringContainsString('NET VAT PAYABLE', $csv->streamedContent());
        $this->get('/accounting/phase6/tax-invoice-register?' . $q)->assertOk()->assertSee($a->invoice_number)->assertSee('No gaps');
    }

    // ---------------------------------------------------------------- 6.4 Receivables

    public function test_adjustment_credit_note_reduces_due_posts_tax_reversal_and_can_be_voided(): void
    {
        $this->actingAs($this->admin());
        $order = $this->issuedInvoice();

        $res = $this->post('/accounting/phase6/credit-notes', ['order_id' => $order->id, 'goods_amount' => 1000, 'reason' => 'Price agreed']);
        $res->assertRedirect();
        $cn = \App\Models\CreditNote::query()->firstOrFail();
        $this->assertSame(25.0, $cn->sscl_amount);
        $this->assertSame(184.5, $cn->vat_amount);
        $this->assertSame(1209.5, $cn->total);
        $this->assertSame(10885.5, (float) $order->fresh()->due_amount);
        $this->assertSame(10885.5, $this->glBalance(\App\Services\AccountingService::GL_ACCOUNTS_RECV));
        $this->assertSame(1000.0, $this->glBalance(\App\Services\AccountingService::GL_SALES_RETURNS));
        $this->assertSame(-1660.5, $this->glBalance(\App\Services\AccountingService::GL_VAT_OUTPUT));
        $this->assertSame(-225.0, $this->glBalance(\App\Services\AccountingService::GL_SSCL_PAYABLE));

        $r = \App\Services\Accounting\VatReturnService::build(now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString());
        $this->assertSame(1660.5, $r['output_vat']);
        $this->assertSame($r['output_vat'], $r['ledger']['vat_output']);

        // cannot credit more than what is left
        $this->post('/accounting/phase6/credit-notes', ['order_id' => $order->id, 'goods_amount' => 9500, 'reason' => 'Too much'])
            ->assertSessionHasErrors('goods_amount');

        $this->get('/accounting/phase6/credit-notes')->assertOk()->assertSee($cn->credit_note_number);
        $this->get('/accounting/phase6/credit-notes/create?order_id=' . $order->id)->assertOk()->assertSee('10,885.50');
        $this->get('/accounting/phase6/credit-notes/' . $cn->id . '/pdf')->assertOk();
        $this->get('/accounting/phase6/invoices/' . $order->id)->assertOk()->assertSee($cn->credit_note_number)->assertSee('Ledger agrees');

        $this->post('/accounting/phase6/credit-notes/' . $cn->id . '/void', ['reason' => 'Raised by mistake'])->assertRedirect();
        $this->assertSame(12095.0, (float) $order->fresh()->due_amount);
        $this->assertSame(12095.0, $this->glBalance(\App\Services\AccountingService::GL_ACCOUNTS_RECV));
    }

    public function test_sale_return_gets_a_numbered_credit_note_document(): void
    {
        $order = $this->issuedInvoice();
        $return = \App\Models\SaleReturn::query()->create([
            'return_no' => 'SR-77', 'order_id' => $order->id, 'customer_id' => $order->customer_id,
            'return_date' => now()->toDateString(), 'status' => \App\Models\SaleReturn::STATUS_CONFIRMED,
            'subtotal' => 2000, 'total' => 2000,
        ]);
        $cn = \App\Models\CreditNote::query()->where('sale_return_id', $return->id)->first();
        $this->assertNotNull($cn);
        $this->assertSame('return', $cn->type);
        $this->assertSame(2419.0, $cn->total);
        // document only — due is reduced once (by the return), not twice
        $this->assertSame(round(12095 - 2419, 2), (float) $order->fresh()->due_amount);
    }

    public function test_void_invoice_reverses_journals_returns_stock_and_is_blocked_after_payment(): void
    {
        $this->actingAs($this->admin());
        $order = $this->issuedInvoice();
        \App\Models\StockLedger::query()->create([
            'product_id' => 1, 'channel' => 'wholesale', 'transaction_type' => 'sale', 'quantity' => -10,
            'reference_type' => \App\Models\Order::class, 'reference_id' => $order->id,
        ]);

        $this->post('/accounting/phase6/invoices/' . $order->id . '/void', ['reason' => 'Wrong customer'])->assertRedirect();
        $order->refresh();
        $this->assertSame('voided', $order->invoiceStatus());
        $this->assertSame(0.0, (float) $order->due_amount);
        $this->assertNotEmpty($order->invoice_number);
        $this->assertSame(0.0, $this->glBalance(\App\Services\AccountingService::GL_ACCOUNTS_RECV));
        $this->assertSame(0.0, $this->glBalance(\App\Services\AccountingService::GL_VAT_OUTPUT));
        $this->assertSame(0.0, (float) \App\Models\StockLedger::query()->where('product_id', 1)->sum('quantity'));
        $this->assertTrue(\App\Models\AuditLog::query()->where('event', 'invoice_voided')->exists());
        $this->get('/accounting/phase6/invoices?status=voided')->assertOk()->assertSee($order->invoice_number);

        $paid = $this->issuedInvoice();
        $this->pay($paid, 100);
        $this->post('/accounting/phase6/invoices/' . $paid->id . '/void', ['reason' => 'x'])->assertSessionHasErrors('reason');
        $this->assertSame('partially_paid', $paid->fresh()->invoiceStatus());
    }

    public function test_overdue_page_and_sms_reminder(): void
    {
        $this->actingAs($this->admin());
        $late = $this->issuedInvoice(['phone' => '0779999999'], 3000, now()->subDays(75)->toDateString());

        $sms = \Mockery::mock(\App\Services\TextLkSmsService::class);
        $sms->shouldReceive('sendSms')->once()->withArgs(fn ($to, $msg) => $to === '0779999999' && str_contains($msg, $late->invoice_number))->andReturn(true);
        $this->app->instance(\App\Services\TextLkSmsService::class, $sms);

        $this->get('/accounting/phase6/receivables/overdue')->assertOk()->assertSee($late->invoice_number)->assertSee('61–90 days');
        $this->post('/accounting/phase6/invoices/' . $late->id . '/reminder')->assertRedirect()->assertSessionHas('status');
        $this->assertNotNull($late->fresh()->last_reminder_at);
        $this->assertTrue(\App\Models\AuditLog::query()->where('event', 'reminder_sent')->where('auditable_id', $late->id)->exists());
    }

    // ---------------------------------------------------------------- 6.5 Payables

    public function test_bill_due_date_status_detail_and_payment_run(): void
    {
        $this->actingAs($this->admin());
        $bill = $this->bill(10000, 1800, true, now()->subDays(40)->toDateString());
        $this->assertSame(now()->subDays(10)->toDateString(), $bill->dueDate()->toDateString());
        $this->assertSame('overdue', $bill->billStatus());
        $this->assertSame(10, $bill->daysOverdue());

        $terms = \App\Models\Supplier::query()->create(['name' => 'Thread Ltd', 'payment_terms_days' => 60]);
        $soon = \App\Models\Purchase::query()->create([
            'purchase_no' => 'PUR-SOON', 'date' => now()->toDateString(), 'supplier_id' => $terms->id, 'purchase_type' => 'credit',
            'status' => \App\Models\Purchase::STATUS_CONFIRMED, 'subtotal' => 500, 'tax_amount' => 0, 'total' => 500, 'paid_amount' => 0,
        ]);
        $this->assertSame(now()->addDays(60)->toDateString(), $soon->fresh()->dueDate()->toDateString());
        $this->assertSame('awaiting_payment', $soon->fresh()->billStatus());

        $this->get('/accounting/phase6/bills')->assertOk()->assertSee($bill->purchase_no)->assertSee('PUR-SOON');
        $this->get('/accounting/phase6/bills?status=overdue')->assertOk()->assertSee($bill->purchase_no)->assertDontSee('PUR-SOON');
        $this->get('/accounting/phase6/bills/' . $bill->id)->assertOk()
            ->assertSee('Input VAT claimed: Rs 1,800.00')
            ->assertSee('Ledger agrees with the balance')
            ->assertSee('Overdue');
        $run = $this->get('/accounting/phase6/bills/payment-run?due_by=' . now()->toDateString());
        $run->assertOk()->assertSee($bill->purchase_no)->assertDontSee('PUR-SOON');
        $csv = $this->get('/accounting/phase6/bills/payment-run?due_by=' . now()->toDateString() . '&export=csv');
        $this->assertStringContainsString('11800.00', $csv->streamedContent());
    }

    public function test_bill_approval_when_required(): void
    {
        config(['dermas.require_bill_approval' => true]);
        $this->actingAs($this->admin());
        $bill = $this->bill(1000, 0, false);
        $this->assertSame('awaiting_approval', $bill->billStatus());
        $this->post('/accounting/phase6/bills/' . $bill->id . '/approve')->assertRedirect();
        $this->assertNotNull($bill->fresh()->approved_at);
        $this->assertSame('awaiting_payment', $bill->fresh()->billStatus());
        $this->assertTrue(\App\Models\AuditLog::query()->where('event', 'bill_approved')->exists());
    }

    // ---------------------------------------------------------------- 6.6 Controls

    public function test_find_and_recode_moves_the_amount_with_a_reclassification_journal(): void
    {
        $this->actingAs($this->admin());
        $this->bill(5000, 0, false);   // Dr Raw materials 5,000 / Cr AP
        $line = \Illuminate\Support\Facades\DB::table('journal_entries')
            ->where('chart_of_account_id', $this->coa[\App\Services\AccountingService::GL_RAW_MATERIALS])->first();

        $q = 'account_id=' . $line->chart_of_account_id . '&from=' . now()->startOfYear()->toDateString() . '&to=' . now()->toDateString();
        $this->get('/accounting/phase6/recode?' . $q)->assertOk()->assertSee($line->transaction_number);

        $this->post('/accounting/phase6/recode', [
            'line_ids' => [$line->id],
            'target_account_id' => $this->coa[\App\Services\AccountingService::GL_GENERAL_EXPENSE],
            'reason' => 'Was consumables, not stock',
        ])->assertRedirect()->assertSessionHas('status', '1 line(s) recoded.');

        $this->assertSame(0.0, $this->glBalance(\App\Services\AccountingService::GL_RAW_MATERIALS));
        $this->assertSame(5000.0, $this->glBalance(\App\Services\AccountingService::GL_GENERAL_EXPENSE));
        $this->assertSame(-5000.0, $this->glBalance(\App\Services\AccountingService::GL_ACCOUNTS_PAY));
        $this->assertTrue(\App\Models\AuditLog::query()->where('event', 'recoded')->where('note', 'Was consumables, not stock')->exists());

        // second attempt is skipped, not doubled
        $this->post('/accounting/phase6/recode', [
            'line_ids' => [$line->id],
            'target_account_id' => $this->coa[\App\Services\AccountingService::GL_GENERAL_EXPENSE],
            'reason' => 'again',
        ])->assertSessionHas('status', '0 line(s) recoded.');
        $this->assertSame(5000.0, $this->glBalance(\App\Services\AccountingService::GL_GENERAL_EXPENSE));
    }

    public function test_global_search_finds_invoices_by_number_and_amount_and_customers_by_phone(): void
    {
        $this->actingAs($this->admin());
        $order = $this->issuedInvoice(['name' => 'Search Shop', 'business_name' => 'Search Shop', 'phone' => '0712345678']);

        $this->get('/accounting/phase6/search?q=' . urlencode($order->invoice_number))->assertOk()->assertSee('/accounting/phase6/invoices/' . $order->id, false);
        $this->get('/accounting/phase6/search?q=12095')->assertOk()->assertSee($order->invoice_number);
        $this->get('/accounting/phase6/search?q=0712345678')->assertOk()->assertSee('Search Shop');
        $this->get('/accounting/phase6/search?q=zzzz-none')->assertOk()->assertSee('Nothing found');
    }

    public function test_strict_permissions_block_actions_without_the_specific_grant(): void
    {
        config(['dermas.strict_accounting_permissions' => true]);
        $order = $this->issuedInvoice();

        $manager = new User(['role' => 'staff', 'role_id' => 2, 'name' => 'Clerk']);
        $manager->id = 8;
        $manager->setRelation('roleModel', new Role(['name' => 'staff', 'permissions' => ['accounting.view', 'accounting.manage']]));
        $this->actingAs($manager);
        $this->post('/accounting/phase6/invoices/' . $order->id . '/void', ['reason' => 'x'])->assertForbidden();
        $this->get('/accounting/phase6/vat-return')->assertForbidden();

        $granted = new User(['role' => 'staff', 'role_id' => 2, 'name' => 'Accountant']);
        $granted->id = 9;
        $granted->setRelation('roleModel', new Role(['name' => 'staff', 'permissions' => ['accounting.view', 'accounting.manage', 'accounting.invoices.void', 'accounting.vat.view']]));
        $this->actingAs($granted);
        $this->get('/accounting/phase6/vat-return')->assertOk();
        $this->post('/accounting/phase6/invoices/' . $order->id . '/void', ['reason' => 'Wrong'])->assertRedirect();
        $this->assertSame('voided', $order->fresh()->invoiceStatus());

        // non-strict (default): "Manage Accounting" is enough
        config(['dermas.strict_accounting_permissions' => false]);
        $this->actingAs($manager);
        $this->get('/accounting/phase6/vat-return')->assertOk();
    }

    public function test_audit_trail_page_lists_filters_and_exports(): void
    {
        $this->actingAs($this->admin());
        Customer::query()->create(['name' => 'Ruwan']);

        $this->get('/accounting/phase6/audit-trail')->assertOk()->assertSee('Customer Ruwan');
        $this->get('/accounting/phase6/audit-trail?type=customer&event=created')->assertOk()->assertSee('Customer Ruwan');
        $this->get('/accounting/phase6/audit-trail?type=bill')->assertOk()->assertDontSee('Customer Ruwan');

        $csv = $this->get('/accounting/phase6/audit-trail?export=csv');
        $csv->assertOk();
        $this->assertStringContainsString('Customer Ruwan', $csv->streamedContent());

        $id = Customer::query()->value('id');
        $this->get('/accounting/phase6/audit-trail/document/customer/' . $id)->assertOk()->assertSee('Created');
    }
}
