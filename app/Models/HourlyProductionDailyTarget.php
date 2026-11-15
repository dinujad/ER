<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HourlyProductionDailyTarget extends Model
{
    protected $fillable = [
        'manufacturing_job_id',
        'production_date',
        'line_name',
        'target_quantity',
        'created_by',
    ];

    protected $casts = [
        'production_date' => 'date',
        'target_quantity' => 'integer',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(ManufacturingJob::class, 'manufacturing_job_id');
    }
}
