<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleDailyRunItem extends Model
{
    protected $fillable = ['vehicle_daily_run_id', 'type', 'name', 'cost', 'slip_path'];

    protected $casts = ['cost' => 'decimal:2'];

    public function run(): BelongsTo
    {
        return $this->belongsTo(VehicleDailyRun::class, 'vehicle_daily_run_id');
    }
}
