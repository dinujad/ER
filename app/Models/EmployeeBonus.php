<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeBonus extends Model
{
    protected $fillable = ['employee_id', 'type', 'amount', 'bonus_date', 'notes'];

    protected $casts = [
        'amount' => 'decimal:2',
        'bonus_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
