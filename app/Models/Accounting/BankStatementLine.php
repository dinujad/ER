<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankStatementLine extends Model
{
    protected $table = 'bank_statement_lines';

    protected $fillable = [
        'bank_statement_import_id', 'txn_date', 'description', 'reference',
        'debit', 'credit', 'amount', 'fingerprint', 'match_status',
        'matched_source_type', 'matched_source_id',
    ];

    protected $casts = [
        'txn_date' => 'date',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(BankStatementImport::class, 'bank_statement_import_id');
    }
}
