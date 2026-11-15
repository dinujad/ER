<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Stub base class — satisfies Modules\Accounting\Entities\Transaction extends App\Transaction.
 * Dermas has no `transactions` table (UltimatePOS concept); methods return safe defaults.
 */
class Transaction extends Model
{
    protected $table = 'transactions';

    protected $guarded = ['id'];

    protected $appends = [];

    public function scopeForBusiness($query, $business_id = null)
    {
        return $query->where('transactions.business_id', $business_id ?? session('business.id'));
    }
}
