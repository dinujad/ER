<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ManualJournal extends Model
{
    protected $table = 'accounting_manual_journals';

    protected $fillable = [
        'business_id', 'journal_number', 'journal_date', 'posting_date', 'status',
        'description', 'reference', 'transaction_number', 'location_id',
        'created_by', 'submitted_by', 'approved_by', 'posted_by',
        'submitted_at', 'approved_at', 'posted_at', 'rejected_at',
        'rejection_reason', 'reversed_by_txn',
    ];

    protected $casts = [
        'journal_date' => 'date',
        'posting_date' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(ManualJournalLine::class, 'manual_journal_id')->orderBy('line_no');
    }

    public function totalDebit(): string
    {
        return number_format((float) $this->lines->sum(fn ($l) => (float) $l->debit), 2, '.', '');
    }

    public function totalCredit(): string
    {
        return number_format((float) $this->lines->sum(fn ($l) => (float) $l->credit), 2, '.', '');
    }

    public function isBalanced(): bool
    {
        return $this->totalDebit() === $this->totalCredit() && (float) $this->totalDebit() > 0;
    }
}
