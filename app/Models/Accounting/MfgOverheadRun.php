<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MfgOverheadRun extends Model
{
    protected $table = 'mfg_overhead_runs';

    protected $fillable = [
        'rule_id', 'business_id', 'period_from', 'period_to', 'status',
        'total_base', 'total_allocated', 'run_key', 'posted_by', 'posted_at', 'reversed_at',
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to' => 'date',
        'total_base' => 'decimal:4',
        'total_allocated' => 'decimal:4',
        'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(MfgOverheadRule::class, 'rule_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(MfgOverheadRunLine::class, 'run_id');
    }
}