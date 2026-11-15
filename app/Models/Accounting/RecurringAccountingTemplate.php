<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringAccountingTemplate extends Model
{
    protected $table = 'accounting_recurring_templates';

    protected $fillable = [
        'business_id', 'name', 'frequency', 'starts_on', 'ends_on', 'next_run_on',
        'status', 'execution_mode', 'payload', 'created_by',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'next_run_on' => 'date',
        'payload' => 'array',
    ];

    public function runs(): HasMany
    {
        return $this->hasMany(RecurringAccountingRun::class, 'template_id');
    }
}
