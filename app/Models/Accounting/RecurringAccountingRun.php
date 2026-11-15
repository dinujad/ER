<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringAccountingRun extends Model
{
    protected $table = 'accounting_recurring_runs';

    protected $fillable = [
        'template_id', 'occurrence_date', 'status', 'manual_journal_id', 'message',
        'generated_type', 'generated_id', 'execution_key',
    ];

    protected $casts = [
        'occurrence_date' => 'date',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(RecurringAccountingTemplate::class, 'template_id');
    }
}
