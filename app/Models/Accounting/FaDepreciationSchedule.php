<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaDepreciationSchedule extends Model
{
    protected $table = 'fa_depreciation_schedules';

    protected $fillable = [
        'asset_id', 'period_year', 'period_month', 'period_end',
        'scheduled_amount', 'accumulated_after', 'nbv_after',
        'status', 'depreciation_run_id', 'transaction_number',
    ];

    protected $casts = [
        'period_end' => 'date',
        'scheduled_amount' => 'decimal:4',
        'accumulated_after' => 'decimal:4',
        'nbv_after' => 'decimal:4',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(FaAsset::class, 'asset_id');
    }
}