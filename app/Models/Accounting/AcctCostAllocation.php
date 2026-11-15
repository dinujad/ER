<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcctCostAllocation extends Model
{
    protected $table = 'acct_cost_allocations';

    protected $fillable = [
        'business_id', 'name', 'allocation_date', 'source_type', 'source_id',
        'source_amount', 'status', 'created_by', 'notes',
    ];

    protected $casts = [
        'allocation_date' => 'date',
        'source_amount' => 'decimal:4',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(AcctCostAllocationLine::class, 'allocation_id');
    }
}