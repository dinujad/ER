<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceManualAdjustment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'original_in' => 'datetime',
        'new_in' => 'datetime',
        'original_out' => 'datetime',
        'new_out' => 'datetime',
    ];

    public function daily()
    {
        return $this->belongsTo(AttendanceDaily::class, 'attendance_daily_id');
    }

    public function adjuster()
    {
        return $this->belongsTo(User::class, 'adjusted_by_user_id');
    }
}
