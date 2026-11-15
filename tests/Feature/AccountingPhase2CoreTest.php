<?php

namespace Tests\Feature;

use App\Models\Accounting\AccountingPeriod;
use App\Models\Accounting\ManualJournal;
use App\Services\Accounting\AccountRegisterService;
use App\Services\Accounting\AccountingPeriodService;
use App\Services\Accounting\BankStatementImportService;
use App\Services\Accounting\ManualJournalService;
use App\Services\Accounting\RecurringAccountingService;
use App\Services\AccountingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class AccountingPhase2CoreTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('accounting_recurring_runs');
        Schema::dropIfExists('accounting_recurring_templates');
        Schema::dropIfExists('bank_statement_lines');
        Schema::dropIfExists('bank_statement_imports');
        Schema::dropIfExists('accounting_manual_journal_lines');
        Schema::dropIfExists('accounting_manual_journals');
        Schema::dropIfExists('accounting_audit_events');
        Schema::dropIfExists('accounting_periods');
        Schema::dropIfExists('accounting_postings');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('chart_of_accounts');

        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('gl_code')->unique();
            $table->string('name')->nullable();
            $table->string('account_type')->nullable();
            $table->string('normal_balance')->nullable();
            $table->decimal('opening_balance', 20, 4)->default(0);
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
            $table->decimal('debit', 20, 4)->nullable();
            $table->decimal('credit', 20, 4)->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('reversed')->default(false);
            $table->boolean('reversible')->default(true);
            $table->boolean('manual_entry')->default(false);
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->text('notes')->nullable();
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

        Schema::create('accounting_periods', function (Blueprint $table) {
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

        Schema::create('accounting_manual_journals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->default(0);
            $table->string('journal_number')->unique();
            $table->date('journal_date');
            $table->date('posting_date')->nullable();
            $table->string('status')->default('draft');
            $table->string('description', 500)->nullable();
            $table->string('reference', 120)->nullable();
            $table->string('transaction_number', 50)->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('rejection_reason', 500)->nullable();
            $table->string('reversed_by_txn', 50)->nullable();
            $table->timestamps();
        });

        Schema::create('accounting_manual_journal_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manual_journal_id');
            $table->unsignedBigInteger('chart_of_account_id');
            $table->decimal('debit', 20, 4)->default(0);
            $table->decimal('credit', 20, 4)->default(0);
            $table->string('memo', 500)->nullable();
            $table->unsignedInteger('line_no')->default(1);
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
            $table->string('reason', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('bank_statement_imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->default(0);
            $table->unsignedBigInteger('chart_of_account_id');
            $table->string('original_filename')->nullable();
            $table->string('status')->default('preview');
            $table->unsignedBigInteger('imported_by')->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->timestamps();
        });

        Schema::create('bank_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bank_statement_import_id');
            $table->date('txn_date')->nullable();
            $table->string('description', 500)->nullable();
            $table->string('reference', 120)->nullable();
            $table->decimal('debit', 20, 4)->default(0);
            $table->decimal('credit', 20, 4)->default(0);
            $table->decimal('amount', 20, 4)->default(0);
            $table->string('fingerprint', 64);
            $table->string('match_status')->default('unmatched');
            $table->string('matched_source_type')->nullable();
            $table->unsignedBigInteger('matched_source_id')->nullable();
            $table->timestamps();
        });

        Schema::create('accounting_recurring_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->default(0);
            $table->string('name');
            $table->string('frequency');
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->date('next_run_on')->nullable();
            $table->string('status')->default('active');
            $table->string('execution_mode')->default('draft');
            $table->json('payload');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('accounting_recurring_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('template_id');
            $table->date('occurrence_date');
            $table->string('status')->default('created');
            $table->unsignedBigInteger('manual_journal_id')->nullable();
            $table->string('message', 500)->nullable();
            $table->timestamps();
            $table->unique(['template_id', 'occurrence_date']);
        });

        DB::table('chart_of_accounts')->insert([
            ['id' => 1, 'gl_code' => '1000', 'name' => 'Cash', 'account_type' => 'asset', 'normal_balance' => 'debit', 'opening_balance' => 0, 'business_id' => 0, 'active' => 1],
            ['id' => 2, 'gl_code' => '1010', 'name' => 'Bank', 'account_type' => 'asset', 'normal_balance' => 'debit', 'opening_balance' => 0, 'business_id' => 0, 'active' => 1],
            ['id' => 3, 'gl_code' => '4000', 'name' => 'Sales', 'account_type' => 'income', 'normal_balance' => 'credit', 'opening_balance' => 0, 'business_id' => 0, 'active' => 1],
            ['id' => 4, 'gl_code' => '6000', 'name' => 'Expense', 'account_type' => 'expense', 'normal_balance' => 'debit', 'opening_balance' => 0, 'business_id' => 0, 'active' => 1],
        ]);
    }

    public function test_multiline_balanced_journal_posts(): void
    {
        $txn = AccountingService::postMultiLine([
            ['account_id' => 1, 'debit' => 10000, 'credit' => 0],
            ['account_id' => 2, 'debit' => 5000, 'credit' => 0],
            ['account_id' => 3, 'debit' => 0, 'credit' => 15000],
        ], '2026-11-01', 'Multi sale', null, 'manual_entry', [], 'mj:test1');

        $rows = DB::table('journal_entries')->where('transaction_number', $txn)->get();
        $this->assertCount(3, $rows);
        $this->assertEquals(15000.0, round((float) $rows->sum('debit'), 2));
        $this->assertEquals(15000.0, round((float) $rows->sum('credit'), 2));
    }

    public function test_unbalanced_multiline_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        AccountingService::postMultiLine([
            ['account_id' => 1, 'debit' => 100, 'credit' => 0],
            ['account_id' => 3, 'debit' => 0, 'credit' => 50],
        ], '2026-11-01', 'Bad');
    }

    public function test_manual_journal_lifecycle_and_duplicate_post_protection(): void
    {
        $journal = ManualJournalService::createDraft([
            'journal_date' => '2026-11-02',
            'description' => 'Rent',
        ], [
            ['account_id' => 4, 'debit' => 2500, 'credit' => 0],
            ['account_id' => 1, 'debit' => 0, 'credit' => 2500],
        ], 1);

        ManualJournalService::submit($journal, 1);
        ManualJournalService::approve($journal->fresh(), 2);
        ManualJournalService::post($journal->fresh(), true, 2);

        $posted = $journal->fresh();
        $this->assertSame('posted', $posted->status);
        $this->assertNotEmpty($posted->transaction_number);

        $this->expectException(RuntimeException::class);
        ManualJournalService::post($posted, true, 2);
    }

    public function test_manual_journal_reversal_creates_opposite_entries(): void
    {
        $journal = ManualJournalService::createDraft([
            'journal_date' => '2026-11-03',
            'description' => 'Adj',
        ], [
            ['account_id' => 1, 'debit' => 100, 'credit' => 0],
            ['account_id' => 3, 'debit' => 0, 'credit' => 100],
        ]);
        ManualJournalService::post($journal, false, 1);
        $rev = ManualJournalService::reverse($journal->fresh(), 1);

        $this->assertSame('reversed', $rev->status);
        $this->assertNotEmpty($rev->reversed_by_txn);

        $orig = DB::table('journal_entries')->where('transaction_number', $journal->transaction_number)->get();
        $this->assertTrue($orig->every(fn ($r) => (int) $r->reversed === 1));
    }

    public function test_account_register_running_balance(): void
    {
        AccountingService::postMultiLine([
            ['account_id' => 1, 'debit' => 1000, 'credit' => 0],
            ['account_id' => 3, 'debit' => 0, 'credit' => 1000],
        ], '2026-10-01', 'Oct');

        AccountingService::postMultiLine([
            ['account_id' => 1, 'debit' => 0, 'credit' => 200],
            ['account_id' => 4, 'debit' => 200, 'credit' => 0],
        ], '2026-11-05', 'Nov spend');

        $reg = AccountRegisterService::register(1, '2026-11-01', '2026-11-30');
        $this->assertSame('1000.00', $reg['opening']);
        $this->assertSame('800.00', $reg['closing']);
        $this->assertCount(1, $reg['rows']);
        $this->assertSame('800.00', $reg['rows']->first()->running_balance);
    }

    public function test_closed_period_blocks_posting(): void
    {
        AccountingPeriod::create([
            'business_id' => 0,
            'name' => 'Nov 2026',
            'starts_on' => '2026-11-01',
            'ends_on' => '2026-11-30',
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        $this->expectException(RuntimeException::class);
        AccountingService::postEntry(1, 3, 50, '2026-11-10', 'Blocked');
    }

    public function test_period_reopen_allows_posting(): void
    {
        $period = AccountingPeriod::create([
            'business_id' => 0,
            'name' => 'Oct 2026',
            'starts_on' => '2026-10-01',
            'ends_on' => '2026-10-31',
            'status' => 'closed',
        ]);
        AccountingPeriodService::reopen($period, 1, 'Correction');
        $txn = AccountingService::postEntry(1, 3, 75, '2026-10-15', 'Allowed');
        $this->assertNotEmpty($txn);
    }

    public function test_bank_statement_import_dedupes(): void
    {
        $rows = [
            ['date' => '2026-11-01', 'description' => 'Deposit', 'reference' => 'R1', 'debit' => 0, 'credit' => 500],
            ['date' => '2026-11-01', 'description' => 'Deposit', 'reference' => 'R1', 'debit' => 0, 'credit' => 500],
        ];
        $import = BankStatementImportService::import(2, $rows, 't.csv');
        $this->assertSame(1, (int) $import->row_count);

        $again = BankStatementImportService::import(2, $rows, 't2.csv');
        $this->assertSame(0, (int) $again->row_count);
    }

    public function test_recurring_idempotent_execution(): void
    {
        $template = RecurringAccountingService::createTemplate([
            'name' => 'Monthly rent',
            'frequency' => 'monthly',
            'starts_on' => '2026-11-01',
            'execution_mode' => 'draft',
            'payload' => [
                'lines' => [
                    ['account_id' => 4, 'debit' => 1000, 'credit' => 0],
                    ['account_id' => 1, 'debit' => 0, 'credit' => 1000],
                ],
            ],
        ], 1);

        $a = RecurringAccountingService::executeOccurrence($template, '2026-11-01');
        $b = RecurringAccountingService::executeOccurrence($template->fresh(), '2026-11-01');
        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, ManualJournal::count());
    }
}
