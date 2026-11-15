<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Manages order cheque lifecycle:
 *  Received → PDC account (not bank); reduces AR
 *  Deposited → status update when sent to bank
 *  Cleared → PDC → Bank
 *  Bounced → reverse entries; AR restored; notifications sent
 */
class ChequePaymentLifecycleService
{
    public function __construct(
        protected ChequePaymentNotifier $notifier,
    ) {}

    /** Called when a new cheque payment is recorded. */
    public function onChequeReceived(Payment $payment): void
    {
        if (!$payment->isCheque()) {
            return;
        }

        DB::transaction(function () use ($payment) {
            $payment->refresh();

            if ($payment->receipt_journal_txn) {
                return;
            }

            if ($payment->cheque_status && $payment->cheque_status !== Payment::CHEQUE_RECEIVED) {
                return;
            }

            $txn = $this->postReceiptEntry($payment);

            $payment->update([
                'cheque_status' => Payment::CHEQUE_RECEIVED,
                'receipt_journal_txn' => $txn,
            ]);
        });
    }

    public function markDeposited(Payment $payment, bool $syncBatch = true): void
    {
        $this->assertChequeAction($payment, 'deposit');

        if (!$payment->canMarkDeposited()) {
            throw new \InvalidArgumentException('This cheque cannot be marked as deposited.');
        }

        $payment->update([
            'cheque_status' => Payment::CHEQUE_DEPOSITED,
            'deposited_at' => now(),
        ]);

        if ($syncBatch) {
            $this->eachBatchSibling($payment, function (Payment $sibling) {
                if ($sibling->canMarkDeposited()) {
                    $this->markDeposited($sibling, false);
                }
            });
        }
    }

    public function markCleared(Payment $payment, bool $syncBatch = true): void
    {
        $this->assertChequeAction($payment, 'clear');

        if (!$payment->canMarkCleared()) {
            throw new \InvalidArgumentException('This cheque cannot be marked as cleared.');
        }

        DB::transaction(function () use ($payment) {
            $payment->refresh();

            $txn = $this->postClearanceEntry($payment);

            $payment->update([
                'cheque_status' => Payment::CHEQUE_CLEARED,
                'cleared_at' => now(),
                'clearance_journal_txn' => $txn,
                'deposited_at' => $payment->deposited_at ?? now(),
            ]);
        });

        if ($syncBatch) {
            $this->eachBatchSibling($payment, function (Payment $sibling) {
                if ($sibling->canMarkCleared()) {
                    $this->markCleared($sibling, false);
                }
            });
        }
    }

    public function markBounced(Payment $payment, string $reason, bool $syncBatch = true): void
    {
        $this->assertChequeAction($payment, 'bounce');

        if (!$payment->canMarkBounced()) {
            throw new \InvalidArgumentException('This cheque is already marked as bounced.');
        }

        $reason = trim($reason);
        if (strlen($reason) < 5) {
            throw new \InvalidArgumentException('Please provide a bounce reason (at least 5 characters).');
        }

        DB::transaction(function () use ($payment, $reason) {
            $payment->refresh();

            if ($payment->clearance_journal_txn) {
                AccountingService::reverseEntry($payment->clearance_journal_txn);
            }
            if ($payment->receipt_journal_txn) {
                AccountingService::reverseEntry($payment->receipt_journal_txn);
            }
            AccountingService::reverseEvent($this->clearanceEventKey($payment));
            AccountingService::reverseEvent($this->receiptEventKey($payment));

            $payment->update([
                'cheque_status' => Payment::CHEQUE_BOUNCED,
                'bounced_at' => now(),
                'bounce_reason' => $reason,
            ]);
        });

        if ($syncBatch) {
            defer(function () use ($payment) {
                try {
                    $fresh = Payment::with(['order.customer', 'order.salesPerson.employee'])->find($payment->id);
                    if ($fresh) {
                        $this->notifier->notifyChequeBounced($fresh, $fresh->bounce_reason ?? '');
                    }
                } catch (\Throwable $e) {
                    Log::error('Cheque bounce notification failed: '.$e->getMessage());
                }
            }, 'cheque-bounce-notify-'.$payment->id);

            $this->eachBatchSibling($payment, function (Payment $sibling) use ($reason) {
                if ($sibling->canMarkBounced()) {
                    $this->markBounced($sibling, $reason, false);
                }
            });
        }
    }

    /** One physical cheque split across orders moves together. */
    protected function eachBatchSibling(Payment $payment, callable $callback): void
    {
        if (!$payment->cheque_batch_id) {
            return;
        }

        Payment::query()
            ->where('cheque_batch_id', $payment->cheque_batch_id)
            ->where('id', '!=', $payment->id)
            ->get()
            ->each($callback);
    }

    protected function assertChequeAction(Payment $payment, string $action): void
    {
        if (!$payment->isCheque()) {
            throw new \InvalidArgumentException('This payment is not a cheque.');
        }
    }

    /**
     * Cheque received: DR PDC (uncleared), CR Accounts Receivable.
     */
    protected function postReceiptEntry(Payment $payment): ?string
    {
        if (!$this->accountingAvailable()) {
            return null;
        }

        $pdcId = AccountingService::accountId(AccountingService::GL_PDC_RECEIVABLE);
        $arId  = AccountingService::accountId(AccountingService::GL_ACCOUNTS_RECV);

        if (!$pdcId || !$arId) {
            Log::info('Cheque receipt: PDC or AR account not found; skipping journal entry.');

            return null;
        }

        $order = $payment->order;
        $ref = $order?->order_number ?? ('Payment #'.$payment->id);

        return AccountingService::postOnce(
            $this->receiptEventKey($payment),
            Payment::class,
            (int) $payment->id,
            $pdcId,
            $arId,
            (float) $payment->amount,
            $payment->payment_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'Cheque received — '.$ref,
            $payment->reference_number,
            'cheque_receipt',
            ['payment_id' => $payment->id, 'order_id' => $payment->order_id]
        );
    }

    /**
     * Cheque cleared: DR Bank, CR PDC.
     */
    protected function postClearanceEntry(Payment $payment): ?string
    {
        if (!$this->accountingAvailable()) {
            return null;
        }

        $bankId = AccountingService::accountId(AccountingService::GL_BANK);
        $pdcId  = AccountingService::accountId(AccountingService::GL_PDC_RECEIVABLE);

        if (!$bankId || !$pdcId) {
            Log::info('Cheque clearance: Bank or PDC account not found; skipping journal entry.');

            return null;
        }

        $order = $payment->order;
        $ref = $order?->order_number ?? ('Payment #'.$payment->id);

        return AccountingService::postOnce(
            $this->clearanceEventKey($payment),
            Payment::class,
            (int) $payment->id,
            $bankId,
            $pdcId,
            (float) $payment->amount,
            now()->format('Y-m-d'),
            'Cheque cleared — '.$ref,
            $payment->reference_number,
            'cheque_clearance',
            ['payment_id' => $payment->id, 'order_id' => $payment->order_id]
        );
    }

    protected function accountingAvailable(): bool
    {
        return Schema::hasTable('chart_of_accounts') && Schema::hasTable('journal_entries');
    }

    private function receiptEventKey(Payment $payment): string
    {
        return "payment:{$payment->id}:cheque-receipt";
    }

    private function clearanceEventKey(Payment $payment): string
    {
        return "payment:{$payment->id}:cheque-clearance";
    }
}
