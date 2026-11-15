<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Services\AccountingService;
use App\Services\SalesAccountingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccountingServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('accounting_postings');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('chart_of_accounts');

        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('gl_code')->unique();
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

        DB::table('chart_of_accounts')->insert([
            ['id' => 1, 'gl_code' => AccountingService::GL_CASH, 'active' => true],
            ['id' => 2, 'gl_code' => AccountingService::GL_ACCOUNTS_RECV, 'active' => true],
            ['id' => 3, 'gl_code' => AccountingService::GL_SALES_REVENUE, 'active' => true],
            ['id' => 4, 'gl_code' => AccountingService::GL_BANK, 'active' => true],
            ['id' => 5, 'gl_code' => AccountingService::GL_PDC_RECEIVABLE, 'active' => true],
            ['id' => 6, 'gl_code' => AccountingService::GL_GENERAL_EXPENSE, 'active' => true],
            ['id' => 7, 'gl_code' => AccountingService::GL_SALARY_EXPENSE, 'active' => true],
            ['id' => 8, 'gl_code' => AccountingService::GL_UTILITIES_EXPENSE, 'active' => true],
            ['id' => 9, 'gl_code' => AccountingService::GL_TRANSPORT_EXPENSE, 'active' => true],
        ]);
    }

    public function test_post_once_creates_one_balanced_pair_for_retries(): void
    {
        $first = AccountingService::postOnce(
            'payment:10:receipt',
            'payment',
            10,
            1,
            2,
            1250,
            '2026-07-29',
            'Customer receipt',
            'PAY-10',
            'payment'
        );
        $second = AccountingService::postOnce(
            'payment:10:receipt',
            'payment',
            10,
            1,
            2,
            1250,
            '2026-07-29',
            'Customer receipt',
            'PAY-10',
            'payment'
        );

        $this->assertSame($first, $second);
        $this->assertSame(2, DB::table('journal_entries')->count());
        $this->assertEquals(1250.0, (float) DB::table('journal_entries')->sum('debit'));
        $this->assertEquals(1250.0, (float) DB::table('journal_entries')->sum('credit'));
    }

    public function test_reverse_event_is_idempotent(): void
    {
        AccountingService::postOnce(
            'payment:11:receipt',
            'payment',
            11,
            1,
            2,
            500,
            '2026-07-29',
            'Customer receipt',
            'PAY-11',
            'payment'
        );

        AccountingService::reverseEvent('payment:11:receipt');
        AccountingService::reverseEvent('payment:11:receipt');

        $this->assertSame(2, DB::table('journal_entries')->where('reversed', true)->count());
        $this->assertTrue((bool) DB::table('accounting_postings')->value('reversed'));
    }

    public function test_completed_order_posts_receivable_and_sales_revenue(): void
    {
        $order = new Order([
            'status' => Order::STATUS_COMPLETED,
            'total_amount' => 3200,
            'order_date' => '2026-07-29',
            'order_number' => 'ORD-100',
        ]);
        $order->id = 100;
        $order->exists = true;
        $order->setRelation('items', collect());

        app(SalesAccountingService::class)->synchronizeOrder($order);

        $this->assertDatabaseHas('journal_entries', [
            'transaction_type' => 'sale',
            'chart_of_account_id' => 2,
            'debit' => 3200,
        ]);
        $this->assertDatabaseHas('journal_entries', [
            'transaction_type' => 'sale',
            'chart_of_account_id' => 3,
            'credit' => 3200,
        ]);
    }

    public function test_cash_customer_payment_reduces_receivable(): void
    {
        $payment = new Payment([
            'entry_type' => Payment::ENTRY_PAYMENT,
            'amount' => 800,
            'payment_method' => 'cash',
            'payment_date' => '2026-07-29',
        ]);
        $payment->id = 55;
        $payment->order_id = 100;
        $payment->exists = true;
        $payment->setRelation('order', null);

        app(SalesAccountingService::class)->synchronizePayment($payment);

        $this->assertDatabaseHas('journal_entries', [
            'transaction_type' => 'customer_payment',
            'chart_of_account_id' => 1,
            'debit' => 800,
        ]);
        $this->assertDatabaseHas('journal_entries', [
            'transaction_type' => 'customer_payment',
            'chart_of_account_id' => 2,
            'credit' => 800,
        ]);
    }

    public function test_expense_category_maps_to_correct_gl(): void
    {
        $this->assertSame(7, AccountingService::expenseAccountId('SALARIES', 'Salaries & Wages'));
        $this->assertSame(8, AccountingService::expenseAccountId('ELECTRICITY', 'Electricity'));
        $this->assertSame(9, AccountingService::expenseAccountId('OUTWARD_TRANSPORT', 'Delivery'));
        $this->assertSame(6, AccountingService::expenseAccountId('OFFICE', 'Office'));
    }

    public function test_customer_cheque_uses_pdc_and_supplier_cheque_uses_bank(): void
    {
        $this->assertSame(5, AccountingService::settlementAccountId('cheque', 'in'));
        $this->assertSame(4, AccountingService::settlementAccountId('cheque', 'out'));
        $this->assertSame(1, AccountingService::settlementAccountId('cash', 'out'));
    }

    public function test_customer_refund_cheque_credits_bank_not_pdc(): void
    {
        $payment = new Payment([
            'entry_type' => Payment::ENTRY_REFUND,
            'amount' => 500,
            'payment_method' => 'cheque',
            'payment_date' => '2026-08-29',
        ]);
        $payment->id = 88;
        $payment->order_id = 100;
        $payment->exists = true;
        $payment->setRelation('order', null);
        $payment->setRelation('saleReturn', null);

        app(SalesAccountingService::class)->synchronizePayment($payment);

        $this->assertDatabaseHas('journal_entries', [
            'transaction_type' => 'customer_refund',
            'chart_of_account_id' => 2,
            'debit' => 500,
        ]);
        $this->assertDatabaseHas('journal_entries', [
            'transaction_type' => 'customer_refund',
            'chart_of_account_id' => 4,
            'credit' => 500,
        ]);
        $this->assertDatabaseMissing('journal_entries', [
            'transaction_type' => 'customer_refund',
            'chart_of_account_id' => 5,
        ]);
    }

    public function test_collapse_duplicate_journals_keeps_one_live_pair(): void
    {
        AccountingService::postOnce(
            'purchase:1:confirmed',
            'purchase',
            1,
            1,
            2,
            100,
            '2026-08-29',
            'Purchase A',
            'PO-1',
            'purchase'
        );

        $second = AccountingService::postEntry(1, 2, 100, '2026-08-29', 'Purchase A copy', 'PO-1', 'purchase', [
            'source_type' => 'purchase',
            'source_id' => 1,
        ]);

        $this->assertSame(4, DB::table('journal_entries')->where('reversed', 0)->count());

        $result = AccountingService::collapseDuplicateJournals();

        $this->assertSame(1, $result['groups']);
        $this->assertSame(1, $result['reversed']);
        $this->assertSame(2, DB::table('journal_entries')->where('reversed', 0)->count());
        $this->assertSame(2, DB::table('journal_entries')->where('reversed', '!=', 0)->count());
        $this->assertContains($second, DB::table('journal_entries')->pluck('transaction_number')->all());
    }

    public function test_pending_posting_recovers_existing_live_journals(): void
    {
        $txn = AccountingService::postEntry(1, 2, 400, '2026-08-29', 'Orphan sale', 'ORD-9', 'sale', [
            'source_type' => Order::class,
            'source_id' => 9,
        ]);

        DB::table('accounting_postings')->insert([
            'event_key' => 'order:9:sale',
            'transaction_number' => 'PENDING',
            'source_type' => Order::class,
            'source_id' => 9,
            'event_type' => 'sale',
            'amount' => 400,
            'reversed' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $recovered = AccountingService::postOnce(
            'order:9:sale',
            Order::class,
            9,
            2,
            3,
            400,
            '2026-08-29',
            'Sale',
            'ORD-9',
            'sale'
        );

        $this->assertSame($txn, $recovered);
        $this->assertSame(2, DB::table('journal_entries')->where('reversed', 0)->count());
        $this->assertSame($txn, DB::table('accounting_postings')->where('event_key', 'order:9:sale')->value('transaction_number'));
    }

    public function test_reverse_entry_marks_matching_postings_reversed(): void
    {
        $txn = AccountingService::postOnce(
            'expense:22:entry',
            'expense',
            22,
            6,
            1,
            250,
            '2026-08-29',
            'Office expense',
            'EXP-22',
            'expense'
        );

        AccountingService::reverseEntry($txn);

        $this->assertSame(2, DB::table('journal_entries')->where('reversed', 1)->count());
        $this->assertTrue((bool) DB::table('accounting_postings')->where('event_key', 'expense:22:entry')->value('reversed'));
    }
}
