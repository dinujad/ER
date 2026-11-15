<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\StockLedger;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SaleReturnService
{
    /**
     * @param  array<int, array{order_item_id: int, quantity: float}>  $lines
     * @param  array{refund_amount?: float, refund_method?: string, refund_reference?: string|null, refund_notes?: string|null}  $refund
     */
    public function createReturn(
        Order $order,
        array $lines,
        string $returnDate,
        ?string $reason,
        ?int $userId,
        array $refund = []
    ): SaleReturn {
        if (!$order->canAcceptSaleReturn()) {
            throw new InvalidArgumentException('Only afterchecking / completed / paid / credit sales can be returned.');
        }

        $order->load(['items.product', 'items.variation', 'customer', 'salesPerson', 'payments']);

        $dueBefore = (float) $order->due_amount;

        return DB::transaction(function () use ($order, $lines, $returnDate, $reason, $userId, $refund, $dueBefore) {
            $channel = $order->stockChannel();

            $return = SaleReturn::create([
                'return_no' => SaleReturn::generateReturnNo(),
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'return_date' => $returnDate,
                'channel' => $channel,
                'status' => SaleReturn::STATUS_CONFIRMED,
                'reason' => $reason,
                'created_by' => $userId,
            ]);

            $subtotal = 0.0;

            foreach ($lines as $line) {
                $qty = round((float) ($line['quantity'] ?? 0), 2);
                if ($qty <= 0) {
                    continue;
                }

                /** @var OrderItem|null $item */
                $item = $order->items->firstWhere('id', (int) $line['order_item_id']);
                if (!$item) {
                    throw new InvalidArgumentException('Invalid order line.');
                }

                $maxReturnable = $item->returnableQuantity();
                if ($qty > $maxReturnable + 0.0001) {
                    $label = $item->product?->name ?? 'Item';
                    if ($item->variation) {
                        $label .= ' (' . $item->variation->variation_name . ')';
                    }
                    throw new InvalidArgumentException(
                        "Cannot return more than {$maxReturnable} for {$label}."
                    );
                }

                $listUnit = round((float) $item->unit_price, 2);
                $effectiveUnit = $item->effectiveReturnUnitPrice();
                $lineTotal = round($qty * $effectiveUnit, 2);
                $subtotal += $lineTotal;

                SaleReturnItem::create([
                    'sale_return_id' => $return->id,
                    'order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_variation_id' => $item->product_variation_id,
                    'quantity' => $qty,
                    'unit_price' => $effectiveUnit,
                    'list_unit_price' => $listUnit,
                    'effective_unit_price' => $effectiveUnit,
                    'line_total' => $lineTotal,
                ]);

                StockLedger::create([
                    'product_id' => $item->product_id,
                    'product_variation_id' => $item->product_variation_id,
                    'channel' => $channel,
                    'transaction_type' => 'sale_return',
                    'quantity' => $qty,
                    'reference_type' => SaleReturn::class,
                    'reference_id' => $return->id,
                    'notes' => 'Sale return ' . $return->return_no . ' — order ' . ($order->order_number ?? $order->id),
                    'created_by' => $userId,
                ]);

                $item->increment('quantity_returned', $qty);
            }

            if ($subtotal <= 0) {
                throw new InvalidArgumentException('Add at least one item with return quantity.');
            }

            // Wholesale invoices keep 30% claim credit. Retail / POS gets cash back, not credit.
            $claimable = $channel === SaleReturn::CHANNEL_RETAIL
                ? 0.0
                : app(SaleReturnClaimService::class)->claimableFromReturnTotal($subtotal);

            $return->update([
                'subtotal' => round($subtotal, 2),
                'total' => round($subtotal, 2),
                'claimable_amount' => $claimable,
                'claimed_amount' => 0,
            ]);

            $order->unsetRelation('saleReturns');
            $order->load('saleReturns');

            // Reduce customer due if unpaid portion shrinks after return.
            $dueAfter = (float) $order->fresh()->due_amount;
            $dueReduction = max(0, round($dueBefore - $dueAfter, 2));
            if ($dueReduction > 0 && $order->customer) {
                $newBalance = max(0, round((float) $order->customer->due_balance - $dueReduction, 2));
                $order->customer->update(['due_balance' => $newBalance]);
            }

            $refundAmount = round((float) ($refund['refund_amount'] ?? 0), 2);
            $autoRefund = !empty($refund['auto_refund']) || $channel === SaleReturn::CHANNEL_RETAIL;
            if ($autoRefund && $refundAmount <= 0) {
                $order->refresh();
                $order->load(['payments', 'saleReturns']);
                $refundAmount = round((float) $order->refundable_to_customer, 2);
            }
            if ($refundAmount > 0) {
                $order->refresh();
                $order->load('payments');
                $maxRefund = (float) $order->refundable_to_customer;
                if ($refundAmount > $maxRefund + 0.0001) {
                    throw new InvalidArgumentException(
                        'Refund cannot exceed refundable amount Rs ' . number_format($maxRefund, 2) . '.'
                    );
                }

                $method = strtolower(trim((string) ($refund['refund_method'] ?? 'cash')));
                if (!in_array($method, ['cash', 'card', 'bank_transfer', 'cheque'], true)) {
                    $method = 'cash';
                }

                Payment::create([
                    'order_id' => $order->id,
                    'entry_type' => Payment::ENTRY_REFUND,
                    'sale_return_id' => $return->id,
                    'amount' => $refundAmount,
                    'payment_date' => $returnDate,
                    'payment_method' => $method,
                    'reference_number' => $refund['refund_reference'] ?? $return->return_no,
                    'notes' => $refund['refund_notes'] ?? ('Customer refund for ' . $return->return_no),
                ]);
            }

            return $return->fresh(['items.product', 'items.variation', 'order', 'customer']);
        });
    }
}
