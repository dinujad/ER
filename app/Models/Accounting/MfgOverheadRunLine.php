<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MfgOverheadRunLine extends Model
{
    protected $table = 'mfg_overhead_run_lines';

    protected $fillable = [
        'run_id', 'job_id', 'base_quantity', 'allocated_amount', 'event_key', 'transaction_number',
    ];

    protected $casts = [
        'base_quantity' => 'decimal:4',
        'allocated_amount' => 'decimal:4',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(MfgOverheadRun::class, 'run_id');
    }
}