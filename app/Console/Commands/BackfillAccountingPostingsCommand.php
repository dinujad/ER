<?php

namespace App\Console\Commands;

use App\Models\Expense;
use App\Models\ManufacturingJobAdditionalCost;
use App\Models\MaterialIssue;
use App\Models\MaterialStockMovement;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PayrollRun;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\PurchaseReturn;
use App\Models\SaleReturn;
use App\Models\SalesPersonExpense;
use App\Models\StockLedger;
use App\Models\VehicleDailyRunItem;
use App\Observers\ExpenseObserver;
use App\Observers\PayrollRunObserver;
use App\Observers\PurchaseObserver;
use App\Observers\PurchasePaymentObserver;
use App\Observers\SalesPersonExpenseObserver;
use App\Observers\VehicleDailyRunItemObserver;
use App\Services\AccountingService;
use App\Services\InventoryAccountingService;
use App\Services\SalesAccountingService;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillAccountingPostingsCommand extends Command
{
    protected $signature = 'accounting:backfill {--force : Run without confirmation}';

    protected $description = 'Safely create missing automatic journal entries for existing ERP transactions';

    public function handle(
        SalesAccountingService $sales,
        InventoryAccountingService $inventory
    ): int {
        if (!Schema::hasTable('accounting_postings')) {
            $this->error('Run php artisan migrate first.');
            return self::FAILURE;
        }

        if (!$this->option('force') && !$this->confirm('Backfill missing accounting entries for all existing transactions?')) {
            return self::SUCCESS;
        }

        $this->call('db:seed', ['--class' => ChartOfAccountsSeeder::class, '--force' => true]);

        Expense::query()->eachById(function (Expense $expense) {
            $this->reverseLegacyIfNeeded("expense:{$expense->id}:entry", $expense->accounting_txn_number);
            app(ExpenseObserver::class)->backfill($expense);
        });

        Purchase::query()->where('status', Purchase::STATUS_CONFIRMED)->eachById(function (Purchase $purchase) {
            $this->reverseLegacyIfNeeded("purchase:{$purchase->id}:confirmed", $purchase->accounting_txn_number);
            app(PurchaseObserver::class)->backfill($purchase);
        });

        PurchasePayment::query()->eachById(
            fn (PurchasePayment $payment) => app(PurchasePaymentObserver::class)->sync((int) $payment->id)
        );

        PurchaseReturn::query()->where('status', PurchaseReturn::STATUS_CONFIRMED)->eachById(function (PurchaseReturn $return) {
            $key = "purchase-return:{$return->id}:confirmed";
            $this->reverseLegacyIfNeeded($key, $return->accounting_txn_number);
            $debit = AccountingService::accountId(AccountingService::GL_ACCOUNTS_PAY);
            $credit = AccountingService::accountId(AccountingService::GL_RAW_MATERIALS);
            if ($debit && $credit && (float) $return->total > 0) {
                $txn = AccountingService::postOnce(
                    $key,
                    PurchaseReturn::class,
                    (int) $return->id,
                    $debit,
                    $credit,
                    (float) $return->total,
                    $return->return_date->format('Y-m-d'),
                    'Purchase return – ' . ($return->purchase?->purchase_no ?? ''),
                    $return->return_no,
                    'purchase_return'
                );
                $return->updateQuietly(['accounting_txn_number' => $txn]);
            }
        });

        Order::query()->whereIn('status', [
            Order::STATUS_COMPLETED,
            Order::STATUS_PAID,
            Order::STATUS_CREDIT,
        ])->eachById(fn (Order $order) => $sales->synchronizeOrder($order));

        SaleReturn::query()->where('status', SaleReturn::STATUS_CONFIRMED)
            ->eachById(fn (SaleReturn $return) => $sales->synchronizeSaleReturn($return));

        Payment::query()->eachById(function (Payment $payment) use ($sales) {
            if ($payment->isRefund()) {
                $sales->synchronizePayment($payment);
                return;
            }

            if ($payment->isCheque()) {
                $this->registerLegacyChequePosting($payment, 'receipt');
                $this->registerLegacyChequePosting($payment, 'clearance');
                return;
            }

            $sales->synchronizePayment($payment);
        });

        SalesPersonExpense::query()->eachById(
            fn (SalesPersonExpense $expense) => app(SalesPersonExpenseObserver::class)->backfill($expense)
        );
        VehicleDailyRunItem::query()->eachById(
            fn (VehicleDailyRunItem $item) => app(VehicleDailyRunItemObserver::class)->backfill($item)
        );
        PayrollRun::query()->where('status', PayrollRun::STATUS_FINALIZED)
            ->eachById(fn (PayrollRun $run) => app(PayrollRunObserver::class)->backfill($run));

        MaterialIssue::query()->eachById(fn (MaterialIssue $issue) => $inventory->postMaterialIssue($issue));
        ManufacturingJobAdditionalCost::query()
            ->eachById(fn (ManufacturingJobAdditionalCost $cost) => $inventory->postAdditionalCost($cost));
        MaterialStockMovement::query()
            ->eachById(fn (MaterialStockMovement $movement) => $inventory->postMaterialMovement($movement));
        StockLedger::query()->eachById(fn (StockLedger $ledger) => $inventory->postStockLedger($ledger));

        $this->info('Accounting backfill scheduled/completed. Re-running this command is safe.');
        return self::SUCCESS;
    }

    private function reverseLegacyIfNeeded(string $eventKey, ?string $transactionNumber): void
    {
        if (!$transactionNumber || DB::table('accounting_postings')->where('event_key', $eventKey)->exists()) {
            return;
        }

        AccountingService::reverseEntry($transactionNumber);
    }

    private function registerLegacyChequePosting(Payment $payment, string $stage): void
    {
        $txn = $stage === 'receipt' ? $payment->receipt_journal_txn : $payment->clearance_journal_txn;
        if ($payment->cheque_status === Payment::CHEQUE_BOUNCED) {
            return;
        }

        $key = "payment:{$payment->id}:cheque-{$stage}";
        if (!$txn) {
            if ($stage === 'clearance' && $payment->cheque_status !== Payment::CHEQUE_CLEARED) {
                return;
            }

            $debit = AccountingService::accountId(
                $stage === 'receipt' ? AccountingService::GL_PDC_RECEIVABLE : AccountingService::GL_BANK
            );
            $credit = AccountingService::accountId(
                $stage === 'receipt' ? AccountingService::GL_ACCOUNTS_RECV : AccountingService::GL_PDC_RECEIVABLE
            );
            if (!$debit || !$credit || (float) $payment->amount <= 0) {
                return;
            }

            $txn = AccountingService::postOnce(
                $key,
                Payment::class,
                (int) $payment->id,
                $debit,
                $credit,
                (float) $payment->amount,
                $payment->payment_date?->format('Y-m-d') ?? now()->toDateString(),
                $stage === 'receipt' ? 'Cheque received' : 'Cheque cleared',
                $payment->reference_number,
                "cheque_{$stage}",
                ['payment_id' => $payment->id, 'order_id' => $payment->order_id]
            );

            $payment->updateQuietly([
                $stage === 'receipt' ? 'receipt_journal_txn' : 'clearance_journal_txn' => $txn,
            ]);
            return;
        }

        DB::table('accounting_postings')->updateOrInsert(
            ['event_key' => $key],
            [
                'transaction_number' => $txn,
                'source_type' => Payment::class,
                'source_id' => $payment->id,
                'event_type' => "cheque_{$stage}",
                'amount' => $payment->amount,
                'reversed' => $payment->cheque_status === Payment::CHEQUE_BOUNCED,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
