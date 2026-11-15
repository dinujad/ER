<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncentiveClaim extends Model
{
    protected $guarded = [];

    protected $casts = [
        'work_date' => 'date',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }
}
