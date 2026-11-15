<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class AccountingPeriod extends Model
{
    protected $table = 'accounting_periods';

    protected $fillable = [
        'business_id', 'name', 'starts_on', 'ends_on', 'status',
        'closed_at', 'closed_by',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'closed_at' => 'datetime',
    ];

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }
}
