<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WashingStock extends Model
{
    protected $fillable = [
        'manufacturing_job_id',
        'quantity_received',
    ];

    protected $casts = [
        'quantity_received' => 'integer',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(ManufacturingJob::class, 'manufacturing_job_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(WashingStockMovement::class);
    }
}
