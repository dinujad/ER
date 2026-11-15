<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HourlyProduction extends Model
{
    use HasFactory;

    protected $fillable = [
        'manufacturing_job_id',
        'employee_id',   // nullable — null means a single "total" entry for that hour
        'line_name',
        'production_date',
        'hour',
        'quantity',
    ];

    public function job()
    {
        return $this->belongsTo(ManufacturingJob::class, 'manufacturing_job_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /** Line total rows only (excludes per-employee breakdown). */
    public function scopeLineTotals($query)
    {
        return $query->whereNull('employee_id');
    }
}
