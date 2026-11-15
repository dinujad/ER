<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeChange extends Model
{
    protected $guarded = [];

    protected $casts = [
        'effective_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
