<?php

namespace Tests\Feature;

use App\Models\Accounting\BankStatementImport;
use App\Models\Accounting\BankStatementLine;
use App\Models\Accounting\CustomerPaymentAllocation;
use App\Models\Accounting\RecurringAccountingTemplate;
use App\Models\Accounting\SupplierPaymentAllocation;
use App\Models\Order;
use App\Models\Purchase;
use App\Services\Accounting\BankMatchingService;
use App\Services\Accounting\BankStatementImportService;
use App\Services\Accounting\PaymentAllocationService;
use App\Services\Accounting\RecurringAccountingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Tests\TestCase;

class AccountingPhase2CompletionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'customer_payment_allocations', 'supplier_payment_allocations',
            'accounting_recurring_runs', 'accounting_recurring_templates',
            'accounting_manual_journal_lines', 'accounting_manual_journals',
            'bank_statement_lines', 'bank_statement_imports', 'accounting_audit_events',
            'payments', 'orders', 'purchase_payments', 'purchases', 'customers', 'suppliers',
            'journal_entries', 'chart_of_accounts',
        ] as $t) {
            Schema::dropIfExists($t);
        }

        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('gl_code')->unique();
            $table->string('name')->nullable();
            $table->string('account_type')->nullable();
            $table->string('normal_balance')->nullable();
            $table->decimal('opening_balance', 20, 4)->default(0);
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
            $table->decimal('debit', 20, 4)->nullable();
            $table->decimal('credit', 20, 4)->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('reversed')->default(false);
            $table->boolean('reversible')->default(true);
            $table->boolean('manual_entry')->default(false);
            $table->timestamps();
        });

        Schema::create('bank_statement_imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->default(0);
            $table->unsignedBigInteger('chart_of_account_id');
            $table->string('original_filename')->nullable();
            $table->string('status')->default('imported');
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
            $table->string('excluded_reason')->nullable();
            $table->string('matched_source_type')->nullable();
            $table->unsignedBigInteger('matched_source_id')->nullable();
            $table->timestamp('matched_at')->nullable();
            $table->unsignedBigInteger('matched_by')->nullable();
            $table->timestamps();
        });

        Schema::create('accounting_recurring_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->default(0);
            $table->string('name');
            $table->string('document_type')->default('journal');
            $table->string('frequency');
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->unsignedInteger('max_occurrences')->nullable();
            $table->unsignedInteger('occurrence_count')->default(0);
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
            $table->string('generated_type')->nullable();
            $table->unsignedBigInteger('generated_id')->nullable();
            $table->string('execution_key')->nullable()->unique();
            $table->string('message', 500)->nullable();
            $table->timestamps();
            $table->unique(['template_id', 'occurrence_date']);
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

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->decimal('total_amount', 20, 4)->default(0);
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->decimal('amount', 20, 4);
            $table->decimal('settlement_amount', 20, 4)->nullable();
            $table->date('payment_date')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_id');
            $table->unsignedBigInteger('order_id');
            $table->decimal('amount', 20, 4);
            $table->unsignedBigInteger('allocated_by')->nullable();
            $table->timestamps();
            $table->unique(['payment_id', 'order_id']);
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_no')->nullable();
            $table->date('date')->nullable();
            $table->unsignedBigInteger('supplier_id');
            $table->string('purchase_type')->nullable();
            $table->string('status')->default('draft');
            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('total', 20, 4)->default(0);
            $table->decimal('paid_amount', 20, 4)->default(0);
            $table->string('reference_no')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->decimal('amount', 20, 4);
            $table->date('payment_date')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('supplier_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_payment_id');
            $table->unsignedBigInteger('purchase_id');
            $table->decimal('amount', 20, 4);
            $table->unsignedBigInteger('allocated_by')->nullable();
            $table->timestamps();
            $table->unique(['purchase_payment_id', 'purchase_id']);
        });

        DB::table('chart_of_accounts')->insert([
            ['id' => 1, 'gl_code' => '1010', 'name' => 'Bank', 'account_type' => 'asset', 'normal_balance' => 'debit', 'opening_balance' => 0, 'active' => 1],
            ['id' => 2, 'gl_code' => '1000', 'name' => 'Cash', 'account_type' => 'asset', 'normal_balance' => 'debit', 'opening_balance' => 0, 'active' => 1],
            ['id' => 3, 'gl_code' => '6000', 'name' => 'Expense', 'account_type' => 'expense', 'normal_balance' => 'debit', 'opening_balance' => 0, 'active' => 1],
        ]);
    }

    public function test_xlsx_import_parses_and_does_not_post_journals(): void
    {
        $path = sys_get_temp_dir().'/bank_stmt_test.xlsx';
        $sheet = new Spreadsheet();
        $sheet->getActiveSheet()->fromArray([
            ['date', 'description', 'reference', 'debit', 'credit'],
            ['2026-11-01', 'Deposit A', 'R1', 0, 1500],
            ['2026-11-02', 'Withdrawal B', 'R2', 200, 0],
        ]);
        (new Xlsx($sheet))->save($path);

        $rows = BankStatementImportService::parseXlsx($path);
        $import = BankStatementImportService::import(1, $rows, 'bank_stmt_test.xlsx');

        $this->assertSame(2, (int) $import->row_count);
        $this->assertSame(0, DB::table('journal_entries')->count());
        @unlink($path);
    }

    public function test_bank_match_confirm_and_prevent_double_match(): void
    {
        $import = BankStatementImport::create([
            'business_id' => 0,
            'chart_of_account_id' => 1,
            'status' => 'imported',
            'row_count' => 1,
        ]);
        $line = BankStatementLine::create([
            'bank_statement_import_id' => $import->id,
            'txn_date' => '2026-11-05',
            'description' => 'Cust pay',
            'reference' => 'INV1',
            'debit' => 0,
            'credit' => 500,
            'amount' => 500,
            'fingerprint' => 'fp1',
            'match_status' => 'unmatched',
        ]);

        DB::table('customers')->insert(['id' => 1, 'name' => 'C1']);
        DB::table('orders')->insert(['id' => 10, 'customer_id' => 1, 'total_amount' => 500, 'status' => 'credit']);
        DB::table('payments')->insert([
            'id' => 20, 'order_id' => 10, 'amount' => 500, 'settlement_amount' => 500,
            'payment_date' => '2026-11-05', 'payment_method' => 'bank_transfer', 'reference_number' => 'INV1',
        ]);

        $suggestions = BankMatchingService::suggestions($line, 1);
        $this->assertNotEmpty($suggestions);

        BankMatchingService::confirmMatch($line, 'payment', 20, 1);
        $this->assertSame('matched', $line->fresh()->match_status);

        $line2 = BankStatementLine::create([
            'bank_statement_import_id' => $import->id,
            'txn_date' => '2026-11-05',
            'description' => 'Dup',
            'reference' => 'X',
            'debit' => 0,
            'credit' => 500,
            'amount' => 500,
            'fingerprint' => 'fp2',
            'match_status' => 'unmatched',
        ]);

        $this->expectException(RuntimeException::class);
        BankMatchingService::confirmMatch($line2, 'payment', 20, 1);
    }

    public function test_customer_allocation_blocks_overpay(): void
    {
        DB::table('customers')->insert(['id' => 1, 'name' => 'C1']);
        DB::table('orders')->insert(['id' => 1, 'customer_id' => 1, 'total_amount' => 100, 'status' => 'credit']);
        DB::table('payments')->insert(['id' => 1, 'order_id' => 1, 'amount' => 100, 'payment_date' => '2026-11-01', 'payment_method' => 'cash']);

        PaymentAllocationService::allocateCustomerPayment(1, [['order_id' => 1, 'amount' => 100]]);
        $this->assertSame(1, CustomerPaymentAllocation::count());

        $this->expectException(RuntimeException::class);
        PaymentAllocationService::allocateCustomerPayment(1, [['order_id' => 1, 'amount' => 50]]);
    }

    public function test_supplier_allocation_blocks_overpay(): void
    {
        DB::table('suppliers')->insert(['id' => 1, 'name' => 'S1']);
        DB::table('purchases')->insert(['id' => 1, 'supplier_id' => 1, 'date' => '2026-11-01', 'status' => 'confirmed', 'total' => 200, 'subtotal' => 200]);
        DB::table('purchase_payments')->insert(['id' => 1, 'supplier_id' => 1, 'purchase_id' => 1, 'amount' => 200, 'payment_date' => '2026-11-02']);

        PaymentAllocationService::allocateSupplierPayment(1, [['purchase_id' => 1, 'amount' => 200]]);
        $this->assertSame(1, SupplierPaymentAllocation::count());

        $this->expectException(RuntimeException::class);
        PaymentAllocationService::allocateSupplierPayment(1, [['purchase_id' => 1, 'amount' => 1]]);
    }

    public function test_recurring_invoice_creates_draft_order_idempotently(): void
    {
        DB::table('customers')->insert(['id' => 5, 'name' => 'Recurring Cust']);

        $template = RecurringAccountingService::createTemplate([
            'name' => 'Monthly retainer',
            'document_type' => 'invoice',
            'frequency' => 'monthly',
            'starts_on' => '2026-11-01',
            'execution_mode' => 'draft',
            'payload' => ['customer_id' => 5, 'amount' => 2500],
        ]);

        $a = RecurringAccountingService::executeOccurrence($template, '2026-11-01');
        $b = RecurringAccountingService::executeOccurrence($template->fresh(), '2026-11-01');

        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, Order::count());
        $this->assertSame(Order::STATUS_DRAFT, Order::first()->status);
        $this->assertSame(0, DB::table('journal_entries')->count());
    }

    public function test_recurring_bill_creates_draft_purchase(): void
    {
        DB::table('suppliers')->insert(['id' => 7, 'name' => 'Recurring Supp']);

        $template = RecurringAccountingService::createTemplate([
            'name' => 'Rent bill',
            'document_type' => 'bill',
            'frequency' => 'monthly',
            'starts_on' => '2026-11-01',
            'execution_mode' => 'draft',
            'payload' => ['supplier_id' => 7, 'amount' => 10000],
        ]);

        $run = RecurringAccountingService::executeOccurrence($template, '2026-11-01');
        $this->assertSame('purchase', $run->generated_type);
        $this->assertSame(1, Purchase::count());
        $this->assertSame(Purchase::STATUS_DRAFT, Purchase::first()->status);
        $this->assertSame(0, DB::table('journal_entries')->count());
    }

    public function test_month_end_next_date_no_overflow(): void
    {
        $next = RecurringAccountingService::nextDate('2026-01-31', 'monthly')->format('Y-m-d');
        $this->assertSame('2026-02-28', $next);
    }
}
