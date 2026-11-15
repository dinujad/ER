<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobLineAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'manufacturing_job_id',
        'line_name',
        'assigned_quantity',
        'dispatch_date',
    ];

    protected $casts = [
        'dispatch_date' => 'date',
    ];

    public function job()
    {
        return $this->belongsTo(ManufacturingJob::class, 'manufacturing_job_id');
    }
}
