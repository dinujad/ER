<?php

namespace App\Utils;

use Illuminate\Support\Collection;

class TransactionUtil
{
    public function getExpenseReport($business_id, $start = null, $end = null, $location_id = null)
    {
        return new Collection();
    }

    public function __call($name, $arguments)
    {
        return null;
    }
}
