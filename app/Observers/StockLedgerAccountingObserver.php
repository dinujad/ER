<?php

namespace App\Observers;

use App\Models\StockLedger;
use App\Services\InventoryAccountingService;

class StockLedgerAccountingObserver
{
    public function __construct(
        private readonly InventoryAccountingService $accounting
    ) {}

    public function created(StockLedger $ledger): void
    {
        $this->accounting->postStockLedger($ledger);
    }

    public function deleted(StockLedger $ledger): void
    {
        $this->accounting->reverseStockLedger($ledger);
    }
}
