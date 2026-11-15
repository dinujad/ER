<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AcctLoan;
use App\Models\Accounting\AcctLoanPayment;
use App\Models\Accounting\AcctLoanScheduleLine;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LoanAccountingService
{
    public static function nextLoanNumber(int $businessId = 0): string
    {
        $prefix = 'LN-'.date('Y').'-';
        $last = AcctLoan::query()->where('loan_number', 'like', $prefix.'%')->orderByDesc('id')->value('loan_number');
        $seq = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return $prefix.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function createLoan(array $data, ?int $userId = null): AcctLoan
    {
        $principal = AccountingService::normalizeMoney($data['principal'] ?? 0);

        return AcctLoan::create([
            'business_id' => (int) ($data['business_id'] ?? 0),
            'loan_number' => $data['loan_number'] ?? self::nextLoanNumber((int) ($data['business_id'] ?? 0)),
            'lender_name' => $data['lender_name'],
            'loan_type' => $data['loan_type'] ?? 'term',
            'principal' => $principal,
            'outstanding_principal' => $principal,
            'annual_interest_rate' => round((float) ($data['annual_interest_rate'] ?? 0), 4),
            'interest_type' => $data['interest_type'] ?? 'reducing',
            'payment_frequency' => $data['payment_frequency'] ?? 'monthly',
            'term_months' => (int) ($data['term_months'] ?? 12),
            'disbursement_date' => $data['disbursement_date'] ?? null,
            'maturity_date' => $data['maturity_date'] ?? null,
            'bank_account_id' => $data['bank_account_id'] ?? AccountingService::accountId(AccountingService::GL_BANK),
            'loan_liability_account_id' => $data['loan_liability_account_id'] ?? AccountingService::accountId(AccountingService::GL_LOANS_PAYABLE),
            'interest_expense_account_id' => $data['interest_expense_account_id'] ?? AccountingService::accountId(AccountingService::GL_INTEREST_EXPENSE),
            'interest_payable_account_id' => $data['interest_payable_account_id'] ?? AccountingService::accountId(AccountingService::GL_INTEREST_PAYABLE),
            'status' => 'draft',
        ]);
    }

    public static function buildEqualInstallmentSchedule(AcctLoan $loan, ?string $firstDueDate = null): AcctLoan
    {
        $term = max(1, (int) $loan->term_months);
        $annualRate = (float) $loan->annual_interest_rate / 100.0;
        $monthlyRate = $annualRate / 12.0;
        $opening = (float) $loan->principal;
        $payment = $monthlyRate > 0
            ? round($opening * ($monthlyRate * pow(1 + $monthlyRate, $term)) / (pow(1 + $monthlyRate, $term) - 1), 2)
            : round($opening / $term, 2);

        $start = $firstDueDate ? strtotime($firstDueDate) : strtotime((string) ($loan->disbursement_date ?: now()->toDateString()).' +1 month');
        $principalLeft = $opening;

        $loan->scheduleLines()->delete();

        for ($line = 1; $line <= $term; $line++) {
            $dueDate = date('Y-m-d', strtotime('+'.($line - 1).' months', $start));
            $interest = round($principalLeft * $monthlyRate, 2);
            $principalPart = round($payment - $interest, 2);
            if ($line === $term) {
                $principalPart = round($principalLeft, 2);
                $payment = round($principalPart + $interest, 2);
            }
            $closing = round(max(0, $principalLeft - $principalPart), 2);

            AcctLoanScheduleLine::create([
                'loan_id' => $loan->id,
                'line_no' => $line,
                'due_date' => $dueDate,
                'opening_principal' => round($principalLeft, 2),
                'interest_amount' => $interest,
                'principal_amount' => $principalPart,
                'total_payment' => $payment,
                'closing_principal' => $closing,
                'status' => 'scheduled',
            ]);

            $principalLeft = $closing;
        }

        return $loan->fresh('scheduleLines');
    }

    public static function disburse(AcctLoan $loan, string $disbursementDate, ?int $userId = null): AcctLoan
    {
        if ($loan->status !== 'draft') {
            throw new RuntimeException('Loan has already been disbursed.');
        }

        $amount = AccountingService::normalizeMoney((float) $loan->principal);
        $bankId = (int) $loan->bank_account_id;
        $loanId = (int) $loan->loan_liability_account_id;

        if (! $bankId || ! $loanId) {
            throw new RuntimeException('Loan GL accounts are not configured.');
        }

        return DB::transaction(function () use ($loan, $disbursementDate, $amount, $bankId, $loanId, $userId) {
            $txn = AccountingService::postOnce(
                'loan:disburse:'.$loan->id,
                'acct_loan',
                (int) $loan->id,
                $bankId,
                $loanId,
                $amount,
                $disbursementDate,
                'Loan disbursement '.$loan->loan_number,
                $loan->loan_number,
                'loan_disbursement'
            );

            $loan->update([
                'status' => 'active',
                'disbursement_date' => $disbursementDate,
                'disbursement_txn' => $txn,
            ]);

            if ($loan->scheduleLines()->count() === 0) {
                self::buildEqualInstallmentSchedule($loan);
            }

            AccountingAuditService::record('loan.disbursed', $loan, null, $loan->fresh()->toArray(), null, (int) $loan->business_id, $userId);

            return $loan->fresh('scheduleLines');
        });
    }

    public static function recordPayment(
        AcctLoan $loan,
        string $paymentDate,
        float $principalPaid,
        float $interestPaid,
        ?int $scheduleLineId = null,
        ?int $userId = null
    ): AcctLoanPayment {
        $principalPaid = round($principalPaid, 2);
        $interestPaid = round($interestPaid, 2);
        $total = round($principalPaid + $interestPaid, 2);

        if ($total <= 0) {
            throw new RuntimeException('Payment total must be greater than zero.');
        }

        $bankId = (int) $loan->bank_account_id;
        $loanLiabilityId = (int) $loan->loan_liability_account_id;
        $interestExpId = (int) $loan->interest_expense_account_id;

        if (! $bankId || ! $loanLiabilityId) {
            throw new RuntimeException('Loan payment GL accounts are not configured.');
        }

        $eventKey = 'loan:payment:'.$loan->id.':'.$paymentDate.':'.number_format($total, 2, '.', '');

        if (AcctLoanPayment::where('event_key', $eventKey)->exists()) {
            return AcctLoanPayment::where('event_key', $eventKey)->first();
        }

        return DB::transaction(function () use (
            $loan, $paymentDate, $principalPaid, $interestPaid, $total,
            $bankId, $loanLiabilityId, $interestExpId, $eventKey, $scheduleLineId, $userId
        ) {
            $lines = [];
            if ($principalPaid > 0) {
                $lines[] = ['account_id' => $loanLiabilityId, 'debit' => $principalPaid, 'credit' => 0];
            }
            if ($interestPaid > 0 && $interestExpId) {
                $lines[] = ['account_id' => $interestExpId, 'debit' => $interestPaid, 'credit' => 0];
            }
            $lines[] = ['account_id' => $bankId, 'debit' => 0, 'credit' => $total];

            $txn = AccountingService::postMultiLine(
                $lines,
                $paymentDate,
                'Loan payment '.$loan->loan_number,
                $loan->loan_number,
                'loan_payment',
                ['source_type' => 'acct_loan', 'source_id' => $loan->id],
                $eventKey
            );

            $payment = AcctLoanPayment::create([
                'loan_id' => $loan->id,
                'schedule_line_id' => $scheduleLineId,
                'payment_date' => $paymentDate,
                'principal_paid' => $principalPaid,
                'interest_paid' => $interestPaid,
                'total_paid' => $total,
                'transaction_number' => $txn,
                'event_key' => $eventKey,
                'created_by' => $userId,
            ]);

            $newOutstanding = round(max(0, (float) $loan->outstanding_principal - $principalPaid), 2);
            $loan->update([
                'outstanding_principal' => $newOutstanding,
                'status' => $newOutstanding <= 0 ? 'closed' : $loan->status,
            ]);

            if ($scheduleLineId) {
                AcctLoanScheduleLine::where('id', $scheduleLineId)->update(['status' => 'paid']);
            }

            AccountingAuditService::record('loan.payment', $payment, null, $payment->toArray(), null, (int) $loan->business_id, $userId);

            return $payment;
        });
    }

    public static function buildEqualPrincipalSchedule(AcctLoan $loan, ?string $firstDueDate = null): AcctLoan
    {
        $term = max(1, (int) $loan->term_months);
        $annualRate = (float) $loan->annual_interest_rate / 100.0;
        $monthlyRate = $annualRate / 12.0;
        $opening = (float) $loan->principal;
        $principalPartBase = round($opening / $term, 2);
        $start = $firstDueDate ? strtotime($firstDueDate) : strtotime((string) ($loan->disbursement_date ?: now()->toDateString()).' +1 month');
        $principalLeft = $opening;

        $loan->scheduleLines()->delete();

        for ($line = 1; $line <= $term; $line++) {
            $dueDate = date('Y-m-d', strtotime('+'.($line - 1).' months', $start));
            $interest = round($principalLeft * $monthlyRate, 2);
            $principalPart = $line === $term ? round($principalLeft, 2) : $principalPartBase;
            $total = round($principalPart + $interest, 2);
            $closing = round(max(0, $principalLeft - $principalPart), 2);

            AcctLoanScheduleLine::create([
                'loan_id' => $loan->id,
                'line_no' => $line,
                'due_date' => $dueDate,
                'opening_principal' => round($principalLeft, 2),
                'interest_amount' => $interest,
                'principal_amount' => $principalPart,
                'total_payment' => $total,
                'closing_principal' => $closing,
                'status' => 'scheduled',
            ]);

            $principalLeft = $closing;
        }

        return $loan->fresh('scheduleLines');
    }

    public static function buildInterestOnlySchedule(AcctLoan $loan, ?string $firstDueDate = null): AcctLoan
    {
        $term = max(1, (int) $loan->term_months);
        $monthlyRate = ((float) $loan->annual_interest_rate / 100.0) / 12.0;
        $opening = (float) $loan->principal;
        $interest = round($opening * $monthlyRate, 2);
        $start = $firstDueDate ? strtotime($firstDueDate) : strtotime((string) ($loan->disbursement_date ?: now()->toDateString()).' +1 month');

        $loan->scheduleLines()->delete();

        for ($line = 1; $line <= $term; $line++) {
            $dueDate = date('Y-m-d', strtotime('+'.($line - 1).' months', $start));
            $principalPart = $line === $term ? round($opening, 2) : 0.0;
            $total = round($principalPart + $interest, 2);
            $closing = $line === $term ? 0.0 : $opening;

            AcctLoanScheduleLine::create([
                'loan_id' => $loan->id,
                'line_no' => $line,
                'due_date' => $dueDate,
                'opening_principal' => $opening,
                'interest_amount' => $interest,
                'principal_amount' => $principalPart,
                'total_payment' => $total,
                'closing_principal' => $closing,
                'status' => 'scheduled',
            ]);
        }

        return $loan->fresh('scheduleLines');
    }

    public static function accrueInterest(AcctLoan $loan, float $amount, string $accrualDate, ?int $userId = null): string
    {
        if ($loan->status !== 'active') {
            throw new RuntimeException('Only active loans can accrue interest.');
        }

        $amount = AccountingService::normalizeMoney($amount);
        $expenseId = (int) $loan->interest_expense_account_id;
        $payableId = (int) $loan->interest_payable_account_id;
        if (! $expenseId || ! $payableId) {
            throw new RuntimeException('Interest accrual GL accounts are not configured.');
        }

        $eventKey = 'loan:accrue:'.$loan->id.':'.$accrualDate.':'.number_format($amount, 2, '.', '');

        $txn = AccountingService::postOnce(
            $eventKey,
            'acct_loan',
            (int) $loan->id,
            $expenseId,
            $payableId,
            $amount,
            $accrualDate,
            'Interest accrual '.$loan->loan_number,
            $loan->loan_number,
            'loan_interest_accrual'
        );

        AccountingAuditService::record('loan.interest_accrued', $loan, null, [
            'amount' => $amount,
            'transaction_number' => $txn,
        ], null, (int) $loan->business_id, $userId);

        return $txn;
    }

    public static function earlySettle(AcctLoan $loan, string $settlementDate, float $interestDue = 0, ?int $userId = null): AcctLoanPayment
    {
        if ($loan->status !== 'active') {
            throw new RuntimeException('Only active loans can be settled early.');
        }

        $principal = round((float) $loan->outstanding_principal, 2);
        if ($principal <= 0 && $interestDue <= 0) {
            throw new RuntimeException('Nothing outstanding to settle.');
        }

        return self::recordPayment($loan, $settlementDate, $principal, round(max(0, $interestDue), 2), null, $userId);
    }
}