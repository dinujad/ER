<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankStatementLine extends Model
{
    protected $table = 'bank_statement_lines';

    protected $fillable = [
        'bank_statement_import_id', 'txn_date', 'description', 'reference',
        'debit', 'credit', 'amount', 'fingerprint', 'match_status', 'excluded_reason',
        'matched_source_type', 'matched_source_id', 'matched_at', 'matched_by',
        'fee_amount', 'fee_transaction_number',
    ];

    protected $casts = [
        'txn_date' => 'date',
        'matched_at' => 'datetime',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(BankStatementImport::class, 'bank_statement_import_id');
    }
}
