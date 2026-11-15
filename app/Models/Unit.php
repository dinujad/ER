<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'short_name', 'base_unit_id', 'conversion_factor'];

    protected $casts = [
        'conversion_factor' => 'decimal:4',
    ];

    public function baseUnit()
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function derivedUnits()
    {
        return $this->hasMany(Unit::class, 'base_unit_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
