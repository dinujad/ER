<?php

namespace App\Observers;

use App\Models\SalesPersonExpense;
use App\Services\AccountingService;
use Illuminate\Support\Facades\Log;

class SalesPersonExpenseObserver
{
    public function created(SalesPersonExpense $expense): void
    {
        $expenseId = (int) $expense->id;
        defer(fn () => $this->postById($expenseId));
    }

    public function updated(SalesPersonExpense $expense): void
    {
        if (! $expense->wasChanged(['expense_date', 'type', 'description', 'amount'])) {
            return;
        }

        $expenseId = (int) $expense->id;
        defer(function () use ($expenseId) {
            AccountingService::reverseEvent($this->eventKey($expenseId));
            $this->postById($expenseId);
        });
    }

    public function backfill(SalesPersonExpense $expense): void
    {
        $this->postById((int) $expense->id);
    }

    public function deleted(SalesPersonExpense $expense): void
    {
        $expenseId = (int) $expense->id;
        defer(fn () => AccountingService::reverseEvent($this->eventKey($expenseId)));
    }

    private function postById(int $expenseId): void
    {
        $expense = SalesPersonExpense::query()->find($expenseId);
        if (! $expense || (float) $expense->amount <= 0) {
            return;
        }

        $this->post($expense);
    }

    private function post(SalesPersonExpense $expense): void
    {
        try {
            $debitId = AccountingService::accountId($this->expenseGlCode($expense->type));
            $cashId = AccountingService::accountId(AccountingService::GL_CASH);

            if (! $debitId || ! $cashId) {
                Log::warning('SalesPersonExpenseObserver: chart of accounts not seeded yet');
                return;
            }

            $date = $expense->expense_date instanceof \DateTimeInterface
                ? $expense->expense_date->format('Y-m-d')
                : (string) $expense->expense_date;

            AccountingService::postOnce(
                $this->eventKey((int) $expense->id),
                SalesPersonExpense::class,
                (int) $expense->id,
                $debitId,
                $cashId,
                (float) $expense->amount,
                $date,
                'Sales expense – ' . $expense->typeLabel(),
                "SPE-{$expense->id}",
                'sales_person_expense'
            );
        } catch (\Throwable $e) {
            Log::error('SalesPersonExpenseObserver: accounting post failed', [
                'sales_person_expense_id' => $expense->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function expenseGlCode(string $type): string
    {
        return match ($type) {
            SalesPersonExpense::TYPE_FUEL,
            SalesPersonExpense::TYPE_TRAVEL => AccountingService::GL_TRANSPORT_EXPENSE,
            SalesPersonExpense::TYPE_OTHERS => AccountingService::GL_OTHER_EXPENSE,
            default => AccountingService::GL_GENERAL_EXPENSE,
        };
    }

    private function eventKey(int $expenseId): string
    {
        return "sales-person-expense:{$expenseId}:entry";
    }
}
