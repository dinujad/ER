<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeShift extends Model
{
    protected $table = 'employee_shifts';

    protected $fillable = ['employee_id', 'shift_id', 'date', 'is_off_day'];

    protected $casts = [
        'date' => 'date',
        'is_off_day' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
