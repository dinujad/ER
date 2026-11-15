<?php

namespace App\Observers;

use App\Models\PurchaseReturn;
use App\Services\AccountingService;

class PurchaseReturnObserver
{
    public function deleted(PurchaseReturn $return): void
    {
        AccountingService::reverseEvent("purchase-return:{$return->id}:confirmed");
    }
}
