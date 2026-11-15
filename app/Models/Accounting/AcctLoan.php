<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcctLoan extends Model
{
    protected $table = 'acct_loans';

    protected $fillable = [
        'business_id', 'loan_number', 'lender_name', 'loan_type',
        'principal', 'outstanding_principal', 'annual_interest_rate',
        'interest_type', 'payment_frequency', 'term_months',
        'disbursement_date', 'maturity_date',
        'bank_account_id', 'loan_liability_account_id',
        'interest_expense_account_id', 'interest_payable_account_id',
        'accrued_interest', 'disbursement_txn', 'status',
    ];

    protected $casts = [
        'principal' => 'decimal:4',
        'outstanding_principal' => 'decimal:4',
        'annual_interest_rate' => 'decimal:4',
        'accrued_interest' => 'decimal:4',
        'disbursement_date' => 'date',
        'maturity_date' => 'date',
    ];

    public function scheduleLines(): HasMany
    {
        return $this->hasMany(AcctLoanScheduleLine::class, 'loan_id')->orderBy('line_no');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(AcctLoanPayment::class, 'loan_id');
    }
}