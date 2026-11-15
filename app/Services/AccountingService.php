<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Posts double-entry journal entries into the accounting module's journal_entries table.
 *
 * Each transaction creates 2 rows that share a transaction_number:
 *   row 1 — DEBIT  (debit > 0, credit = 0)
 *   row 2 — CREDIT (debit = 0, credit > 0)
 */
class AccountingService
{
    // Well-known GL codes — seeded by ChartOfAccountsSeeder
    public const GL_CASH             = '1000';
    public const GL_BANK             = '1010';
    public const GL_PDC_RECEIVABLE   = '1150'; // Post-dated / uncleared cheques
    public const GL_ACCOUNTS_RECV    = '1200';
    public const GL_INVENTORY        = '1300';
    public const GL_RAW_MATERIALS    = '1310';
    public const GL_WORK_IN_PROGRESS = '1320';
    public const GL_FINISHED_GOODS   = '1330';
    public const GL_ACCOUNTS_PAY     = '2000';
    public const GL_PAYROLL_PAYABLE  = '2100';
    public const GL_OWNER_EQUITY     = '3000';
    public const GL_SALES_REVENUE    = '4000';
    public const GL_SALES_RETURNS    = '4010';
    public const GL_COST_OF_GOODS    = '5000';
    public const GL_PURCHASES        = '5100';
    public const GL_GENERAL_EXPENSE  = '6000';
    public const GL_SALARY_EXPENSE   = '6100';
    public const GL_UTILITIES_EXPENSE = '6200';
    public const GL_TRANSPORT_EXPENSE = '6300';
    public const GL_OTHER_EXPENSE    = '6900';

    /** Return the chart_of_accounts.id for a GL code (or null if not found). */
    public static function accountId(string $glCode): ?int
    {
        $query = DB::table('chart_of_accounts')->where('gl_code', $glCode)->where('active', 1);

        if (Schema::hasColumn('chart_of_accounts', 'business_id')) {
            $query->orderByRaw('CASE WHEN business_id IN (0, 1) THEN 0 ELSE 1 END')
                ->orderBy('business_id');
        }

        $row = $query->first();

        return $row ? (int) $row->id : null;
    }

    public static function expenseAccountId(?string $categoryCode, ?string $categoryName = null): ?int
    {
        $haystack = strtoupper(trim(($categoryCode ?? '') . ' ' . ($categoryName ?? '')));

        $gl = match (true) {
            str_contains($haystack, 'SALAR') || str_contains($haystack, 'WAGE') || str_contains($haystack, 'LABOR') || str_contains($haystack, 'LABOUR') => self::GL_SALARY_EXPENSE,
            str_contains($haystack, 'ELECTRIC') || str_contains($haystack, 'WATER') || str_contains($haystack, 'UTILIT') || str_contains($haystack, 'COMMUNIC') => self::GL_UTILITIES_EXPENSE,
            str_contains($haystack, 'TRANSPORT') || str_contains($haystack, 'FUEL') || str_contains($haystack, 'DELIVER') || str_contains($haystack, 'COURIER') => self::GL_TRANSPORT_EXPENSE,
            default => self::GL_GENERAL_EXPENSE,
        };

        return self::accountId($gl)
            ?? self::accountId(self::GL_GENERAL_EXPENSE)
            ?? self::accountId(self::GL_OTHER_EXPENSE);
    }

    /**
     * Post a balanced pair of journal entries.
     *
     * @param int         $debitAccountId   chart_of_accounts.id for the DEBIT side
     * @param int         $creditAccountId  chart_of_accounts.id for the CREDIT side
     * @param float       $amount
     * @param string      $date             Y-m-d
     * @param string      $name             short description shown in the journal
     * @param string|null $reference        invoice / purchase / expense ref
     * @param string      $transactionType  e.g. 'expense', 'purchase', 'sale', 'payment'
     * @param array       $extra            any additional columns to merge in
     *
     * @return string  The shared transaction_number for these two rows
     */
    public static function postEntry(
        int    $debitAccountId,
        int    $creditAccountId,
        float  $amount,
        string $date,
        string $name,
        ?string $reference   = null,
        string $transactionType = 'manual_entry',
        array  $extra = []
    ): string {
        if ($amount <= 0) {
            throw new RuntimeException('Journal amount must be greater than zero.');
        }
        if ($debitAccountId === $creditAccountId) {
            throw new RuntimeException('Debit and credit accounts must be different.');
        }

        return DB::transaction(function () use (
            $debitAccountId,
            $creditAccountId,
            $amount,
            $date,
            $name,
            $reference,
            $transactionType,
            $extra
        ) {
            $txnNumber = 'TXN-' . strtoupper(Str::random(8));
            $month = date('n', strtotime($date));
            $year  = date('Y', strtotime($date));
            $now   = now();

            $base = array_merge([
                'transaction_number'  => $txnNumber,
                'transaction_type'    => $transactionType,
                'name'                => $name,
                'date'                => $date,
                'month'               => $month,
                'year'                => $year,
                'reference'           => $reference,
                'active'              => 1,
                'reversed'            => 0,
                'reversible'          => 1,
                'manual_entry'        => 0,
                'created_at'          => $now,
                'updated_at'          => $now,
            ], $extra);

            if (Schema::hasColumn('journal_entries', 'created_by_id') && ! array_key_exists('created_by_id', $base)) {
                $base['created_by_id'] = auth()->id();
            }

            DB::table('journal_entries')->insert(array_merge($base, [
                'chart_of_account_id' => $debitAccountId,
                'debit'               => $amount,
                'credit'              => 0,
            ]));

            DB::table('journal_entries')->insert(array_merge($base, [
                'chart_of_account_id' => $creditAccountId,
                'debit'               => 0,
                'credit'              => $amount,
            ]));

            return $txnNumber;
        });
    }

    /**
     * Post one business event exactly once. The unique event key protects
     * controller retries, observers and queue retries from duplicate journals.
     */
    public static function postOnce(
        string $eventKey,
        string $sourceType,
        int $sourceId,
        int $debitAccountId,
        int $creditAccountId,
        float $amount,
        string $date,
        string $name,
        ?string $reference,
        string $transactionType,
        array $extra = []
    ): string {
        return DB::transaction(function () use (
            $eventKey,
            $sourceType,
            $sourceId,
            $debitAccountId,
            $creditAccountId,
            $amount,
            $date,
            $name,
            $reference,
            $transactionType,
            $extra
        ) {
            $existing = DB::table('accounting_postings')
                ->where('event_key', $eventKey)
                ->lockForUpdate()
                ->first();

            if ($existing && ! $existing->reversed && $existing->transaction_number !== 'PENDING') {
                return $existing->transaction_number;
            }

            if ($existing && $existing->transaction_number === 'PENDING' && ! $existing->reversed) {
                $recovered = DB::table('journal_entries')
                    ->where('source_type', $sourceType)
                    ->where('source_id', $sourceId)
                    ->where('transaction_type', $transactionType)
                    ->where('reversed', 0)
                    ->where('transaction_number', '!=', 'PENDING')
                    ->value('transaction_number');

                if ($recovered) {
                    DB::table('accounting_postings')->where('id', $existing->id)->update([
                        'transaction_number' => $recovered,
                        'amount' => $amount,
                        'updated_at' => now(),
                    ]);

                    return $recovered;
                }
            }

            if (!$existing) {
                DB::table('accounting_postings')->insertOrIgnore([
                    'event_key' => $eventKey,
                    'transaction_number' => 'PENDING',
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'event_type' => $transactionType,
                    'amount' => $amount,
                    'reversed' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $existing = DB::table('accounting_postings')
                    ->where('event_key', $eventKey)
                    ->lockForUpdate()
                    ->first();

                if ($existing && $existing->transaction_number !== 'PENDING' && !$existing->reversed) {
                    return $existing->transaction_number;
                }
            }

            $txn = self::postEntry(
                $debitAccountId,
                $creditAccountId,
                $amount,
                $date,
                $name,
                $reference,
                $transactionType,
                array_merge($extra, ['source_type' => $sourceType, 'source_id' => $sourceId])
            );

            DB::table('accounting_postings')->updateOrInsert(
                ['event_key' => $eventKey],
                [
                    'transaction_number' => $txn,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'event_type' => $transactionType,
                    'amount' => $amount,
                    'reversed' => false,
                    'created_at' => $existing?->created_at ?? now(),
                    'updated_at' => now(),
                ]
            );

            return $txn;
        });
    }

    public static function reverseEvent(string $eventKey): void
    {
        DB::transaction(function () use ($eventKey) {
            $posting = DB::table('accounting_postings')
                ->where('event_key', $eventKey)
                ->lockForUpdate()
                ->first();

            if (!$posting || $posting->reversed) {
                return;
            }

            self::reverseEntry($posting->transaction_number);
            DB::table('accounting_postings')
                ->where('id', $posting->id)
                ->update(['reversed' => true, 'updated_at' => now()]);
        });
    }

    /**
     * @param  'in'|'out'  $direction  Customer receipts use PDC for cheques; supplier/expense outflows use Bank.
     */
    public static function settlementAccountId(?string $method, string $direction = 'in'): ?int
    {
        $method = strtolower(trim((string) $method));

        if ($direction === 'out') {
            return match ($method) {
                'bank', 'bank_transfer', 'card', 'mobile', 'online', 'cheque' => self::accountId(self::GL_BANK),
                default => self::accountId(self::GL_CASH),
            };
        }

        return match ($method) {
            'bank', 'bank_transfer', 'card', 'mobile', 'online' => self::accountId(self::GL_BANK),
            'cheque' => self::accountId(self::GL_PDC_RECEIVABLE),
            default => self::accountId(self::GL_CASH),
        };
    }

    /**
     * Keep one live journal pair per source + type. Extra pairs (retries / double observers) are reversed.
     *
     * @return array{groups: int, reversed: int}
     */
    public static function collapseDuplicateJournals(): array
    {
        if (! Schema::hasTable('journal_entries')) {
            return ['groups' => 0, 'reversed' => 0];
        }

        $rows = DB::table('journal_entries')
            ->where('reversed', 0)
            ->whereNotNull('source_type')
            ->whereNotNull('source_id')
            ->whereNotNull('transaction_type')
            ->select('source_type', 'source_id', 'transaction_type', 'transaction_number')
            ->distinct()
            ->orderBy('transaction_number')
            ->get();

        $groups = $rows->groupBy(
            fn ($row) => $row->source_type . '|' . $row->source_id . '|' . $row->transaction_type
        );

        $reversed = 0;
        $duplicateGroups = 0;

        foreach ($groups as $txns) {
            $numbers = $txns->pluck('transaction_number')->unique()->values();
            if ($numbers->count() <= 1) {
                continue;
            }

            $duplicateGroups++;
            $keep = $numbers->first();
            foreach ($numbers->skip(1) as $number) {
                self::reverseEntry((string) $number);
                $reversed++;
            }
            unset($keep);
        }

        return ['groups' => $duplicateGroups, 'reversed' => $reversed];
    }

    /**
     * Reverse (void) all journal entries with the given transaction_number.
     * Sets reversed = 1 on both rows.
     */
    public static function reverseEntry(string $transactionNumber): void
    {
        if ($transactionNumber === '' || $transactionNumber === 'PENDING') {
            return;
        }

        DB::table('journal_entries')
            ->where('transaction_number', $transactionNumber)
            ->update(['reversed' => 1, 'updated_at' => now()]);

        if (Schema::hasTable('accounting_postings')) {
            DB::table('accounting_postings')
                ->where('transaction_number', $transactionNumber)
                ->where('reversed', false)
                ->update(['reversed' => true, 'updated_at' => now()]);
        }
    }

    /**
     * Reverse an existing entry and post a corrected one.
     * Returns the new transaction_number.
     */
    public static function replaceEntry(
        string $oldTransactionNumber,
        int    $debitAccountId,
        int    $creditAccountId,
        float  $amount,
        string $date,
        string $name,
        ?string $reference   = null,
        string $transactionType = 'manual_entry',
        array  $extra = []
    ): string {
        self::reverseEntry($oldTransactionNumber);
        return self::postEntry($debitAccountId, $creditAccountId, $amount, $date, $name, $reference, $transactionType, $extra);
    }
}
