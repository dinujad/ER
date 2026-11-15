<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_overnight' => 'boolean',
        'ot_rate_per_hour' => 'decimal:2',
    ];
}
