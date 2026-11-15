<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcctCostAllocationLine extends Model
{
    protected $table = 'acct_cost_allocation_lines';

    protected $fillable = [
        'allocation_id', 'cost_center_id', 'amount', 'percentage',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'percentage' => 'decimal:4',
    ];

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(AcctCostCenter::class, 'cost_center_id');
    }

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(AcctCostAllocation::class, 'allocation_id');
    }
}