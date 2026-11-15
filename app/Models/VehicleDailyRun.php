<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleDailyRun extends Model
{
    protected $fillable = [
        'vehicle_id',
        'driver_id',
        'run_date',
        'start_meter_photo_path',
        'start_km',
        'end_km',
        'start_route',
        'end_route',
        'google_map_link',
        'distance_km',
        'fuel_received',
        'fuel_receipt_path',
        'meter_photo_path',
        'slip_path',
        'notes',
        'status',
    ];

    protected $casts = [
        'run_date' => 'date',
        'start_km' => 'decimal:2',
        'end_km' => 'decimal:2',
        'distance_km' => 'decimal:2',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function items()
    {
        return $this->hasMany(VehicleDailyRunItem::class, 'vehicle_daily_run_id');
    }

    public function totalCost(): float
    {
        return (float) $this->items->sum('cost');
    }
}
