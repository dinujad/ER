<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesRoute extends Model
{
    protected $fillable = [
        'name',
        'code',
        'from_location',
        'to_location',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function salesPersons(): HasMany
    {
        return $this->hasMany(User::class, 'sales_route_id')
            ->where('role', 'sales_person');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(SalesRouteRun::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function pathLabel(): string
    {
        return $this->name;
    }
}
