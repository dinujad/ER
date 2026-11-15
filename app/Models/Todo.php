<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Todo extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id', 'task_id', 'title', 'description',
        'status', 'priority', 'start_date', 'end_date',
        'estimated_hours', 'assigned_by', 'assigned_to', 'created_by'
    ];

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to'); // Assuming User model for now
    }
}
