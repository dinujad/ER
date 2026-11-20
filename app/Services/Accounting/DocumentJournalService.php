<?php

namespace App\Services\Accounting;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6.2 — journal drill-down: the double-entry lines behind any document, a single transaction,
 * or an account for a period (Xero "Account Transactions").
 */
class DocumentJournalService
{
    /**
     * Journal transactions posted by the given source documents.
     *
     * @param  array<string, array<int, int>>  $sources  source_type (class or short string) => [ids]
     * @return Collection<int, array{transaction_number: string, date: ?string, name: ?string, type: ?string, reference: ?string, reversed: bool, source: array, lines: array<int, array>, total: float}>
     */
    public static function forSources(array $sources, bool $includeReversed = true): Collection
    {
        if (!\App\Support\SchemaCache::hasTable('journal_entries')) {
            return collect();
        }

        $sources = array_filter($sources, fn ($ids) => !empty($ids));
        if ($sources === []) {
            return collect();
        }

        $query = self::baseQuery()->where(function ($w) use ($sources) {
            foreach ($sources as $type => $ids) {
                $w->orWhere(function ($q) use ($type, $ids) {
                    $q->where('je.source_type', $type)->whereIn('je.source_id', array_values(array_unique(array_map('intval', $ids))));
                });
            }
        });

        if (!$includeReversed) {
            $query->where('je.reversed', 0);
        }

        return self::group($query->orderBy('je.date')->orderBy('je.id')->get());
    }

    /** One transaction (all its lines). */
    public static function transaction(string $transactionNumber): ?array
    {
        if (!\App\Support\SchemaCache::hasTable('journal_entries')) {
            return null;
        }

        return self::group(
            self::baseQuery()->where('je.transaction_number', $transactionNumber)->orderBy('je.id')->get()
        )->first();
    }

    /**
     * Account activity for a period with running balance and source links.
     *
     * @return array{account: ?object, opening: float, closing: float, debit: float, credit: float, rows: Collection}
     */
    public static function accountActivity(int $accountId, string $from, string $to): array
    {
        $account = DB::table('chart_of_accounts')->where('id', $accountId)->first();
        if (!$account) {
            return ['account' => null, 'opening' => 0.0, 'closing' => 0.0, 'debit' => 0.0, 'credit' => 0.0, 'rows' => collect()];
        }

        $creditNormal = in_array(strtolower((string) ($account->account_type ?? '')), ['liability', 'equity', 'income', 'revenue'], true);
        $sign = $creditNormal ? -1 : 1;

        $opening = (float) DB::table('journal_entries')
            ->where('chart_of_account_id', $accountId)
            ->where('reversed', 0)
            ->where('date', '<', $from)
            ->selectRaw('COALESCE(SUM(debit),0) - COALESCE(SUM(credit),0) as b')
            ->value('b');
        $opening = round($opening * $sign, 2);

        $lines = DB::table('journal_entries')
            ->where('chart_of_account_id', $accountId)
            ->where('reversed', 0)
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')->orderBy('id')
            ->get();

        $running = $opening;
        $debit = 0.0;
        $credit = 0.0;
        $rows = $lines->map(function ($l) use (&$running, &$debit, &$credit, $sign) {
            $d = round((float) $l->debit, 2);
            $c = round((float) $l->credit, 2);
            $debit = round($debit + $d, 2);
            $credit = round($credit + $c, 2);
            $running = round($running + ($d - $c) * $sign, 2);

            return (object) [
                'date' => $l->date,
                'transaction_number' => $l->transaction_number,
                'description' => $l->name,
                'reference' => $l->reference,
                'type' => $l->transaction_type,
                'debit' => $d,
                'credit' => $c,
                'balance' => $running,
                'source' => SourceDocumentResolver::resolve($l->source_type, $l->source_id),
            ];
        });

        return [
            'account' => $account,
            'opening' => $opening,
            'closing' => $running,
            'debit' => $debit,
            'credit' => $credit,
            'rows' => $rows,
        ];
    }

    private static function baseQuery()
    {
        return DB::table('journal_entries as je')
            ->leftJoin('chart_of_accounts as coa', 'coa.id', '=', 'je.chart_of_account_id')
            ->select([
                'je.id', 'je.transaction_number', 'je.transaction_type', 'je.name', 'je.date', 'je.reference',
                'je.source_type', 'je.source_id', 'je.debit', 'je.credit', 'je.reversed', 'je.chart_of_account_id',
                'coa.gl_code', 'coa.name as account_name',
            ]);
    }

    private static function group(Collection $rows): Collection
    {
        return $rows->groupBy('transaction_number')->map(function (Collection $lines, string $txn) {
            $first = $lines->first();
            $sourceLine = $lines->first(fn ($line) => $line->source_type && $line->source_id) ?? $first;

            return [
                'transaction_number' => $txn,
                'date' => $first->date,
                'name' => $first->name,
                'type' => $first->transaction_type,
                'reference' => $first->reference,
                'reversed' => (bool) $lines->every(fn ($l) => (bool) $l->reversed),
                'source' => SourceDocumentResolver::forJournal($sourceLine),
                'lines' => $lines->map(fn ($l) => [
                    'account_id' => (int) $l->chart_of_account_id,
                    'gl_code' => $l->gl_code,
                    'account' => $l->account_name,
                    'debit' => round((float) $l->debit, 2),
                    'credit' => round((float) $l->credit, 2),
                ])->values()->all(),
                'total' => round((float) $lines->sum('debit'), 2),
            ];
        })->values();
    }
}
