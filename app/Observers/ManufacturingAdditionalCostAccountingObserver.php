<?php

namespace App\Observers;

use App\Models\ManufacturingJobAdditionalCost;
use App\Services\InventoryAccountingService;

class ManufacturingAdditionalCostAccountingObserver
{
    public function __construct(
        private readonly InventoryAccountingService $accounting
    ) {}

    public function created(ManufacturingJobAdditionalCost $cost): void
    {
        $this->accounting->postAdditionalCost($cost);
    }

    public function updated(ManufacturingJobAdditionalCost $cost): void
    {
        if ($cost->wasChanged(['category', 'amount'])) {
            $this->accounting->replaceAdditionalCost($cost);
        }
    }

    public function deleted(ManufacturingJobAdditionalCost $cost): void
    {
        $this->accounting->reverseAdditionalCost($cost);
    }
}
