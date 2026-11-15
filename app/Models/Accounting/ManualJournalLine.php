<?php

namespace App\Models\Accounting;

use Modules\Accounting\Entities\ChartOfAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualJournalLine extends Model
{
    protected $table = 'accounting_manual_journal_lines';

    protected $fillable = [
        'manual_journal_id', 'chart_of_account_id', 'debit', 'credit', 'memo', 'line_no',
    ];

    public function journal(): BelongsTo
    {
        return $this->belongsTo(ManualJournal::class, 'manual_journal_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }
}
