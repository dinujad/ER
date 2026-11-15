<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtRequest extends Model
{
    protected $guarded = [];

    protected $casts = [
        'work_date' => 'date',
        'planned_hours' => 'decimal:2',
        'approved_hours' => 'decimal:2',
        'rate_per_hour' => 'decimal:2',
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
