<?php

namespace Tests\Feature;

use App\Models\Accounting\BankStatementImport;
use App\Models\Accounting\BankStatementLine;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Accounting\BankReconciliationService;
use App\Services\Accounting\MonthEndCloseService;
use App\Services\Accounting\Ocr\DocumentTextExtractor;
use App\Services\Accounting\Ocr\InvoiceTextParser;
use App\Services\Accounting\PaymentRunService;
use App\Services\AccountingService;
use App\Services\Audit\AuditLogger;
use App\Services\SalesAccountingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsPhase6Schema;
use Tests\TestCase;

/**
 * Phase 7 — reconciliation workspace, payment runs, month-end close, OCR, reminders.
 */
class AccountingPhase7Test extends TestCase
{
    use BuildsPhase6Schema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildPhase6Schema();
        AuditLogger::flushCache();
        Order::flushSchemaCache();
        Purchase::flushSchemaCache();
        \App\Services\Accounting\SourceDocumentResolver::flushCache();
        $this->actingAs($this->admin());
    }

    protected function tearDown(): void
    {
        AuditLogger::flushCache();
        Order::flushSchemaCache();
        Purchase::flushSchemaCache();
        parent::tearDown();
    }

    private function admin(int $id = 7, string $name = 'Acct Admin'): User
    {
        $user = new User(['role' => 'admin', 'role_id' => 1, 'name' => $name]);
        $user->id = $id;
        $user->setRelation('roleModel', new Role(['name' => 'admin', 'permissions' => ['accounting.view', 'accounting.manage']]));

        return $user;
    }

    private function bankId(): int
    {
        return $this->coa[AccountingService::GL_BANK];
    }

    private function invoiceWithBankPayment(float $amount, string $date): Payment
    {
        $c = Customer::query()->create(['name' => 'Shop ' . $amount, 'business_name' => 'Shop ' . $amount]);
        $o = Order::query()->create(['order_number' => 'O' . random_int(1000, 99999), 'customer_id' => $c->id, 'user_id' => 7,
            'subtotal' => $amount, 'total_amount' => $amount, 'status' => Order::STATUS_COMPLETED, 'invoice_number' => 'INV' . random_int(1000, 99999), 'order_date' => $date]);
        app(SalesAccountingService::class)->synchronizeOrder($o->fresh());
        $p = Payment::query()->create(['order_id' => $o->id, 'amount' => $amount, 'payment_method' => 'bank_transfer', 'payment_date' => $date]);
        app(SalesAccountingService::class)->synchronizePayment($p->fresh());

        return $p;
    }

    private function statement(array $lines, ?float $closing = null, ?string $to = null): BankStatementImport
    {
        $import = BankStatementImport::query()->create(['chart_of_account_id' => $this->bankId(), 'status' => 'imported', 'row_count' => count($lines)]);
        foreach ($lines as $i => [$date, $desc, $debit, $credit]) {
            BankStatementLine::query()->create([
                'bank_statement_import_id' => $import->id, 'txn_date' => $date, 'description' => $desc,
                'debit' => $debit, 'credit' => $credit, 'amount' => $credit - $debit, 'fingerprint' => md5($i . $desc . $date),
            ]);
        }
        $import->forceFill(['closing_balance' => $closing, 'statement_to' => $to])->save();

        return $import;
    }

    // ------------------------------------------------------------------ P1 bank reconciliation

    public function test_one_bank_deposit_matched_to_several_payments_with_bank_fee(): void
    {
        $date = now()->startOfMonth()->addDays(4)->toDateString();
        $p1 = $this->invoiceWithBankPayment(6000, $date);
        $p2 = $this->invoiceWithBankPayment(4000, $date);
        $this->statement([[$date, 'CEFT DEPOSIT SHOP', 0, 9950]], 9950, $date);
        $line = BankStatementLine::query()->firstOrFail();

        $ws = BankReconciliationService::workspace($this->bankId());
        $this->assertSame(10000.0, $ws['gl_balance']);
        $this->assertSame(-50.0, $ws['difference']);
        $this->assertSame($ws['difference'], $ws['explained']);    // 9,950 open on statement − 10,000 open in books

        $cands = BankReconciliationService::candidates($line);
        $this->assertEqualsCanonicalizing([$p1->id, $p2->id], $cands->where('type', 'payment')->pluck('id')->all());

        // without accepting the fee it refuses; with it, it posts the 50 fee
        $this->expectExceptionOnce(fn () => BankReconciliationService::match($line, [['type' => 'payment', 'id' => $p1->id], ['type' => 'payment', 'id' => $p2->id]], false));
        $res = BankReconciliationService::match($line, [['type' => 'payment', 'id' => $p1->id], ['type' => 'payment', 'id' => $p2->id]], true, 7);
        $this->assertSame(50.0, $res['fee']);
        $this->assertSame(9950.0, $this->glBalance(AccountingService::GL_BANK));
        $this->assertSame(50.0, $this->glBalance('6050'));

        $ws = BankReconciliationService::workspace($this->bankId());
        $this->assertSame(0.0, $ws['difference']);
        $this->assertCount(0, $ws['queue']);
        $this->assertCount(0, $ws['unpresented']);

        // a payment can't be matched twice; unmatch reverses the fee
        $this->statement([[$date, 'AGAIN', 0, 6000]]);
        $other = BankStatementLine::query()->where('description', 'AGAIN')->firstOrFail();
        $this->expectExceptionOnce(fn () => BankReconciliationService::match($other, [['type' => 'payment', 'id' => $p1->id]]));
        BankReconciliationService::unmatch($line->fresh(), 7);
        $this->assertSame(10000.0, $this->glBalance(AccountingService::GL_BANK));
        $this->assertSame(0.0, $this->glBalance('6050'));
    }

    public function test_reconcile_screen_imports_with_saved_mapping_and_matches(): void
    {
        $date = now()->startOfMonth()->addDays(2)->format('d/m/Y');
        $p = $this->invoiceWithBankPayment(2500, now()->startOfMonth()->addDays(2)->toDateString());
        $csv = "Posted,Narrative,Withdrawal,Lodgement,Ref\n{$date},CEFT FROM SHOP,,2500.00,R1\n";
        $file = UploadedFile::fake()->createWithContent('stmt.csv', $csv);

        $this->post('/accounting/phase7/reconcile/import', [
            'chart_of_account_id' => $this->bankId(), 'file' => $file, 'closing_balance' => 2500,
            'map' => ['date' => 1, 'description' => 2, 'debit' => 3, 'credit' => 4, 'reference' => 5], 'save_mapping' => 1,
        ])->assertRedirect();
        $this->assertSame(['date' => 0, 'description' => 1, 'reference' => 4, 'debit' => 2, 'credit' => 3, 'amount' => null], BankReconciliationService::mappingFor($this->bankId()));
        $line = BankStatementLine::query()->firstOrFail();
        $this->assertSame(2500.0, (float) $line->credit);

        $page = $this->get('/accounting/phase7/reconcile?account_id=' . $this->bankId() . '&line=' . $line->id);
        $page->assertOk()->assertSee('CEFT FROM SHOP')->assertSee('Shop 2500');
        $this->post('/accounting/phase7/reconcile/lines/' . $line->id . '/match', ['sources' => ['payment:' . $p->id]])->assertRedirect()->assertSessionHas('status');
        $this->assertSame('matched', $line->fresh()->match_status);

        // second import of the same account uses the saved mapping (no map typed)
        $file2 = UploadedFile::fake()->createWithContent('stmt2.csv', "Posted,Narrative,Withdrawal,Lodgement,Ref\n{$date},BANK CHARGE,25.00,,\n");
        $this->post('/accounting/phase7/reconcile/import', ['chart_of_account_id' => $this->bankId(), 'file' => $file2])->assertRedirect();
        $this->assertSame(25.0, (float) BankStatementLine::query()->where('description', 'BANK CHARGE')->value('debit'));
    }

    // ------------------------------------------------------------------ P1 payment runs

    private function bill(float $total, string $supplierName = 'Fabric Co', bool $bank = true): Purchase
    {
        $s = Supplier::query()->create(['name' => $supplierName, 'company_name' => $supplierName] + ($bank ? ['bank_account_no' => '0012345678', 'bank_name' => 'Commercial Bank', 'bank_code' => '7056', 'branch_code' => '001', 'bank_account_name' => $supplierName] : []));
        $p = Purchase::query()->create(['purchase_no' => 'PUR-' . random_int(1000, 99999), 'date' => now()->subDays(40)->toDateString(), 'supplier_id' => $s->id,
            'purchase_type' => 'credit', 'status' => Purchase::STATUS_CONFIRMED, 'subtotal' => $total, 'tax_amount' => 0, 'total' => $total, 'paid_amount' => 0]);
        app(\App\Observers\PurchaseObserver::class)->backfill($p->fresh());

        return $p->fresh();
    }

    public function test_payment_run_flow_approve_export_fail_confirm_without_double_payment(): void
    {
        $a = $this->bill(10000, 'Alpha Mills');
        $b = $this->bill(5000, 'Beta Threads');
        $c = $this->bill(2000, 'Gamma Labels');

        $this->post('/accounting/phase7/payment-runs', [
            'purchase_ids' => [$a->id, $b->id, $c->id], 'amounts' => [$a->id => 4000], 'payment_date' => now()->toDateString(), 'bank_account_id' => $this->bankId(),
        ])->assertRedirect();
        $run = DB::table('acct_payment_runs')->first();
        $this->assertSame(11000.0, (float) $run->total);           // 4,000 part-payment + 5,000 + 2,000

        // same bill cannot go into a second open run
        $this->post('/accounting/phase7/payment-runs', ['purchase_ids' => [$b->id], 'payment_date' => now()->toDateString()])->assertSessionHasErrors('run');

        // export needs approval first
        $this->get('/accounting/phase7/payment-runs/' . $run->id . '/export?format=slips_csv')->assertSessionHasErrors('run');
        $this->post('/accounting/phase7/payment-runs/' . $run->id . '/approve')->assertSessionHas('status');
        $file = $this->get('/accounting/phase7/payment-runs/' . $run->id . '/export?format=slips_csv');
        $file->assertOk();
        $this->assertStringContainsString('7056,001,0012345678', $file->getContent());
        $this->assertStringContainsString('4000.00', $file->getContent());

        $itemC = DB::table('acct_payment_run_items')->where('purchase_id', $c->id)->first();
        $this->post('/accounting/phase7/payment-runs/' . $run->id . '/items/' . $itemC->id . '/fail', ['reason' => 'Account closed'])->assertSessionHas('status');

        $this->post('/accounting/phase7/payment-runs/' . $run->id . '/confirm', ['paid_on' => now()->toDateString()])->assertSessionHas('status');
        $this->assertSame(6000.0, (float) $a->fresh()->balance);
        $this->assertSame(0.0, (float) $b->fresh()->balance);
        $this->assertSame(2000.0, (float) $c->fresh()->balance);     // failed → still unpaid
        $this->assertSame('completed', DB::table('acct_payment_runs')->value('status'));
        $this->assertSame(-9000.0, $this->glBalance(AccountingService::GL_BANK));
        $this->assertSame(-8000.0, $this->glBalance(AccountingService::GL_ACCOUNTS_PAY));

        // confirming again creates nothing
        $this->post('/accounting/phase7/payment-runs/' . $run->id . '/confirm')->assertSessionHasErrors('run');
        $this->assertSame(2, \App\Models\PurchasePayment::query()->count());

        $this->get('/accounting/phase7/payment-runs/' . $run->id)->assertOk()->assertSee('Account closed')->assertSee('Completed');
        $this->get('/accounting/phase6/bills/payment-run?due_by=' . now()->toDateString())->assertOk()->assertSee('Gamma Labels');
    }

    public function test_bill_paid_elsewhere_after_run_was_prepared_is_not_paid_twice(): void
    {
        $a = $this->bill(3000);
        $svc = app(PaymentRunService::class);
        $run = $svc->create([$a->id], now()->toDateString(), $this->bankId(), null, 7);
        $svc->approve($run->id, 7);
        \App\Models\PurchasePayment::query()->create(['supplier_id' => $a->supplier_id, 'purchase_id' => $a->id, 'entry_type' => 'payment', 'amount' => 3000, 'payment_method' => 'cash', 'payment_date' => now()->toDateString()]);

        $res = $svc->confirmPaid($run->id, null, 7);
        $this->assertSame(0, $res['paid']);
        $this->assertSame('failed', DB::table('acct_payment_run_items')->value('status'));
        $this->assertSame(1, \App\Models\PurchasePayment::query()->count());
    }

    // ------------------------------------------------------------------ P1 month-end close

    public function test_month_end_close_checks_sign_off_and_lock_blocks_posting(): void
    {
        $key = now()->format('Y-m');
        $date = now()->startOfMonth()->addDays(3)->toDateString();
        $p = $this->invoiceWithBankPayment(1000, $date);
        $this->statement([[$date, 'DEPOSIT', 0, 1000]]);

        $checks = collect(MonthEndCloseService::checks($key))->keyBy('key');
        $this->assertSame('fail', $checks['bank']['status']);
        $this->assertSame('pass', $checks['ar']['status']);
        $this->assertSame('pass', $checks['tb']['status']);
        $this->assertSame('pass', $checks['vat']['status']);

        $this->get('/accounting/phase7/close?period=' . $key)->assertOk()->assertSee('Bank statements reconciled')->assertSee('need attention');
        $this->post('/accounting/phase7/close/' . $key . '/sign-off', ['notes' => ''])->assertSessionHasErrors('close');
        $this->post('/accounting/phase7/close/' . $key . '/lock')->assertSessionHasErrors('close');

        BankReconciliationService::match(BankStatementLine::query()->firstOrFail(), [['type' => 'payment', 'id' => $p->id]]);
        $this->assertSame('pass', collect(MonthEndCloseService::checks($key))->firstWhere('key', 'bank')['status']);
        $this->post('/accounting/phase7/close/' . $key . '/sign-off', ['notes' => 'Reviewed'])->assertSessionHas('status');
        $this->post('/accounting/phase7/close/' . $key . '/lock')->assertSessionHas('status');

        $this->expectExceptionOnce(fn () => AccountingService::postEntry($this->bankId(), $this->coa[AccountingService::GL_CASH], 10, $date, 'late entry'));

        $this->post('/accounting/phase7/close/' . $key . '/reopen', ['reason' => 'Late supplier bill'])->assertSessionHas('status');
        AccountingService::postEntry($this->bankId(), $this->coa[AccountingService::GL_CASH], 10, $date, 'after reopen');
        $this->assertSame('reopened', DB::table('acct_period_close_reviews')->value('status'));
    }

    // ------------------------------------------------------------------ P2 OCR

    public function test_invoice_text_parser_reads_sri_lankan_supplier_invoices(): void
    {
        Supplier::query()->create(['name' => 'Lanka Fabrics', 'company_name' => 'Lanka Fabrics (Pvt) Ltd', 'tax_vat_no' => '114455667-7000']);
        $parser = app(InvoiceTextParser::class);

        $a = $parser->parse("LANKA FABRICS (PVT) LTD\nNo 12, Galle Road, Colombo 03\nTAX INVOICE\nSupplier's TIN: 114455667\nTax Invoice No: LF/2026/0458\nDate of Invoice: 05/11/2026\nCotton fabric 200 m  100.00  20,000.00\nTotal Value of Supply 20,000.00\nVAT Amount (Total Value of Supply @ 18%) 3,600.00\nTotal Amount including VAT 23,600.00");
        $this->assertSame('LF/2026/0458', $a['invoice_number']);
        $this->assertSame('2026-11-05', $a['invoice_date']);
        $this->assertSame(20000.0, $a['subtotal']);
        $this->assertSame(3600.0, $a['tax_amount']);
        $this->assertSame(23600.0, $a['total']);
        $this->assertNotNull($a['supplier_id']);
        $this->assertTrue($a['amounts_consistent']);
        $this->assertGreaterThanOrEqual(85, $a['confidence']);

        $b = $parser->parse("Ceylon Threads Traders\nInvoice No. CT-9921\nDate: 2026-10-28\nSub Total 8,474.58\nVAT 18% 1,525.42\nGrand Total 10,000.00");
        $this->assertSame('CT-9921', $b['invoice_number']);
        $this->assertSame('2026-10-28', $b['invoice_date']);
        $this->assertSame(10000.0, $b['total']);
        $this->assertSame(1525.42, $b['tax_amount']);
        $this->assertNull($b['supplier_id']);
        $this->assertSame('Ceylon Threads Traders', $b['supplier_name']);

        $c = $parser->parse("Some receipt\nTotal 1,250.00");
        $this->assertNull($c['invoice_number']);          // nothing invented
        $this->assertSame(1250.0, $c['total']);
    }

    public function test_text_is_read_from_a_generated_pdf(): void
    {
        $html = '<html><body><h3>Lanka Fabrics (Pvt) Ltd</h3><p>Tax Invoice No: LF-7781</p><p>Date of Invoice: 12/11/2026</p><p>Total Value of Supply 10,000.00</p><p>VAT Amount 1,800.00</p><p>Total Amount including VAT 11,800.00</p></body></html>';
        $path = sys_get_temp_dir() . '/ocr-test-' . uniqid() . '.pdf';
        file_put_contents($path, \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->output());

        $read = app(DocumentTextExtractor::class)->extract($path, 'application/pdf');
        @unlink($path);
        $this->assertNotNull($read['text'], 'PDF text should be readable by pdftotext or the built-in reader');
        $fields = app(InvoiceTextParser::class)->parse($read['text']);
        $this->assertSame('LF-7781', $fields['invoice_number']);
        $this->assertSame(11800.0, $fields['total']);
        $this->assertSame(1800.0, $fields['tax_amount']);
    }

    public function test_builtin_pdf_reader_works_without_pdftotext(): void
    {
        $html = '<p>Invoice No: X-100</p><p>Total 500.00</p>';
        $text = app(DocumentTextExtractor::class)->phpPdfText(\Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->output());
        $this->assertNotNull($text);
        $this->assertStringContainsString('X-100', str_replace(' ', '', $text) . $text);
    }

    // ------------------------------------------------------------------ P2 reminders

    public function test_reminder_schedule_sends_once_per_step_and_logs_delivery(): void
    {
        config(['dermas.reminder_schedule_days' => [1, 7, 30], 'dermas.reminder_min_gap_days' => 0]);
        $c = Customer::query()->create(['name' => 'Late Shop', 'phone' => '0771112223']);
        $o = Order::query()->create(['order_number' => 'O-LATE', 'customer_id' => $c->id, 'user_id' => 7, 'subtotal' => 1000, 'total_amount' => 1000,
            'status' => Order::STATUS_COMPLETED, 'invoice_number' => 'INV-LATE', 'order_date' => now()->subDays(40)->toDateString(),
            'invoice_date' => now()->subDays(40)->toDateString(), 'invoice_due_date' => now()->subDays(10)->toDateString()]);
        $o->forceFill(['invoice_due_date' => now()->subDays(10)->toDateString()])->save();

        $sms = \Mockery::mock(\App\Services\TextLkSmsService::class);
        $sms->shouldReceive('sendSms')->twice()->andReturn(true);
        $this->app->instance(\App\Services\TextLkSmsService::class, $sms);

        $svc = app(\App\Services\Accounting\ReceivablesService::class);
        $first = $svc->runReminderSchedule();          // 10 days late: steps 1 and 7 reached → one SMS now
        $this->assertSame(1, $first['sent']);
        $second = $svc->runReminderSchedule();         // one more for the second step
        $this->assertSame(1, $second['sent']);
        $third = $svc->runReminderSchedule();          // nothing until 30 days
        $this->assertSame(0, $third['sent']);
        $this->assertSame(2, DB::table('acct_reminder_logs')->where('status', 'sent')->where('trigger', 'schedule')->count());

        $this->get('/accounting/phase7/reminders')->assertOk()->assertSee('INV-LATE')->assertSee('Automatic');
        $this->artisan('accounting:send-payment-reminders')->expectsOutputToContain('off')->assertSuccessful();
    }

    public function test_payment_settlement_discount_is_posted_and_reversed_on_bounce(): void
    {
        $date = now()->toDateString();
        $c = Customer::query()->create(['name' => 'Disc Shop']);
        $o = Order::query()->create(['order_number' => 'O-D', 'customer_id' => $c->id, 'user_id' => 7, 'subtotal' => 10000, 'total_amount' => 10000,
            'status' => Order::STATUS_COMPLETED, 'invoice_number' => 'INV-D', 'order_date' => $date]);
        app(SalesAccountingService::class)->synchronizeOrder($o->fresh());

        $p = Payment::query()->create(['order_id' => $o->id, 'amount' => 9800, 'discount_amount' => 200, 'settlement_amount' => 10000,
            'payment_method' => 'bank_transfer', 'payment_date' => $date]);
        app(SalesAccountingService::class)->synchronizePayment($p->fresh());
        $this->assertSame(0.0, (float) $o->fresh()->due_amount);
        $this->assertSame(0.0, $this->glBalance(AccountingService::GL_ACCOUNTS_RECV));    // was 200 left open before
        $this->assertSame(200.0, $this->glBalance('4020'));

        $chq = Payment::query()->create(['order_id' => $o->id, 'amount' => 950, 'discount_amount' => 50, 'settlement_amount' => 1000,
            'payment_method' => 'cheque', 'payment_date' => $date, 'cheque_status' => 'pending']);
        app(SalesAccountingService::class)->synchronizePaymentDiscount($chq->fresh());
        $this->assertSame(250.0, $this->glBalance('4020'));
        $chq->update(['cheque_status' => Payment::CHEQUE_BOUNCED]);           // observer re-syncs the discount
        $this->assertSame(200.0, $this->glBalance('4020'));
    }

    private function expectExceptionOnce(callable $fn): void
    {
        try {
            $fn();
        } catch (\RuntimeException $e) {
            $this->assertNotSame('', $e->getMessage());

            return;
        }
        $this->fail('Expected an exception.');
    }
}
