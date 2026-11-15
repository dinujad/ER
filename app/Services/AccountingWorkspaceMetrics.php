<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Read-only dashboard metrics from posted journal_entries (LKR).
 * No new accounting formulas — net balances from active, non-reversed lines.
 */
class AccountingWorkspaceMetrics
{
    /**
     * @return array{
     *   available: bool,
     *   currency: string,
     *   start_date: string|null,
     *   end_date: string|null,
     *   revenue: float|null,
     *   expenses: float|null,
     *   net_profit: float|null,
     *   cash: float|null,
     *   bank: float|null,
     *   receivables: float|null,
     *   payables: float|null,
     *   recent: list<array<string, mixed>>,
     * }
     */
    public function summary(?string $startDate = null, ?string $endDate = null): array
    {
        $empty = [
            'available' => false,
            'currency' => 'LKR',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'revenue' => null,
            'expenses' => null,
            'net_profit' => null,
            'cash' => null,
            'bank' => null,
            'receivables' => null,
            'payables' => null,
            'recent' => [],
        ];

        if (! Schema::hasTable('journal_entries') || ! Schema::hasTable('chart_of_accounts')) {
            return $empty;
        }

        $cashId = AccountingService::accountId(AccountingService::GL_CASH);
        $bankId = AccountingService::accountId(AccountingService::GL_BANK);
        $arId = AccountingService::accountId(AccountingService::GL_ACCOUNTS_RECV);
        $apId = AccountingService::accountId(AccountingService::GL_ACCOUNTS_PAY);
        $salesId = AccountingService::accountId(AccountingService::GL_SALES_REVENUE);
        $returnsId = AccountingService::accountId(AccountingService::GL_SALES_RETURNS);

        if (! $cashId && ! $bankId && ! $arId && ! $apId && ! $salesId) {
            return $empty;
        }

        $cash = $cashId ? $this->assetBalance($cashId) : null;
        $bank = $bankId ? $this->assetBalance($bankId) : null;
        $receivables = $arId ? $this->assetBalance($arId) : null;
        $payables = $apId ? $this->liabilityBalance($apId) : null;

        $revenue = $this->periodIncomeNet([$salesId, $returnsId], $startDate, $endDate);
        $expenses = $this->periodExpenseNet($startDate, $endDate);
        $net = ($revenue !== null && $expenses !== null) ? round($revenue - $expenses, 2) : null;

        return [
            'available' => true,
            'currency' => 'LKR',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'revenue' => $revenue,
            'expenses' => $expenses,
            'net_profit' => $net,
            'cash' => $cash,
            'bank' => $bank,
            'receivables' => $receivables,
            'payables' => $payables,
            'recent' => $this->recentJournals(8),
        ];
    }

    private function assetBalance(int $accountId): float
    {
        $row = $this->baseJournalQuery()
            ->where('chart_of_account_id', $accountId)
            ->selectRaw('COALESCE(SUM(debit),0) - COALESCE(SUM(credit),0) as net')
            ->first();

        return round((float) ($row->net ?? 0), 2);
    }

    private function liabilityBalance(int $accountId): float
    {
        $row = $this->baseJournalQuery()
            ->where('chart_of_account_id', $accountId)
            ->selectRaw('COALESCE(SUM(credit),0) - COALESCE(SUM(debit),0) as net')
            ->first();

        return round((float) ($row->net ?? 0), 2);
    }

    /** @param list<int|null> $accountIds */
    private function periodIncomeNet(array $accountIds, ?string $start, ?string $end): ?float
    {
        $ids = array_values(array_filter($accountIds));
        if ($ids === []) {
            return null;
        }

        $q = $this->baseJournalQuery()->whereIn('chart_of_account_id', $ids);
        $this->applyDateRange($q, $start, $end);
        $row = $q->selectRaw('COALESCE(SUM(credit),0) - COALESCE(SUM(debit),0) as net')->first();

        return round((float) ($row->net ?? 0), 2);
    }

    private function periodExpenseNet(?string $start, ?string $end): ?float
    {
        if (! Schema::hasColumn('chart_of_accounts', 'account_type')) {
            $expenseIds = array_filter([
                AccountingService::accountId(AccountingService::GL_COST_OF_GOODS),
                AccountingService::accountId(AccountingService::GL_GENERAL_EXPENSE),
                AccountingService::accountId(AccountingService::GL_SALARY_EXPENSE),
                AccountingService::accountId(AccountingService::GL_UTILITIES_EXPENSE),
                AccountingService::accountId(AccountingService::GL_TRANSPORT_EXPENSE),
                AccountingService::accountId(AccountingService::GL_OTHER_EXPENSE),
            ]);
            if ($expenseIds === []) {
                return null;
            }
            $q = $this->baseJournalQuery()->whereIn('chart_of_account_id', $expenseIds);
            $this->applyDateRange($q, $start, $end);
            $row = $q->selectRaw('COALESCE(SUM(debit),0) - COALESCE(SUM(credit),0) as net')->first();

            return round((float) ($row->net ?? 0), 2);
        }

        $q = $this->baseJournalQuery()
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entries.chart_of_account_id')
            ->where('chart_of_accounts.account_type', 'expense')
            ->where('chart_of_accounts.active', 1);
        $this->applyDateRange($q, $start, $end);
        $row = $q->selectRaw('COALESCE(SUM(journal_entries.debit),0) - COALESCE(SUM(journal_entries.credit),0) as net')->first();

        return round((float) ($row->net ?? 0), 2);
    }

    /** @return list<array<string, mixed>> */
    private function recentJournals(int $limit): array
    {
        $select = [
            'journal_entries.transaction_number',
            'journal_entries.transaction_type',
            'journal_entries.name',
            'journal_entries.date',
            'journal_entries.debit',
            'journal_entries.credit',
        ];

        $query = $this->baseJournalQuery()
            ->leftJoin('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entries.chart_of_account_id')
            ->orderByDesc('journal_entries.date')
            ->orderByDesc('journal_entries.id')
            ->limit($limit);

        if (Schema::hasColumn('chart_of_accounts', 'name')) {
            $select[] = 'chart_of_accounts.name as account_name';
        } elseif (Schema::hasColumn('chart_of_accounts', 'gl_code')) {
            $select[] = 'chart_of_accounts.gl_code as account_name';
        }

        $rows = $query->get($select);

        return $rows->map(fn ($r) => [
            'transaction_number' => $r->transaction_number,
            'transaction_type' => $r->transaction_type,
            'name' => $r->name,
            'date' => $r->date,
            'debit' => (float) $r->debit,
            'credit' => (float) $r->credit,
            'account_name' => $r->account_name ?? null,
        ])->all();
    }

    private function baseJournalQuery()
    {
        return DB::table('journal_entries')
            ->where(function ($q) {
                $q->where('journal_entries.reversed', 0)->orWhereNull('journal_entries.reversed');
            })
            ->where(function ($q) {
                $q->where('journal_entries.active', 1)->orWhereNull('journal_entries.active');
            });
    }

    private function applyDateRange($query, ?string $start, ?string $end): void
    {
        if ($start && $end) {
            $query->whereBetween('journal_entries.date', [$start, $end]);
        } elseif ($start) {
            $query->where('journal_entries.date', '>=', $start);
        } elseif ($end) {
            $query->where('journal_entries.date', '<=', $end);
        }
    }
}
