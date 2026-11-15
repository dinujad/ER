<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    protected $fillable = [
        'name',
        'number_plate',
        'type',
        'driver_id',
        'status',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function driverAssignments(): HasMany
    {
        return $this->hasMany(VehicleDriverAssignment::class);
    }

    public function dailyRuns(): HasMany
    {
        return $this->hasMany(VehicleDailyRun::class);
    }

    public function assignedDriverForDate(Carbon|string|null $date = null): ?Driver
    {
        $date = $date ? Carbon::parse($date)->toDateString() : now()->toDateString();

        $assignment = $this->driverAssignments()
            ->whereDate('assignment_date', $date)
            ->with('driver')
            ->first();

        return $assignment?->driver ?? $this->driver;
    }
}
