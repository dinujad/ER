<?php

namespace App\Observers;

use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Services\AccountingService;
use Illuminate\Support\Facades\Log;

class PurchasePaymentObserver
{
    public function created(PurchasePayment $payment): void
    {
        $paymentId = (int) $payment->id;
        defer(fn () => $this->sync($paymentId));
    }

    public function updated(PurchasePayment $payment): void
    {
        if (! $payment->wasChanged(['entry_type', 'amount', 'payment_method', 'payment_date', 'reference'])) {
            return;
        }

        $paymentId = (int) $payment->id;
        defer(function () use ($paymentId) {
            AccountingService::reverseEvent($this->eventKey($paymentId));
            $this->sync($paymentId);
        });
    }

    public function deleted(PurchasePayment $payment): void
    {
        $paymentId = (int) $payment->id;
        defer(fn () => AccountingService::reverseEvent($this->eventKey($paymentId)));
    }

    public function sync(int $paymentId): void
    {
        $payment = PurchasePayment::query()->with(['purchase', 'supplier'])->find($paymentId);
        if (! $payment || (float) $payment->amount <= 0) {
            return;
        }

        if ($payment->purchase_id) {
            $purchase = $payment->purchase;
            if (! $purchase || $purchase->status !== Purchase::STATUS_CONFIRMED) {
                return;
            }
        }

        $this->post($payment);
    }

    public function reverse(int $paymentId): void
    {
        AccountingService::reverseEvent($this->eventKey($paymentId));
    }

    private function post(PurchasePayment $payment): void
    {
        try {
            $payableId = AccountingService::accountId(AccountingService::GL_ACCOUNTS_PAY);
            $settlementId = AccountingService::settlementAccountId($payment->payment_method, 'out');

            if (! $payableId || ! $settlementId) {
                Log::warning('PurchasePaymentObserver: chart of accounts not seeded yet');
                return;
            }

            $isRefund = $payment->isRefund();
            $supplierName = $payment->supplier?->name ?? 'Supplier';
            $date = $payment->payment_date instanceof \DateTimeInterface
                ? $payment->payment_date->format('Y-m-d')
                : (string) $payment->payment_date;

            AccountingService::postOnce(
                $this->eventKey((int) $payment->id),
                PurchasePayment::class,
                (int) $payment->id,
                $isRefund ? $settlementId : $payableId,
                $isRefund ? $payableId : $settlementId,
                (float) $payment->amount,
                $date,
                ($isRefund ? 'Supplier refund – ' : 'Supplier payment – ') . $supplierName,
                $payment->reference ?? "PP-{$payment->id}",
                $isRefund ? 'supplier_refund' : 'purchase_payment'
            );
        } catch (\Throwable $e) {
            Log::error('PurchasePaymentObserver: accounting post failed', [
                'purchase_payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function eventKey(int $paymentId): string
    {
        return "purchase-payment:{$paymentId}:entry";
    }
}
