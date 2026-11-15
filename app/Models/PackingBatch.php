<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackingBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'manufacturing_job_id',
        'washing_transfer_id',
        'type', // 'good', 'damage'
        'quantity',
        'status', // 'packing', 'sent_to_final_check'
    ];

    public function job()
    {
        return $this->belongsTo(ManufacturingJob::class, 'manufacturing_job_id');
    }

    public function materialIssues()
    {
        return $this->hasMany(MaterialIssue::class);
    }

    public function variationLines()
    {
        return $this->hasMany(PackingBatchVariation::class);
    }
}
