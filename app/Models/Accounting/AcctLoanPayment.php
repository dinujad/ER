<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcctLoanPayment extends Model
{
    protected $table = 'acct_loan_payments';

    protected $fillable = [
        'loan_id', 'schedule_line_id', 'payment_date',
        'principal_paid', 'interest_paid', 'total_paid',
        'transaction_number', 'event_key', 'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'principal_paid' => 'decimal:4',
        'interest_paid' => 'decimal:4',
        'total_paid' => 'decimal:4',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(AcctLoan::class, 'loan_id');
    }
}