<?php

namespace App\Observers;

use App\Models\Expense;
use App\Services\AccountingService;
use Illuminate\Support\Facades\Log;

class ExpenseObserver
{
    public function created(Expense $expense): void
    {
        $expenseId = (int) $expense->id;
        defer(fn () => $this->postById($expenseId));
    }

    public function updated(Expense $expense): void
    {
        if (! $expense->wasChanged([
            'expense_category_id',
            'amount',
            'payment_method',
            'expense_date',
            'reference',
        ])) {
            return;
        }

        $expenseId = (int) $expense->id;
        defer(function () use ($expenseId) {
            AccountingService::reverseEvent($this->eventKey($expenseId));
            $this->postById($expenseId);
        });
    }

    public function deleted(Expense $expense): void
    {
        $expenseId = (int) $expense->id;
        defer(fn () => AccountingService::reverseEvent($this->eventKey($expenseId)));
    }

    public function backfill(Expense $expense): void
    {
        $this->postById((int) $expense->id);
    }

    private function postById(int $expenseId): void
    {
        $expense = Expense::query()->with('category')->find($expenseId);
        if (! $expense || (float) $expense->amount <= 0) {
            return;
        }

        $this->postExpenseEntry($expense);
    }

    private function postExpenseEntry(Expense $expense): void
    {
        try {
            $txn = $this->buildExpenseEntry($expense);
            if ($txn) {
                $expense->updateQuietly(['accounting_txn_number' => $txn]);
            }
        } catch (\Throwable $e) {
            Log::error('ExpenseObserver: failed to post journal entry', [
                'expense_id' => $expense->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function buildExpenseEntry(Expense $expense): ?string
    {
        $debitId = AccountingService::expenseAccountId(
            $expense->category?->code,
            $expense->category?->name
        );
        $creditId = AccountingService::settlementAccountId($expense->payment_method, 'out');

        if (! $debitId || ! $creditId) {
            Log::warning('ExpenseObserver: chart of accounts not seeded yet');
            return null;
        }

        $categoryName = $expense->category?->name ?? 'Expense';
        $name = "Expense – {$categoryName}";
        $date = $expense->expense_date instanceof \DateTimeInterface
            ? $expense->expense_date->format('Y-m-d')
            : (string) $expense->expense_date;
        $ref = $expense->reference ?? "EXP-{$expense->id}";

        return AccountingService::postOnce(
            $this->eventKey((int) $expense->id),
            Expense::class,
            (int) $expense->id,
            $debitId,
            $creditId,
            (float) $expense->amount,
            $date,
            $name,
            $ref,
            'expense'
        );
    }

    private function eventKey(int $expenseId): string
    {
        return "expense:{$expenseId}:entry";
    }
}
