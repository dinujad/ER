<?php

namespace App\Observers;

use App\Models\MaterialIssue;
use App\Services\InventoryAccountingService;

class MaterialIssueAccountingObserver
{
    public function __construct(
        private readonly InventoryAccountingService $accounting
    ) {}

    public function created(MaterialIssue $issue): void
    {
        $this->accounting->postMaterialIssue($issue);
    }

    public function updated(MaterialIssue $issue): void
    {
        if ($issue->wasChanged(['material_id', 'quantity_issued'])) {
            $this->accounting->replaceMaterialIssue($issue);
        }
    }

    public function deleted(MaterialIssue $issue): void
    {
        $this->accounting->reverseMaterialIssue($issue);
    }
}
