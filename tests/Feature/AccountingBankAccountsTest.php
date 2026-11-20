<?php

namespace Tests\Feature;

use App\Models\Accounting\BankStatementImport;
use App\Models\Accounting\BankStatementLine;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\User;
use App\Services\Accounting\BankAccountService;
use App\Services\Accounting\BankReconciliationService;
use App\Services\AccountingService;
use App\Services\Audit\AuditLogger;
use App\Services\SalesAccountingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\BuildsPhase6Schema;
use Tests\TestCase;

/**
 * Bank accounts: add → import (preview) → review (match / categorise / transfer) → reconcile → history → archive.
 */
class AccountingBankAccountsTest extends TestCase
{
    use BuildsPhase6Schema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildPhase6Schema();
        AuditLogger::flushCache();
        Order::flushSchemaCache();
        Purchase::flushSchemaCache();
        Storage::fake('local');
        $user = new User(['role' => 'admin', 'role_id' => 1, 'name' => 'Bank Admin']);
        $user->id = 7;
        $user->setRelation('roleModel', new Role(['name' => 'admin', 'permissions' => ['accounting.view', 'accounting.manage']]));
        $this->actingAs($user);
    }

    protected function tearDown(): void
    {
        AuditLogger::flushCache();
        Order::flushSchemaCache();
        Purchase::flushSchemaCache();
        parent::tearDown();
    }

    private function addAccount(array $over = []): object
    {
        $this->post('/accounting/banking', array_merge([
            'bank_name' => 'Commercial Bank', 'display_name' => 'Commercial Bank – Collections', 'account_type' => 'current',
            'account_number' => '8001234566789', 'ledger_mode' => 'new', 'opening_balance' => 50000, 'opening_balance_date' => now()->startOfMonth()->toDateString(),
        ], $over))->assertRedirect();

        return DB::table('bank_accounts')->orderByDesc('id')->first();
    }

    private function balance(int $ledgerId): float
    {
        return round((float) DB::table('journal_entries')->where('chart_of_account_id', $ledgerId)->where('reversed', 0)
            ->selectRaw('COALESCE(SUM(debit),0) - COALESCE(SUM(credit),0) as b')->value('b'), 2);
    }

    private function line(object $acc, string $date, string $desc, float $out, float $in): BankStatementLine
    {
        $imp = BankStatementImport::query()->create(['chart_of_account_id' => $acc->chart_of_account_id, 'status' => 'imported', 'row_count' => 1]);

        return BankStatementLine::query()->create(['bank_statement_import_id' => $imp->id, 'txn_date' => $date, 'description' => $desc,
            'debit' => $out, 'credit' => $in, 'amount' => $in - $out, 'fingerprint' => md5($desc . $date . $out . $in)]);
    }

    public function test_add_account_creates_ledger_posts_opening_balance_and_masks_number(): void
    {
        $acc = $this->addAccount();
        $ledger = DB::table('chart_of_accounts')->where('id', $acc->chart_of_account_id)->first();
        $this->assertSame('1011', (string) $ledger->gl_code);
        $this->assertSame('asset', $ledger->account_type);
        $this->assertSame(50000.0, $this->balance($acc->chart_of_account_id));
        $this->assertSame(-50000.0, $this->glBalance('3050'));

        $card = $this->addAccount(['display_name' => 'Sampath Visa', 'bank_name' => 'Sampath', 'account_type' => 'credit_card', 'account_number' => '4111 1111 1111 4242', 'opening_balance' => 12000]);
        $this->assertSame('liability', DB::table('chart_of_accounts')->where('id', $card->chart_of_account_id)->value('account_type'));
        $this->assertSame(-12000.0, $this->balance($card->chart_of_account_id));     // credit balance = amount owed
        $this->assertSame(12000.0, BankAccountService::bookBalance($card));

        // existing ledger with history: its balance is the opening balance, nothing extra posted
        AccountingService::postEntry($this->coa[AccountingService::GL_BANK], $this->coa[AccountingService::GL_CASH], 7000, now()->toDateString(), 'old history');
        $linked = $this->addAccount(['ledger_mode' => 'existing', 'chart_of_account_id' => $this->coa[AccountingService::GL_BANK], 'display_name' => 'BOC Main', 'opening_balance' => 99999]);
        $this->assertSame(7000.0, (float) $linked->opening_balance);
        $this->assertSame(7000.0, $this->balance($this->coa[AccountingService::GL_BANK]));
        $this->post('/accounting/banking', ['bank_name' => 'X', 'display_name' => 'Dup', 'account_type' => 'current', 'ledger_mode' => 'existing', 'chart_of_account_id' => $this->coa[AccountingService::GL_BANK]])
            ->assertSessionHasErrors('account');

        $this->get('/accounting/banking')->assertOk()->assertSee('Commercial Bank – Collections')->assertSee('•••• 6789')->assertDontSee('8001234566789')->assertSee('50,000.00');
        $this->get('/accounting/banking/' . $acc->id)->assertOk()->assertSee('Next steps');
        $this->get('/accounting/banking/create')->assertOk();
    }

    public function test_import_preview_flags_duplicates_and_invalid_rows_then_imports_only_new(): void
    {
        $acc = $this->addAccount();
        $d1 = now()->startOfMonth()->addDays(1)->format('d/m/Y');
        $d2 = now()->startOfMonth()->addDays(2)->format('d/m/Y');
        $csv = "Txn Date,Narration,Debit,Credit\n{$d1},CEFT FROM PERERA,,\"12,500.00\"\n{$d2},SERVICE CHARGE,150.00,\n{$d2},BROKEN ROW,,\n";
        $file = fn () => UploadedFile::fake()->createWithContent('stmt.csv', $csv);

        $prev = $this->post('/accounting/banking/' . $acc->id . '/import/preview', ['file' => $file(), 'map' => ['date' => 1, 'description' => 2, 'debit' => 3, 'credit' => 4], 'save_mapping' => 1, 'closing_balance' => 62350]);
        $prev->assertOk()->assertSee('New lines')->assertSee('12,500.00')->assertSee('missing amount');
        $this->assertSame(0, BankStatementLine::query()->count());                  // preview writes nothing
        preg_match('/name="token" value="([^"]+)"/', $prev->getContent(), $m);

        $this->post('/accounting/banking/' . $acc->id . '/import/confirm', ['token' => $m[1]])->assertRedirect();
        $this->assertSame(2, BankStatementLine::query()->count());
        $this->assertSame(12500.0, (float) BankStatementLine::query()->where('description', 'CEFT FROM PERERA')->value('credit'));
        $this->assertSame(now()->startOfMonth()->addDays(1)->toDateString(), BankStatementLine::query()->where('description', 'CEFT FROM PERERA')->first()->txn_date->toDateString());
        $this->assertNotNull(BankReconciliationService::mappingFor($acc->chart_of_account_id));
        $this->assertSame(62350.0, (float) DB::table('bank_statement_imports')->where('status', 'imported')->value('closing_balance'));

        // same file again: everything is a duplicate, nothing to import
        $again = $this->post('/accounting/banking/' . $acc->id . '/import/preview', ['file' => $file()]);
        $again->assertOk()->assertSee('Already imported');
        $this->assertStringContainsString('Import 0 new line(s)', $again->getContent());
    }

    public function test_review_categorise_bank_charge_and_transfer_between_own_accounts(): void
    {
        $a = $this->addAccount();
        $b = $this->addAccount(['display_name' => 'HNB Savings', 'bank_name' => 'HNB', 'account_type' => 'savings', 'opening_balance' => 0]);
        $date = now()->startOfMonth()->addDays(3)->toDateString();

        $fee = $this->line($a, $date, 'SMS ALERT FEE', 150, 0);
        $this->post('/accounting/phase7/reconcile/lines/' . $fee->id . '/post', ['account_id' => DB::table('chart_of_accounts')->where('gl_code', '6050')->value('id')])->assertRedirect()->assertSessionHas('status');
        $this->assertSame('matched', $fee->fresh()->match_status);
        $this->assertSame(49850.0, $this->balance($a->chart_of_account_id));
        $this->assertSame(150.0, $this->glBalance('6050'));

        $twin = $this->line($b, $date, 'TRANSFER IN', 0, 5000);
        $out = $this->line($a, $date, 'TRANSFER TO HNB', 5000, 0);
        $this->post('/accounting/phase7/reconcile/lines/' . $out->id . '/transfer', ['bank_account_id' => $b->id])->assertRedirect()->assertSessionHas('status');
        $this->assertSame('matched', $out->fresh()->match_status);
        $this->assertSame('matched', $twin->fresh()->match_status);    // the other side matched automatically
        $this->assertSame(44850.0, $this->balance($a->chart_of_account_id));
        $this->assertSame(5000.0, $this->balance($b->chart_of_account_id));

        $this->get('/accounting/phase7/reconcile?account_id=' . $a->chart_of_account_id)->assertOk()->assertSee('Commercial Bank – Collections');
    }

    public function test_reconcile_to_statement_balance_history_and_undo(): void
    {
        $acc = $this->addAccount();
        $date = now()->startOfMonth()->addDays(5)->toDateString();
        $c = Customer::query()->create(['name' => 'Perera']);
        $o = Order::query()->create(['order_number' => 'O-1', 'customer_id' => $c->id, 'user_id' => 7, 'subtotal' => 12500, 'total_amount' => 12500, 'status' => Order::STATUS_COMPLETED, 'invoice_number' => 'INV-1', 'order_date' => $date]);
        app(SalesAccountingService::class)->synchronizeOrder($o->fresh());
        // book the receipt straight to this bank account
        AccountingService::postOnce('test:receipt', Payment::class, 99, $acc->chart_of_account_id, $this->coa[AccountingService::GL_ACCOUNTS_RECV], 12500, $date, 'Receipt Perera', 'R1', 'customer_payment');
        AccountingService::postEntry($this->coa[AccountingService::GL_GENERAL_EXPENSE], $acc->chart_of_account_id, 3000, now()->startOfMonth()->addDays(20)->toDateString(), 'Cheque not yet presented');

        $stmtDate = now()->startOfMonth()->addDays(10)->toDateString();
        $page = $this->get('/accounting/banking/' . $acc->id . '?tab=reconcile&statement_date=' . $stmtDate . '&statement_balance=62500');
        $page->assertOk()->assertSee('Receipt Perera')->assertSee('Opening balance')->assertDontSee('Cheque not yet presented');   // dated after the statement

        $ids = DB::table('journal_entries')->where('chart_of_account_id', $acc->chart_of_account_id)->whereDate('date', '<=', $stmtDate)->pluck('id')->all();
        $this->post('/accounting/banking/' . $acc->id . '/reconcile', ['statement_date' => $stmtDate, 'statement_balance' => 60000, 'cleared' => $ids])->assertSessionHasErrors('reconcile');
        $this->post('/accounting/banking/' . $acc->id . '/reconcile', ['statement_date' => $stmtDate, 'statement_balance' => 62500, 'cleared' => $ids])->assertRedirect();
        $rec = DB::table('bank_reconciliations')->first();
        $this->assertSame(62500.0, (float) $rec->statement_balance);
        $this->assertSame(0.0, (float) $rec->beginning_balance);
        $this->assertSame(2, (int) $rec->cleared_count);

        // next statement starts from 62,500; the unpresented cheque clears now
        $acc2 = BankAccountService::find($acc->id);
        $this->assertSame(62500.0, BankAccountService::beginningBalance($acc2));
        $next = now()->startOfMonth()->addDays(25)->toDateString();
        $lines = BankAccountService::unclearedLines($acc2, $next);
        $this->assertCount(1, $lines);
        $this->post('/accounting/banking/' . $acc->id . '/reconcile', ['statement_date' => $next, 'statement_balance' => 59500, 'cleared' => [$lines->first()->id]])->assertRedirect();

        $this->get('/accounting/banking/' . $acc->id . '?tab=history')->assertOk()->assertSee('59,500.00')->assertSee('62,500.00');
        $this->get('/accounting/banking/' . $acc->id . '/reconciliations/' . $rec->id)->assertOk()->assertSee('Receipt Perera');

        // only the latest can be undone
        $this->post('/accounting/banking/' . $acc->id . '/reconciliations/' . $rec->id . '/undo')->assertSessionHasErrors('reconcile');
        $last = DB::table('bank_reconciliations')->orderByDesc('id')->first();
        $this->post('/accounting/banking/' . $acc->id . '/reconciliations/' . $last->id . '/undo')->assertSessionHas('status');
        $this->assertSame(62500.0, BankAccountService::beginningBalance($acc2));
        $this->assertCount(1, BankAccountService::unclearedLines($acc2, $next));
    }

    public function test_archive_requires_final_statement_matched_and_keeps_ledger(): void
    {
        $acc = $this->addAccount();
        $line = $this->line($acc, now()->toDateString(), 'CLOSING TRANSFER', 50000, 0);
        $this->post('/accounting/banking/' . $acc->id . '/archive')->assertSessionHasErrors('settings');

        $this->post('/accounting/phase7/reconcile/lines/' . $line->id . '/post', ['account_id' => $this->coa[AccountingService::GL_CASH], 'description' => 'Cash withdrawn to close account'])->assertRedirect();
        $this->assertSame(0.0, $this->balance($acc->chart_of_account_id));
        $this->post('/accounting/banking/' . $acc->id . '/settings', ['display_name' => 'ComBank Collections (old)', 'bank_name' => 'Commercial Bank', 'map' => ['date' => 2, 'description' => 3, 'amount' => 4]])->assertSessionHas('status');
        $this->assertSame(['date' => 1, 'description' => 2, 'reference' => null, 'debit' => null, 'credit' => null, 'amount' => 3], BankReconciliationService::mappingFor($acc->chart_of_account_id));

        $this->post('/accounting/banking/' . $acc->id . '/archive')->assertRedirect();
        $this->assertSame('archived', DB::table('bank_accounts')->value('status'));
        $this->assertSame(1, (int) DB::table('chart_of_accounts')->where('id', $acc->chart_of_account_id)->value('active'));   // history stays
        $this->get('/accounting/banking')->assertOk()->assertDontSee('ComBank Collections (old)');
        $this->get('/accounting/banking?archived=1')->assertOk()->assertSee('ComBank Collections (old)');
        $this->post('/accounting/banking/' . $acc->id . '/import/preview', ['file' => UploadedFile::fake()->createWithContent('x.csv', "Date,Description,Amount\n01/01/2026,X,1\n")])->assertForbidden();
    }
}
