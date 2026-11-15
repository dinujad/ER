<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class SupplierPaymentAllocation extends Model
{
    protected $table = 'supplier_payment_allocations';

    protected $fillable = [
        'purchase_payment_id', 'purchase_id', 'amount', 'allocated_by',
    ];
}
