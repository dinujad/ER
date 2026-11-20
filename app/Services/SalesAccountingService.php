<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\SaleReturn;
use Illuminate\Support\Facades\Log;

class SalesAccountingService
{
    public function synchronizeOrder(Order $order, bool $replaceExisting = false): void
    {
        if (!in_array($order->status, $this->recognizedOrderStatuses(), true)) {
            $this->reverseOrder($order->getKey());

            return;
        }

        $order->loadMissing(['items.variation', 'items.product.latestRecipe']);

        $saleEvent = $this->orderSaleEvent($order->getKey());
        $cogsEvent = $this->orderCogsEvent($order->getKey());

        if ($replaceExisting) {
            AccountingService::reverseEvent($saleEvent);
            $this->reverseOrderTaxEvents($order->getKey());
            AccountingService::reverseEvent($cogsEvent);
        }

        $reference = $order->invoice_number ?: $order->order_number ?: "Order #{$order->getKey()}";
        $date = $this->dateString($order->order_date);
        $saleAmount = $order->goodsValue();

        if ($saleAmount > 0) {
            $arId = AccountingService::accountId(AccountingService::GL_ACCOUNTS_RECV);
            $salesId = AccountingService::accountId(AccountingService::GL_SALES_REVENUE);

            if ($arId && $salesId) {
                AccountingService::postOnce(
                    $saleEvent,
                    Order::class,
                    $order->getKey(),
                    $arId,
                    $salesId,
                    $saleAmount,
                    $date,
                    "Sale — {$reference}",
                    $reference,
                    'sale',
                    ['order_id' => $order->getKey()]
                );
            } else {
                $this->missingAccounts('order sale', $order->getKey());
            }
        }

        // SSCL + VAT billed on top of the goods value: customer owes it (AR), business owes it to IRD.
        // Separate accounts (2320 SSCL, 2310 VAT output) feed the SSCL and VAT returns; 2300 is the fallback.
        foreach ([
            'sscl' => [round((float) ($order->invoice_sscl_amount ?? 0), 2), AccountingService::GL_SSCL_PAYABLE, 'SSCL on sale', 'sale_sscl'],
            'vat' => [round((float) ($order->invoice_vat_amount ?? 0), 2), AccountingService::GL_VAT_OUTPUT, 'Output VAT on sale', 'sale_vat'],
        ] as $kind => [$amount, $glCode, $label, $type]) {
            if ($amount <= 0) {
                continue;
            }
            $arId = AccountingService::accountId(AccountingService::GL_ACCOUNTS_RECV);
            $taxId = AccountingService::accountId($glCode) ?? AccountingService::accountId(AccountingService::GL_TAX_PAYABLE);
            if (!$arId || !$taxId) {
                $this->missingAccounts("order {$kind}", $order->getKey());
                continue;
            }
            AccountingService::postOnce(
                "order:{$order->getKey()}:{$kind}",
                Order::class,
                $order->getKey(),
                $arId,
                $taxId,
                $amount,
                $date,
                "{$label} — {$reference}",
                $reference,
                $type,
                ['order_id' => $order->getKey()]
            );
        }

        $cogsAmount = round($order->items->sum(
            fn ($item) => (float) $item->quantity * $this->unitCost($item->product, $item->variation)
        ), 2);

        if ($cogsAmount > 0) {
            $cogsId = AccountingService::accountId(AccountingService::GL_COST_OF_GOODS);
            $finishedGoodsId = AccountingService::accountId(AccountingService::GL_FINISHED_GOODS);

            if ($cogsId && $finishedGoodsId) {
                AccountingService::postOnce(
                    $cogsEvent,
                    Order::class,
                    $order->getKey(),
                    $cogsId,
                    $finishedGoodsId,
                    $cogsAmount,
                    $date,
                    "Cost of goods sold — {$reference}",
                    $reference,
                    'sale_cogs',
                    ['order_id' => $order->getKey()]
                );
            } else {
                $this->missingAccounts('order COGS', $order->getKey());
            }
        }
    }

    public function synchronizeOrderById(int $orderId, bool $replaceExisting = false): void
    {
        $order = Order::query()->with(['items.variation', 'items.product.latestRecipe'])->find($orderId);

        if ($order) {
            $this->synchronizeOrder($order, $replaceExisting);
        }
    }

    public function reverseOrder(int $orderId): void
    {
        AccountingService::reverseEvent($this->orderSaleEvent($orderId));
        $this->reverseOrderTaxEvents($orderId);
        AccountingService::reverseEvent($this->orderCogsEvent($orderId));
    }

    public function synchronizePayment(Payment $payment): void
    {
        if ($payment->isRefund()) {
            $this->postRefund($payment);

            return;
        }

        if ($payment->isCheque()) {
            return;
        }

        $amount = round((float) $payment->amount, 2);
        $settlementId = AccountingService::settlementAccountId($payment->payment_method);
        $arId = AccountingService::accountId(AccountingService::GL_ACCOUNTS_RECV);

        if ($amount <= 0) {
            return;
        }

        if (!$settlementId || !$arId) {
            $this->missingAccounts('customer payment', $payment->getKey());

            return;
        }

        $reference = $payment->order?->invoice_number
            ?: $payment->order?->order_number
            ?: $payment->reference_number
            ?: "Payment #{$payment->getKey()}";

        AccountingService::postOnce(
            $this->paymentReceiptEvent($payment->getKey()),
            Payment::class,
            $payment->getKey(),
            $settlementId,
            $arId,
            $amount,
            $this->dateString($payment->payment_date),
            "Customer payment — {$reference}",
            $payment->reference_number ?: $reference,
            'customer_payment',
            ['payment_id' => $payment->getKey(), 'order_id' => $payment->order_id]
        );
    }

    public function reversePayment(Payment $payment): void
    {
        AccountingService::reverseEvent($this->paymentReceiptEvent($payment->getKey()));
        AccountingService::reverseEvent($this->paymentRefundEvent($payment->getKey()));

        if ($payment->clearance_journal_txn) {
            AccountingService::reverseEntry($payment->clearance_journal_txn);
        }

        if ($payment->receipt_journal_txn) {
            AccountingService::reverseEntry($payment->receipt_journal_txn);
        }
    }

    public function synchronizeSaleReturn(SaleReturn $saleReturn, bool $replaceExisting = false): void
    {
        if ($saleReturn->status !== SaleReturn::STATUS_CONFIRMED) {
            $this->reverseSaleReturn($saleReturn->getKey());

            return;
        }

        $saleReturn->loadMissing(['items.variation', 'items.product.latestRecipe']);

        $salesEvent = $this->saleReturnRevenueEvent($saleReturn->getKey());
        $cogsEvent = $this->saleReturnCogsEvent($saleReturn->getKey());

        if ($replaceExisting) {
            AccountingService::reverseEvent($salesEvent);
            $this->reverseSaleReturnTaxEvents($saleReturn->getKey());
            AccountingService::reverseEvent($cogsEvent);
        }

        $reference = $saleReturn->return_no ?: "Sale return #{$saleReturn->getKey()}";
        $date = $this->dateString($saleReturn->return_date);
        $returnAmount = round((float) $saleReturn->total, 2);

        // Returned goods carry their share of the SSCL + VAT on the original invoice; the rest is revenue.
        $returnOrder = $saleReturn->relationLoaded('order')
            ? $saleReturn->order
            : ($saleReturn->order_id && \Illuminate\Support\Facades\Schema::hasTable('orders')
                ? Order::query()->find($saleReturn->order_id)
                : null);
        $shares = $this->returnTaxShares($returnOrder, $returnAmount);
        $revenueAmount = $returnOrder && $returnOrder->chargesInvoiceTax()
            ? round($returnOrder->withInvoiceTax($returnAmount) - $shares['sscl'] - $shares['vat'], 2)
            : $returnAmount;

        if ($revenueAmount > 0) {
            $salesReturnsId = AccountingService::accountId(AccountingService::GL_SALES_RETURNS);
            $arId = AccountingService::accountId(AccountingService::GL_ACCOUNTS_RECV);

            if ($salesReturnsId && $arId) {
                AccountingService::postOnce(
                    $salesEvent,
                    SaleReturn::class,
                    $saleReturn->getKey(),
                    $salesReturnsId,
                    $arId,
                    $revenueAmount,
                    $date,
                    "Sale return — {$reference}",
                    $reference,
                    'sale_return',
                    ['order_id' => $saleReturn->order_id]
                );
            } else {
                $this->missingAccounts('sale return', $saleReturn->getKey());
            }
        }

        foreach ($shares as $kind => $amount) {
            if ($amount <= 0) {
                continue;
            }
            $glCode = $kind === 'sscl' ? AccountingService::GL_SSCL_PAYABLE : AccountingService::GL_VAT_OUTPUT;
            $taxId = AccountingService::accountId($glCode) ?? AccountingService::accountId(AccountingService::GL_TAX_PAYABLE);
            $arId = AccountingService::accountId(AccountingService::GL_ACCOUNTS_RECV);
            if (!$taxId || !$arId) {
                $this->missingAccounts("sale return {$kind}", $saleReturn->getKey());
                continue;
            }
            AccountingService::postOnce(
                "sale-return:{$saleReturn->getKey()}:{$kind}",
                SaleReturn::class,
                $saleReturn->getKey(),
                $taxId,
                $arId,
                $amount,
                $date,
                ($kind === 'sscl' ? 'SSCL' : 'Output VAT') . " reversed on sale return — {$reference}",
                $reference,
                $kind === 'sscl' ? 'sale_return_sscl' : 'sale_return_vat',
                ['order_id' => $saleReturn->order_id]
            );
        }

        $returnedCost = round($saleReturn->items->sum(
            fn ($item) => (float) $item->quantity * $this->unitCost($item->product, $item->variation)
        ), 2);

        if ($returnedCost > 0) {
            $finishedGoodsId = AccountingService::accountId(AccountingService::GL_FINISHED_GOODS);
            $cogsId = AccountingService::accountId(AccountingService::GL_COST_OF_GOODS);

            if ($finishedGoodsId && $cogsId) {
                AccountingService::postOnce(
                    $cogsEvent,
                    SaleReturn::class,
                    $saleReturn->getKey(),
                    $finishedGoodsId,
                    $cogsId,
                    $returnedCost,
                    $date,
                    "Returned goods at cost — {$reference}",
                    $reference,
                    'sale_return_cogs',
                    ['order_id' => $saleReturn->order_id]
                );
            } else {
                $this->missingAccounts('sale return COGS', $saleReturn->getKey());
            }
        }
    }

    public function synchronizeSaleReturnById(int $saleReturnId, bool $replaceExisting = false): void
    {
        $saleReturn = SaleReturn::query()
            ->with(['items.variation', 'items.product.latestRecipe'])
            ->find($saleReturnId);

        if ($saleReturn) {
            $this->synchronizeSaleReturn($saleReturn, $replaceExisting);
        }
    }

    public function reverseSaleReturn(int $saleReturnId): void
    {
        AccountingService::reverseEvent($this->saleReturnRevenueEvent($saleReturnId));
        $this->reverseSaleReturnTaxEvents($saleReturnId);
        AccountingService::reverseEvent($this->saleReturnCogsEvent($saleReturnId));
    }

    private function postRefund(Payment $payment): void
    {
        $amount = round((float) $payment->amount, 2);
        $arId = AccountingService::accountId(AccountingService::GL_ACCOUNTS_RECV);
        $settlementId = AccountingService::settlementAccountId($payment->payment_method, 'out');

        if ($amount <= 0) {
            return;
        }

        if (!$arId || !$settlementId) {
            $this->missingAccounts('customer refund', $payment->getKey());

            return;
        }

        $reference = $payment->saleReturn?->return_no
            ?: $payment->order?->invoice_number
            ?: $payment->order?->order_number
            ?: $payment->reference_number
            ?: "Refund #{$payment->getKey()}";

        AccountingService::postOnce(
            $this->paymentRefundEvent($payment->getKey()),
            Payment::class,
            $payment->getKey(),
            $arId,
            $settlementId,
            $amount,
            $this->dateString($payment->payment_date),
            "Customer refund — {$reference}",
            $payment->reference_number ?: $reference,
            'customer_refund',
            ['payment_id' => $payment->getKey(), 'order_id' => $payment->order_id]
        );
    }

    private function unitCost(?Product $product, ?ProductVariation $variation): float
    {
        $variationCost = (float) ($variation?->default_purchase_price ?? 0);
        if ($variationCost > 0) {
            return $variationCost;
        }

        $productCost = (float) ($product?->cost_price ?? 0);
        if ($productCost > 0) {
            return $productCost;
        }

        return max(0, (float) ($product?->latestRecipe?->production_cost ?? 0));
    }

    private function dateString(mixed $date): string
    {
        if ($date instanceof \DateTimeInterface) {
            return $date->format('Y-m-d');
        }

        return $date ? date('Y-m-d', strtotime((string) $date)) : now()->toDateString();
    }

    /** @return array<int, string> */
    private function recognizedOrderStatuses(): array
    {
        return [
            Order::STATUS_COMPLETED,
            Order::STATUS_PAID,
            Order::STATUS_CREDIT,
        ];
    }

    private function orderSaleEvent(int $id): string
    {
        return "order:{$id}:sale";
    }

    /**
     * SSCL and VAT share of a returned goods amount, so that goods + shares = the billed value of the return.
     *
     * @return array{sscl: float, vat: float}
     */
    public function returnTaxShares(?Order $order, float $returnGoods): array
    {
        if (!$order || $returnGoods <= 0 || !$order->chargesInvoiceTax() || (float) $order->total_amount <= 0) {
            return ['sscl' => 0.0, 'vat' => 0.0];
        }
        // Same share of each tax as the returned item value is of the item total (works for added and included tax).
        $share = $returnGoods / (float) $order->total_amount;

        return [
            'sscl' => round((float) $order->invoice_sscl_amount * $share, 2),
            'vat' => round((float) $order->invoice_vat_amount * $share, 2),
        ];
    }

    /** Includes the pre-6.3 combined key so re-syncs never double count. */
    private function reverseOrderTaxEvents(int $id): void
    {
        foreach (['sale-tax', 'sscl', 'vat'] as $kind) {
            AccountingService::reverseEvent("order:{$id}:{$kind}");
        }
    }

    private function reverseSaleReturnTaxEvents(int $id): void
    {
        foreach (['tax', 'sscl', 'vat'] as $kind) {
            AccountingService::reverseEvent("sale-return:{$id}:{$kind}");
        }
    }

    private function orderCogsEvent(int $id): string
    {
        return "order:{$id}:cogs";
    }

    private function paymentReceiptEvent(int $id): string
    {
        return "payment:{$id}:receipt";
    }

    private function paymentRefundEvent(int $id): string
    {
        return "payment:{$id}:refund";
    }

    private function saleReturnRevenueEvent(int $id): string
    {
        return "sale-return:{$id}:revenue";
    }

    private function saleReturnCogsEvent(int $id): string
    {
        return "sale-return:{$id}:cogs";
    }

    private function missingAccounts(string $event, int $sourceId): void
    {
        Log::warning("SalesAccountingService: missing chart account for {$event}", [
            'source_id' => $sourceId,
        ]);
    }
}
