<?php

namespace App\Services;

use App\Models\ManufacturingJob;
use App\Models\ManufacturingJobAdditionalCost;
use App\Models\MaterialIssue;
use App\Models\MaterialStockMovement;
use App\Models\ProductVariation;
use App\Models\StockLedger;
use Illuminate\Support\Facades\Log;

class InventoryAccountingService
{
    private const GL_OWNER_EQUITY = '3000';

    public function postMaterialIssue(MaterialIssue $issue): void
    {
        $issue->loadMissing('material');

        $quantity = abs((float) $issue->quantity_issued);
        $unitCost = $issue->effectiveUnitCost();
        $amount = $issue->line_cost !== null
            ? abs((float) $issue->line_cost)
            : round($quantity * $unitCost, 2);

        if ($quantity <= 0 || $unitCost <= 0 || $amount <= 0) {
            return;
        }

        $this->post(
            $this->materialIssueEventKey($issue),
            MaterialIssue::class,
            $issue->id,
            AccountingService::GL_WORK_IN_PROGRESS,
            AccountingService::GL_RAW_MATERIALS,
            $amount,
            $issue->created_at?->toDateString() ?? now()->toDateString(),
            'Material issued to production',
            $issue->job?->job_number ?? "Material issue #{$issue->id}",
            'manufacturing_material_issue'
        );
    }

    public function reverseMaterialIssue(MaterialIssue $issue): void
    {
        AccountingService::reverseEvent($this->materialIssueEventKey($issue));
    }

    public function postAdditionalCost(ManufacturingJobAdditionalCost $cost): void
    {
        $amount = round((float) $cost->amount, 2);
        if ($amount <= 0) {
            return;
        }

        $this->post(
            $this->additionalCostEventKey($cost),
            ManufacturingJobAdditionalCost::class,
            $cost->id,
            AccountingService::GL_WORK_IN_PROGRESS,
            AccountingService::GL_CASH,
            $amount,
            $cost->created_at?->toDateString() ?? now()->toDateString(),
            'Manufacturing cost – ' . $cost->categoryLabel(),
            $cost->job?->job_number ?? "Manufacturing cost #{$cost->id}",
            'manufacturing_additional_cost'
        );
    }

    public function replaceAdditionalCost(ManufacturingJobAdditionalCost $cost): void
    {
        $this->reverseAdditionalCost($cost);
        $this->postAdditionalCost($cost);
    }

    public function reverseAdditionalCost(ManufacturingJobAdditionalCost $cost): void
    {
        AccountingService::reverseEvent($this->additionalCostEventKey($cost));
    }

    public function replaceMaterialIssue(MaterialIssue $issue): void
    {
        $this->reverseMaterialIssue($issue);
        $this->postMaterialIssue($issue);
    }

    public function postMaterialMovement(MaterialStockMovement $movement): void
    {
        if (!in_array($movement->movement_type, [
            MaterialStockMovement::TYPE_OPENING,
            MaterialStockMovement::TYPE_ADJUSTMENT,
        ], true)) {
            return;
        }

        $movement->loadMissing('material');
        $quantity = (float) $movement->quantity;
        $unitCost = (float) ($movement->material?->unit_price ?? 0);
        $amount = round(abs($quantity) * $unitCost, 2);

        if ($quantity == 0.0 || $unitCost <= 0 || $amount <= 0) {
            return;
        }

        $isIncrease = $quantity > 0;
        $counterpart = $movement->movement_type === MaterialStockMovement::TYPE_OPENING || $isIncrease
            ? self::GL_OWNER_EQUITY
            : AccountingService::GL_OTHER_EXPENSE;

        $this->post(
            $this->materialMovementEventKey($movement),
            MaterialStockMovement::class,
            $movement->id,
            $isIncrease ? AccountingService::GL_RAW_MATERIALS : $counterpart,
            $isIncrease ? $counterpart : AccountingService::GL_RAW_MATERIALS,
            $amount,
            $movement->created_at?->toDateString() ?? now()->toDateString(),
            MaterialStockMovement::typeLabel($movement->movement_type),
            $movement->reference_label ?? "Material movement #{$movement->id}",
            'material_inventory_adjustment'
        );
    }

    public function reverseMaterialMovement(MaterialStockMovement $movement): void
    {
        AccountingService::reverseEvent($this->materialMovementEventKey($movement));
    }

    public function postStockLedger(StockLedger $ledger): void
    {
        if ($this->isManufacturingReceipt($ledger)) {
            $this->postManufacturingReceipt($ledger);
            return;
        }

        if (!in_array($ledger->transaction_type, ['opening', 'adjustment'], true)) {
            return;
        }

        $unitCost = $this->productUnitCost($ledger);
        $quantity = (float) $ledger->quantity;
        $amount = round(abs($quantity) * $unitCost, 2);

        if ($quantity == 0.0 || $unitCost <= 0 || $amount <= 0) {
            return;
        }

        $isIncrease = $quantity > 0;
        $counterpart = $ledger->transaction_type === 'opening' || $isIncrease
            ? self::GL_OWNER_EQUITY
            : AccountingService::GL_OTHER_EXPENSE;

        $this->post(
            $this->stockLedgerEventKey($ledger),
            StockLedger::class,
            $ledger->id,
            $isIncrease ? AccountingService::GL_FINISHED_GOODS : $counterpart,
            $isIncrease ? $counterpart : AccountingService::GL_FINISHED_GOODS,
            $amount,
            $ledger->created_at?->toDateString() ?? now()->toDateString(),
            $ledger->transaction_type === 'opening' ? 'Finished goods opening stock' : 'Finished goods stock adjustment',
            "Stock ledger #{$ledger->id}",
            'finished_goods_adjustment'
        );
    }

    public function reverseStockLedger(StockLedger $ledger): void
    {
        if ($this->isManufacturingReceipt($ledger)) {
            AccountingService::reverseEvent($this->overheadAllocationEventKey($ledger));
        }

        AccountingService::reverseEvent($this->stockLedgerEventKey($ledger));
    }

    private function postManufacturingReceipt(StockLedger $ledger): void
    {
        $job = ManufacturingJob::with([
            'materialIssues.material',
            'packingBatches.materialIssues.material',
            'additionalCosts',
            'product',
        ])->find($ledger->reference_id);

        $quantity = (float) $ledger->quantity;
        if (!$job || $quantity <= 0) {
            return;
        }

        $breakdown = $job->costBreakdown();
        $overheadPerPiece = (float) ($breakdown['overhead_per_piece'] ?? 0);
        $unitCostWithOverhead = (float) ($breakdown['cost_per_unit_with_overhead'] ?? $job->costPerUnit());
        $overheadAmount = round($overheadPerPiece * $quantity, 2);
        $totalAmount = round($unitCostWithOverhead * $quantity, 2);
        $date = $ledger->created_at?->toDateString() ?? now()->toDateString();
        $reference = $job->job_number ?? "Manufacturing job #{$job->id}";

        if ($overheadAmount > 0) {
            $this->post(
                $this->overheadAllocationEventKey($ledger),
                StockLedger::class,
                $ledger->id,
                AccountingService::GL_WORK_IN_PROGRESS,
                AccountingService::GL_GENERAL_EXPENSE,
                $overheadAmount,
                $date,
                'Manufacturing overhead allocated (simple average)',
                $reference,
                'manufacturing_overhead_allocation'
            );
        }

        if ($unitCostWithOverhead <= 0 || $totalAmount <= 0) {
            return;
        }

        $this->post(
            $this->stockLedgerEventKey($ledger),
            StockLedger::class,
            $ledger->id,
            AccountingService::GL_FINISHED_GOODS,
            AccountingService::GL_WORK_IN_PROGRESS,
            $totalAmount,
            $date,
            'Finished goods received from production',
            $reference,
            'manufacturing_finished_goods'
        );
    }

    private function isManufacturingReceipt(StockLedger $ledger): bool
    {
        return $ledger->transaction_type === 'transfer_in'
            && $ledger->reference_type === ManufacturingJob::class
            && (float) $ledger->quantity > 0;
    }

    private function productUnitCost(StockLedger $ledger): float
    {
        if ($ledger->product_variation_id) {
            $variationCost = (float) (ProductVariation::query()
                ->whereKey($ledger->product_variation_id)
                ->value('default_purchase_price') ?? 0);
            if ($variationCost > 0) {
                return $variationCost;
            }
        }

        $ledger->loadMissing('product');

        return (float) ($ledger->product?->cost_price ?? 0);
    }

    private function post(
        string $eventKey,
        string $sourceType,
        int $sourceId,
        string $debitGl,
        string $creditGl,
        float $amount,
        string $date,
        string $name,
        string $reference,
        string $transactionType
    ): void {
        $debitId = AccountingService::accountId($debitGl);
        $creditId = AccountingService::accountId($creditGl);

        if (!$debitId || !$creditId) {
            Log::warning('Inventory accounting skipped: chart account unavailable', [
                'event_key' => $eventKey,
                'debit_gl' => $debitGl,
                'credit_gl' => $creditGl,
            ]);
            return;
        }

        AccountingService::postOnce(
            $eventKey,
            $sourceType,
            $sourceId,
            $debitId,
            $creditId,
            $amount,
            $date,
            $name,
            $reference,
            $transactionType
        );
    }

    private function materialIssueEventKey(MaterialIssue $issue): string
    {
        return "inventory:material-issue:{$issue->id}";
    }

    private function additionalCostEventKey(ManufacturingJobAdditionalCost $cost): string
    {
        return "inventory:manufacturing-additional-cost:{$cost->id}";
    }

    private function materialMovementEventKey(MaterialStockMovement $movement): string
    {
        return "inventory:material-movement:{$movement->id}";
    }

    private function stockLedgerEventKey(StockLedger $ledger): string
    {
        return "inventory:stock-ledger:{$ledger->id}";
    }

    private function overheadAllocationEventKey(StockLedger $ledger): string
    {
        return "inventory:manufacturing-overhead:ledger:{$ledger->id}";
    }
}
