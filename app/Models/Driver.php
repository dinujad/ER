<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Driver extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'license_no',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Permanent default vehicle (fallback when no daily assignment). */
    public function vehicle(): HasOne
    {
        return $this->hasOne(Vehicle::class);
    }

    public function vehicleAssignments(): HasMany
    {
        return $this->hasMany(VehicleDriverAssignment::class);
    }

    public function dailyRuns(): HasMany
    {
        return $this->hasMany(VehicleDailyRun::class);
    }

    public function assignedVehicleForDate(Carbon|string|null $date = null): ?Vehicle
    {
        $date = $date ? Carbon::parse($date)->toDateString() : now()->toDateString();

        $assignment = $this->vehicleAssignments()
            ->whereDate('assignment_date', $date)
            ->with('vehicle')
            ->first();

        return $assignment?->vehicle ?? $this->vehicle;
    }
}
