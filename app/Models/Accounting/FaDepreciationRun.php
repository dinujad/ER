<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class FaDepreciationRun extends Model
{
    protected $table = 'fa_depreciation_runs';

    protected $fillable = [
        'business_id', 'run_key', 'run_date', 'period_year', 'period_month',
        'status', 'total_amount', 'asset_count', 'posted_by', 'posted_at',
        'reversed_by', 'reversed_at',
    ];

    protected $casts = [
        'run_date' => 'date',
        'total_amount' => 'decimal:4',
        'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];
}