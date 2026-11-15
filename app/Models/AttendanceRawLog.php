<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRawLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'payload_json' => 'array',
        'event_time' => 'datetime',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];
}
