<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MfgOverheadRule extends Model
{
    protected $table = 'mfg_overhead_rules';

    protected $fillable = [
        'name', 'business_id', 'allocation_base', 'rate', 'percentage',
        'overhead_pool_account_id', 'applied_clearing_account_id', 'wip_account_id',
        'effective_from', 'effective_to', 'status', 'approved_by', 'approved_at', 'notes',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'percentage' => 'decimal:4',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'approved_at' => 'datetime',
    ];

    public function runs(): HasMany
    {
        return $this->hasMany(MfgOverheadRun::class, 'rule_id');
    }
}