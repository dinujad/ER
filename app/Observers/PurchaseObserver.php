<?php

namespace App\Observers;

use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Services\AccountingService;
use Illuminate\Support\Facades\Log;

class PurchaseObserver
{
    public function created(Purchase $purchase): void
    {
        if ($purchase->status !== Purchase::STATUS_CONFIRMED) {
            return;
        }

        $purchaseId = (int) $purchase->id;
        defer(fn () => $this->syncConfirmed($purchaseId));
    }

    public function backfill(Purchase $purchase): void
    {
        if ($purchase->status === Purchase::STATUS_CONFIRMED) {
            $this->syncConfirmed((int) $purchase->id);
        }
    }

    public function updated(Purchase $purchase): void
    {
        $wasConfirmed = $purchase->getOriginal('status') === Purchase::STATUS_CONFIRMED;
        $isConfirmed = $purchase->status === Purchase::STATUS_CONFIRMED;
        $isCancelled = $purchase->status === Purchase::STATUS_CANCELLED;
        $accountingChanged = $purchase->wasChanged([
            'total',
            'date',
            'purchase_no',
            'reference_no',
            'supplier_id',
            'status',
        ]);
        $purchaseId = (int) $purchase->id;

        defer(function () use ($purchaseId, $wasConfirmed, $isConfirmed, $isCancelled, $accountingChanged) {
            try {
                if ($wasConfirmed && (! $isConfirmed || $isCancelled)) {
                    $this->reversePurchaseEntries($purchaseId);
                    return;
                }

                if (! $isConfirmed) {
                    return;
                }

                $this->syncConfirmed($purchaseId, $wasConfirmed && $accountingChanged);
            } catch (\Throwable $e) {
                Log::error('PurchaseObserver: failed to update journal entry', [
                    'purchase_id' => $purchaseId,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    public function deleted(Purchase $purchase): void
    {
        $purchaseId = (int) $purchase->id;
        defer(fn () => $this->reversePurchaseEntries($purchaseId));
    }

    private function syncConfirmed(int $purchaseId, bool $replaceExisting = false): void
    {
        $purchase = Purchase::query()->with(['supplier', 'payments'])->find($purchaseId);
        if (! $purchase || $purchase->status !== Purchase::STATUS_CONFIRMED) {
            return;
        }

        if ($replaceExisting) {
            $this->reversePurchaseEntries($purchaseId);
        }

        $this->postPurchaseEntries($purchase);
        $this->syncPayments($purchaseId);
        $this->syncHeaderPayment($purchase);
    }

    private function postPurchaseEntries(Purchase $purchase): void
    {
        try {
            $inventoryId = AccountingService::accountId(AccountingService::GL_RAW_MATERIALS);
            $payableId = AccountingService::accountId(AccountingService::GL_ACCOUNTS_PAY);

            if (! $inventoryId || ! $payableId) {
                Log::warning('PurchaseObserver: chart of accounts not seeded yet');
                return;
            }

            $amount = (float) $purchase->total;
            if ($amount <= 0) {
                return;
            }

            $supplierName = $purchase->supplier?->name ?? 'Supplier';
            $date = $purchase->date instanceof \DateTimeInterface
                ? $purchase->date->format('Y-m-d')
                : ($purchase->date ? (string) $purchase->date : now()->toDateString());
            $reference = $purchase->purchase_no ?? $purchase->reference_no ?? "PO-{$purchase->id}";

            $txn = AccountingService::postOnce(
                $this->purchaseEventKey($purchase->id),
                Purchase::class,
                (int) $purchase->id,
                $inventoryId,
                $payableId,
                $amount,
                $date,
                "Purchase – {$supplierName}",
                $reference,
                'purchase'
            );

            $purchase->updateQuietly(['accounting_txn_number' => $txn]);
        } catch (\Throwable $e) {
            Log::error('PurchaseObserver: failed to post journal entry', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function syncPayments(int $purchaseId): void
    {
        $observer = app(PurchasePaymentObserver::class);

        PurchasePayment::query()
            ->where('purchase_id', $purchaseId)
            ->pluck('id')
            ->each(fn ($id) => $observer->sync((int) $id));
    }

    private function syncHeaderPayment(Purchase $purchase): void
    {
        $fromRows = $purchase->paymentEntriesTotal();
        $header = (float) $purchase->paid_amount;
        $eventKey = "purchase:{$purchase->id}:initial-payment";

        if ($header <= 0 || abs($header - $fromRows) <= 0.009) {
            AccountingService::reverseEvent($eventKey);

            return;
        }

        try {
            $payableId = AccountingService::accountId(AccountingService::GL_ACCOUNTS_PAY);
            $settlementId = AccountingService::settlementAccountId($purchase->payment_method, 'out');

            if (! $payableId || ! $settlementId) {
                Log::warning('PurchaseObserver: chart of accounts not seeded yet');
                return;
            }

            $supplierName = $purchase->supplier?->name ?? 'Supplier';
            $date = $purchase->date instanceof \DateTimeInterface
                ? $purchase->date->format('Y-m-d')
                : ($purchase->date ? (string) $purchase->date : now()->toDateString());

            AccountingService::postOnce(
                $eventKey,
                Purchase::class,
                (int) $purchase->id,
                $payableId,
                $settlementId,
                $header,
                $date,
                "Supplier payment – {$supplierName}",
                $purchase->payment_reference ?? $purchase->purchase_no ?? "PO-{$purchase->id}",
                'purchase_payment'
            );
        } catch (\Throwable $e) {
            Log::error('PurchaseObserver: failed to post header payment', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function reversePurchaseEntries(int $purchaseId): void
    {
        AccountingService::reverseEvent($this->purchaseEventKey($purchaseId));
        AccountingService::reverseEvent("purchase:{$purchaseId}:initial-payment");

        PurchasePayment::query()
            ->where('purchase_id', $purchaseId)
            ->pluck('id')
            ->each(fn ($id) => AccountingService::reverseEvent("purchase-payment:{$id}:entry"));
    }

    private function purchaseEventKey(int $purchaseId): string
    {
        return "purchase:{$purchaseId}:confirmed";
    }
}
