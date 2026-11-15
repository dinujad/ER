<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class CustomerPaymentAllocation extends Model
{
    protected $table = 'customer_payment_allocations';

    protected $fillable = [
        'payment_id', 'order_id', 'amount', 'allocated_by',
    ];
}
