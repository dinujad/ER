<?php

namespace App\Observers;

use App\Models\MaterialStockMovement;
use App\Services\InventoryAccountingService;

class MaterialStockMovementAccountingObserver
{
    public function __construct(
        private readonly InventoryAccountingService $accounting
    ) {}

    public function created(MaterialStockMovement $movement): void
    {
        $this->accounting->postMaterialMovement($movement);
    }

    public function deleted(MaterialStockMovement $movement): void
    {
        $this->accounting->reverseMaterialMovement($movement);
    }
}
