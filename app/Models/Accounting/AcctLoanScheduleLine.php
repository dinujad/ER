<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcctLoanScheduleLine extends Model
{
    protected $table = 'acct_loan_schedule_lines';

    protected $fillable = [
        'loan_id', 'line_no', 'due_date',
        'opening_principal', 'interest_amount', 'principal_amount',
        'total_payment', 'closing_principal', 'status',
    ];

    protected $casts = [
        'due_date' => 'date',
        'opening_principal' => 'decimal:4',
        'interest_amount' => 'decimal:4',
        'principal_amount' => 'decimal:4',
        'total_payment' => 'decimal:4',
        'closing_principal' => 'decimal:4',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(AcctLoan::class, 'loan_id');
    }
}