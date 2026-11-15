<?php

namespace App\Services;

use App\Models\MaterialStockMovement;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseReturnService
{
    public function __construct(
        protected MaterialStockService $stockService
    ) {}
    /**
     * @param  array<int, array{purchase_item_id: int, quantity: float}>  $lines
     */
    public function createReturn(Purchase $purchase, array $lines, string $returnDate, ?string $reason, ?int $userId): PurchaseReturn
    {
        if ($purchase->status !== Purchase::STATUS_CONFIRMED) {
            throw new \InvalidArgumentException('Only confirmed purchases can be returned to supplier.');
        }

        $purchase->load('items.material');

        return DB::transaction(function () use ($purchase, $lines, $returnDate, $reason, $userId) {
            $return = PurchaseReturn::create([
                'return_no' => PurchaseReturn::generateReturnNo(),
                'purchase_id' => $purchase->id,
                'supplier_id' => $purchase->supplier_id,
                'return_date' => $returnDate,
                'status' => PurchaseReturn::STATUS_CONFIRMED,
                'reason' => $reason,
                'created_by' => $userId,
            ]);

            $subtotal = 0;

            foreach ($lines as $line) {
                $qty = round((float) ($line['quantity'] ?? 0), 2);
                if ($qty <= 0) {
                    continue;
                }

                /** @var PurchaseItem|null $item */
                $item = $purchase->items->firstWhere('id', (int) $line['purchase_item_id']);
                if (!$item) {
                    throw new \InvalidArgumentException('Invalid purchase line.');
                }

                $alreadyReturned = (float) $item->quantity_returned;
                $maxReturnable = round((float) $item->quantity - $alreadyReturned, 2);
                if ($qty > $maxReturnable + 0.0001) {
                    throw new \InvalidArgumentException(
                        "Cannot return more than {$maxReturnable} for {$item->material?->name} (purchased {$item->quantity}, already returned {$alreadyReturned})."
                    );
                }

                $material = $item->material;
                if (!$material) {
                    throw new \RuntimeException('Material missing on purchase line.');
                }

                $this->stockService->applyMovement(
                    $material,
                    -$qty,
                    MaterialStockMovement::TYPE_PURCHASE_RETURN,
                    PurchaseReturn::class,
                    $return->id,
                    $return->return_no,
                    'Return to supplier – PO ' . ($purchase->purchase_no ?? ''),
                    $userId,
                    true
                );

                $lineTotal = round($qty * (float) $item->buy_price, 2);
                $subtotal += $lineTotal;

                PurchaseReturnItem::create([
                    'purchase_return_id' => $return->id,
                    'purchase_item_id' => $item->id,
                    'material_id' => $item->material_id,
                    'quantity' => $qty,
                    'buy_price' => $item->buy_price,
                    'line_total' => $lineTotal,
                ]);

                $item->increment('quantity_returned', $qty);
            }

            if ($subtotal <= 0) {
                throw new \InvalidArgumentException('Add at least one item with return quantity.');
            }

            $return->update([
                'subtotal' => $subtotal,
                'total' => $subtotal,
            ]);

            $returnId = (int) $return->id;
            defer(fn () => $this->postAccountingReturnById($returnId));

            return $return->fresh(['items.material', 'purchase']);
        });
    }

    protected function postAccountingReturnById(int $returnId): void
    {
        $return = PurchaseReturn::query()->with('purchase')->find($returnId);
        if ($return) {
            $this->postAccountingReturn($return);
        }
    }

    protected function postAccountingReturn(PurchaseReturn $return): void
    {
        try {
            $debitId = AccountingService::accountId(AccountingService::GL_ACCOUNTS_PAY);
            $creditId = AccountingService::accountId(AccountingService::GL_RAW_MATERIALS);

            if (!$debitId || !$creditId) {
                return;
            }

            $txn = AccountingService::postOnce(
                "purchase-return:{$return->id}:confirmed",
                PurchaseReturn::class,
                (int) $return->id,
                $debitId,
                $creditId,
                (float) $return->total,
                $return->return_date->format('Y-m-d'),
                'Purchase return – ' . ($return->purchase?->purchase_no ?? ''),
                $return->return_no,
                'purchase_return'
            );

            if ($txn) {
                $return->updateQuietly(['accounting_txn_number' => $txn]);
            }
        } catch (\Throwable $e) {
            Log::error('PurchaseReturnService: accounting post failed', [
                'return_id' => $return->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
