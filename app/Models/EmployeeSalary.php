<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeSalary extends Model
{
    use HasFactory;

    protected $fillable = ['employee_id', 'basic_salary', 'effective_from', 'effective_to'];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public static function currentForEmployee(int $employeeId, $asOf = null): ?self
    {
        $date = $asOf ? \Carbon\Carbon::parse($asOf) : now();
        return static::where('employee_id', $employeeId)
            ->where('effective_from', '<=', $date->toDateString())
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date->toDateString());
            })
            ->orderByDesc('effective_from')
            ->first();
    }
}
