<?php

namespace App\Services\Accounting;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountRegisterService
{
    /**
     * Build account register with opening balance + running balances for a date window.
     * Opening = prior posted activity (respecting normal balance).
     *
     * @return array{opening: string, closing: string, rows: Collection, normal: string}
     */
    public static function register(
        int $accountId,
        ?string $from = null,
        ?string $to = null,
        ?int $locationId = null,
        bool $postedOnly = true
    ): array {
        $normal = self::normalBalance($accountId);
        $from = $from ?: '1970-01-01';
        $to = $to ?: date('Y-m-d');

        $opening = self::balanceBefore($accountId, $from, $locationId, $postedOnly, $normal);

        $q = DB::table('journal_entries')
            ->where('chart_of_account_id', $accountId)
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->where('active', 1);

        if ($postedOnly) {
            $q->where('reversed', 0);
        }
        if ($locationId && Schema::hasColumn('journal_entries', 'location_id')) {
            $q->where('location_id', $locationId);
        }

        $rows = $q->orderBy('date')
            ->orderBy('id')
            ->get(['id', 'date', 'transaction_number', 'transaction_type', 'name', 'reference',
                'debit', 'credit', 'reversed', 'source_type', 'source_id']);

        $running = (float) $opening;
        $enriched = $rows->map(function ($row) use (&$running, $normal) {
            $debit = round((float) $row->debit, 2);
            $credit = round((float) $row->credit, 2);
            if ($normal === 'credit') {
                $running = round($running + $credit - $debit, 2);
            } else {
                $running = round($running + $debit - $credit, 2);
            }

            return (object) [
                'id' => $row->id,
                'date' => $row->date,
                'transaction_number' => $row->transaction_number,
                'transaction_type' => $row->transaction_type,
                'description' => $row->name,
                'reference' => $row->reference,
                'debit' => number_format($debit, 2, '.', ''),
                'credit' => number_format($credit, 2, '.', ''),
                'running_balance' => number_format($running, 2, '.', ''),
                'reversed' => (bool) $row->reversed,
                'source_type' => $row->source_type,
                'source_id' => $row->source_id,
                'status' => $row->reversed ? 'reversed' : 'posted',
            ];
        });

        return [
            'opening' => number_format((float) $opening, 2, '.', ''),
            'closing' => number_format($running, 2, '.', ''),
            'rows' => $enriched,
            'normal' => $normal,
        ];
    }

    public static function balanceBefore(
        int $accountId,
        string $beforeDate,
        ?int $locationId,
        bool $postedOnly,
        ?string $normal = null
    ): float {
        $normal = $normal ?: self::normalBalance($accountId);

        $q = DB::table('journal_entries')
            ->where('chart_of_account_id', $accountId)
            ->whereDate('date', '<', $beforeDate)
            ->where('active', 1);

        if ($postedOnly) {
            $q->where('reversed', 0);
        }
        if ($locationId && Schema::hasColumn('journal_entries', 'location_id')) {
            $q->where('location_id', $locationId);
        }

        $totals = $q->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')->first();
        $d = round((float) ($totals->d ?? 0), 2);
        $c = round((float) ($totals->c ?? 0), 2);

        // Opening balance on the account record (if any) is included when beforeDate is the earliest filter
        // only via journal postings representing opening — COA opening_balance is added separately at full history start.
        $coaOpening = 0.0;
        if ($beforeDate <= '1970-01-02' && Schema::hasColumn('chart_of_accounts', 'opening_balance')) {
            $coaOpening = round((float) DB::table('chart_of_accounts')->where('id', $accountId)->value('opening_balance'), 2);
        }

        if ($normal === 'credit') {
            return round($coaOpening + $c - $d, 2);
        }

        return round($coaOpening + $d - $c, 2);
    }

    public static function normalBalance(int $accountId): string
    {
        $account = DB::table('chart_of_accounts')->where('id', $accountId)->first();
        if (! $account) {
            return 'debit';
        }

        if (! empty($account->normal_balance)) {
            return strtolower((string) $account->normal_balance) === 'credit' ? 'credit' : 'debit';
        }

        $type = strtolower((string) ($account->account_type ?? ''));

        return match (true) {
            str_contains($type, 'liabilit'),
            str_contains($type, 'equity'),
            str_contains($type, 'income'),
            str_contains($type, 'revenue') => 'credit',
            default => 'debit',
        };
    }
}
