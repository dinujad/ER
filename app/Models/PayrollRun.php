<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PayrollRun extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'period_start', 'period_end', 'status', 'created_by', 'finalized_at'];

    const STATUS_DRAFT = 'draft';
    const STATUS_REVIEWED = 'reviewed';
    const STATUS_APPROVED = 'approved';
    const STATUS_FINALIZED = 'finalized';

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'finalized_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(PayrollRunItem::class);
    }

    public function corrections()
    {
        return $this->hasMany(PayrollCorrection::class)->latest();
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REVIEWED], true);
    }
}
