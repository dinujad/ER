<?php

namespace App\Services\Accounting;

use App\Models\Accounting\BankStatementLine;
use App\Services\AccountingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Bank accounts (Xero / QuickBooks style): add → import → review & match → reconcile → history.
 * Every bank account sits on top of one ledger (chart of accounts) row; all history stays in that ledger.
 */
class BankAccountService
{
    public const TYPES = ['current' => 'Current account', 'savings' => 'Savings account', 'credit_card' => 'Credit card'];

    public const GL_OPENING_EQUITY = '3050';

    // ------------------------------------------------------------------ add / settings / archive

    /**
     * @param  array{bank_name: string, display_name: string, account_type: string, account_number: ?string, currency?: string,
     *               ledger_mode: string, chart_of_account_id?: ?int, opening_balance?: ?float, opening_balance_date?: ?string}  $d
     */
    public static function create(array $d, ?int $userId): object
    {
        if (!isset(self::TYPES[$d['account_type']])) {
            throw new RuntimeException('Choose current, savings or credit card.');
        }
        if (strtoupper($d['currency'] ?? 'LKR') !== 'LKR') {
            throw new RuntimeException('Only LKR accounts are supported.');
        }

        return DB::transaction(function () use ($d, $userId) {
            $isCard = $d['account_type'] === 'credit_card';
            if ($d['ledger_mode'] === 'existing') {
                $ledgerId = (int) ($d['chart_of_account_id'] ?? 0);
                $ledger = DB::table('chart_of_accounts')->where('id', $ledgerId)->first();
                if (!$ledger) {
                    throw new RuntimeException('Choose the ledger account.');
                }
                if (DB::table('bank_accounts')->where('chart_of_account_id', $ledgerId)->exists()) {
                    throw new RuntimeException('That ledger account is already linked to another bank account.');
                }
            } else {
                $ledgerId = self::createLedger($d['display_name'], $isCard);
            }

            $id = DB::table('bank_accounts')->insertGetId([
                'chart_of_account_id' => $ledgerId,
                'bank_name' => trim($d['bank_name']),
                'display_name' => trim($d['display_name']),
                'account_type' => $d['account_type'],
                'account_number' => self::cleanNumber($d['account_number'] ?? null),
                'currency' => 'LKR',
                'opening_balance' => 0,
                'opening_balance_date' => $d['opening_balance_date'] ?? null,
                'statement_source' => 'manual',
                'status' => 'active',
                'created_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Opening balance: only when the ledger does not already carry the history.
            $opening = round((float) ($d['opening_balance'] ?? 0), 2);
            $existing = self::bookBalance((object) ['chart_of_account_id' => $ledgerId, 'account_type' => $d['account_type']]);
            if (abs($opening) > 0.009 && abs($existing) < 0.009) {
                self::postOpeningBalance($id, $ledgerId, $opening, $d['opening_balance_date'] ?? now()->toDateString(), $isCard);
            } elseif (abs($existing) > 0.009) {
                DB::table('bank_accounts')->where('id', $id)->update(['opening_balance' => $existing, 'updated_at' => now()]);
            }

            return self::find($id);
        });
    }

    private static function createLedger(string $name, bool $isCard): int
    {
        [$from, $to] = $isCard ? [2050, 2099] : [1011, 1099];
        $used = DB::table('chart_of_accounts')->pluck('gl_code')->map(fn ($c) => (string) $c)->all();
        $code = null;
        for ($n = $from; $n <= $to; $n++) {
            if (!in_array((string) $n, $used, true)) {
                $code = (string) $n;
                break;
            }
        }
        if (!$code) {
            throw new RuntimeException('No free ledger code left for bank accounts.');
        }
        $row = ['gl_code' => $code, 'name' => $name, 'account_type' => $isCard ? 'liability' : 'asset', 'active' => 1];
        foreach (['normal_balance' => $isCard ? 'credit' : 'debit', 'business_id' => 0, 'allow_manual' => 1] as $col => $v) {
            if (\App\Support\SchemaCache::hasColumn('chart_of_accounts', $col)) {
                $row[$col] = $v;
            }
        }
        if (\App\Support\SchemaCache::hasColumn('chart_of_accounts', 'created_at')) {
            $row['created_at'] = now();
            $row['updated_at'] = now();
        }

        return (int) DB::table('chart_of_accounts')->insertGetId($row);
    }

    private static function postOpeningBalance(int $bankAccountId, int $ledgerId, float $opening, string $date, bool $isCard): void
    {
        $equity = AccountingService::accountId(self::GL_OPENING_EQUITY);
        if (!$equity) {
            $row = ['gl_code' => self::GL_OPENING_EQUITY, 'name' => 'Opening Balance Equity', 'account_type' => 'equity', 'active' => 1];
            foreach (['normal_balance' => 'credit', 'business_id' => 0] as $col => $v) {
                if (\App\Support\SchemaCache::hasColumn('chart_of_accounts', $col)) {
                    $row[$col] = $v;
                }
            }
            if (\App\Support\SchemaCache::hasColumn('chart_of_accounts', 'created_at')) {
                $row['created_at'] = now();
                $row['updated_at'] = now();
            }
            $equity = (int) DB::table('chart_of_accounts')->insertGetId($row);
        }
        // Money in the bank (asset) or owed on the card (liability); a negative figure is an overdraft / card credit.
        $increase = $isCard ? [$equity, $ledgerId] : [$ledgerId, $equity];
        [$dr, $cr] = $opening > 0 ? $increase : array_reverse($increase);
        $txn = AccountingService::postOnce('bank-account:' . $bankAccountId . ':opening', 'bank_account', $bankAccountId, $dr, $cr, abs($opening), $date, 'Opening balance', null, 'opening_balance');
        DB::table('bank_accounts')->where('id', $bankAccountId)->update(['opening_balance' => $opening, 'opening_transaction_number' => $txn, 'updated_at' => now()]);
    }

    public static function updateSettings(int $id, array $d): void
    {
        $acc = self::find($id);
        DB::table('bank_accounts')->where('id', $id)->update([
            'display_name' => trim($d['display_name'] ?? $acc->display_name),
            'bank_name' => trim($d['bank_name'] ?? $acc->bank_name),
            'account_number' => array_key_exists('account_number', $d) ? self::cleanNumber($d['account_number']) : $acc->account_number,
            'updated_at' => now(),
        ]);
        DB::table('chart_of_accounts')->where('id', $acc->chart_of_account_id)->update(['name' => trim($d['display_name'] ?? $acc->display_name)]);
    }

    /** Archive: hidden from lists and imports; the ledger account and its history stay in every report. */
    public static function archive(int $id, bool $acceptBalance, ?int $userId): void
    {
        $acc = self::find($id);
        $open = BankReconciliationService::linesQuery((int) $acc->chart_of_account_id)->whereIn('match_status', ['unmatched', 'suggested'])->count();
        if ($open > 0) {
            throw new RuntimeException("{$open} statement line(s) are still unmatched — reconcile the final statement first.");
        }
        $balance = self::bookBalance($acc);
        if (abs($balance) > 0.009 && !$acceptBalance) {
            throw new RuntimeException('The book balance is Rs ' . number_format($balance, 2) . '. Transfer it out first, or tick "archive with this balance".');
        }
        DB::table('bank_accounts')->where('id', $id)->update(['status' => 'archived', 'archived_at' => now(), 'archived_by' => $userId, 'feed_status' => null, 'updated_at' => now()]);
    }

    public static function restore(int $id): void
    {
        DB::table('bank_accounts')->where('id', $id)->update(['status' => 'active', 'archived_at' => null, 'archived_by' => null, 'updated_at' => now()]);
    }

    public static function disconnectFeed(int $id): void
    {
        DB::table('bank_accounts')->where('id', $id)->update(['statement_source' => 'manual', 'feed_status' => null, 'updated_at' => now()]);
    }

    // ------------------------------------------------------------------ queries

    public static function find(int $id): object
    {
        $acc = DB::table('bank_accounts')->where('id', $id)->first();
        if (!$acc) {
            throw new RuntimeException('Bank account not found.');
        }

        return $acc;
    }

    public static function forLedger(int $chartOfAccountId): ?object
    {
        return \App\Support\SchemaCache::hasTable('bank_accounts') ? DB::table('bank_accounts')->where('chart_of_account_id', $chartOfAccountId)->first() : null;
    }

    public static function masked(?string $number): string
    {
        $n = preg_replace('/\s+/', '', (string) $number);

        return $n === '' ? '—' : '•••• ' . substr($n, -4);
    }

    private static function cleanNumber(?string $n): ?string
    {
        $n = trim((string) $n);

        return $n === '' ? null : mb_substr($n, 0, 40);
    }

    /** Ledger balance in the account's natural sign: money in the bank / amount owed on the card. */
    public static function bookBalance(object $acc, ?string $asOf = null): float
    {
        $net = (float) DB::table('journal_entries')
            ->where('chart_of_account_id', $acc->chart_of_account_id)
            ->where('reversed', 0)
            ->when($asOf, fn ($q, $d) => $q->whereDate('date', '<=', $d))
            ->selectRaw('COALESCE(SUM(debit),0) - COALESCE(SUM(credit),0) as b')->value('b');

        return round(($acc->account_type === 'credit_card' ? -1 : 1) * $net, 2);
    }

    /** List rows with book balance, latest statement balance, unmatched count, last reconciled. */
    public static function summaries(bool $includeArchived = false): Collection
    {
        return DB::table('bank_accounts')
            ->when(!$includeArchived, fn ($q) => $q->where('status', 'active'))
            ->orderBy('status')->orderBy('display_name')
            ->get()
            ->map(function ($a) {
                $latest = DB::table('bank_statement_imports')->where('chart_of_account_id', $a->chart_of_account_id)
                    ->where('status', 'imported')->whereNotNull('closing_balance')->orderByDesc('statement_to')->orderByDesc('id')->first();
                $a->gl_code = DB::table('chart_of_accounts')->where('id', $a->chart_of_account_id)->value('gl_code');
                $a->book_balance = self::bookBalance($a);
                $a->statement_balance = $latest ? round((float) $latest->closing_balance, 2) : null;
                $a->statement_date = $latest?->statement_to;
                $a->unmatched = BankReconciliationService::linesQuery((int) $a->chart_of_account_id)->whereIn('match_status', ['unmatched', 'suggested'])->count();
                $a->last_reconciled = \App\Support\SchemaCache::hasTable('bank_reconciliations')
                    ? DB::table('bank_reconciliations')->where('bank_account_id', $a->id)->where('status', 'completed')->max('statement_date')
                    : null;
                $a->masked = self::masked($a->account_number);

                return $a;
            });
    }

    // ------------------------------------------------------------------ review: categorise & post / transfer

    /**
     * Bank line that is not in the books yet (bank charge, interest, standing order …):
     * post it against the chosen account and match the line to the new journal.
     */
    public static function categoriseAndPost(BankStatementLine $line, int $accountId, ?string $description, ?int $userId): string
    {
        $line->loadMissing('import');
        $bankLedger = (int) $line->import->chart_of_account_id;
        if ($accountId === $bankLedger) {
            throw new RuntimeException('Choose a different account from the bank itself.');
        }
        if (!DB::table('chart_of_accounts')->where('id', $accountId)->where('active', 1)->exists()) {
            throw new RuntimeException('Choose an active account.');
        }
        if ($line->match_status === 'matched') {
            throw new RuntimeException('This bank line is already matched.');
        }
        $signed = BankReconciliationService::signedLine($line);
        [$dr, $cr] = $signed > 0 ? [$bankLedger, $accountId] : [$accountId, $bankLedger];
        $name = trim($description ?: (string) $line->description) ?: 'Bank transaction';

        return DB::transaction(function () use ($line, $dr, $cr, $signed, $name, $bankLedger, $userId) {
            $txn = AccountingService::postOnce('bank-line:' . $line->id . ':post', 'bank_statement_line', (int) $line->id, $dr, $cr, abs($signed),
                $line->txn_date?->toDateString() ?? now()->toDateString(), mb_substr($name, 0, 190), $line->reference, 'bank_entry');
            $bankJournalLine = (int) DB::table('journal_entries')->where('transaction_number', $txn)->where('chart_of_account_id', $bankLedger)->value('id');
            BankReconciliationService::match($line, [['type' => 'journal_entry', 'id' => $bankJournalLine]], false, $userId);

            return $txn;
        });
    }

    /**
     * Money moved between two of our own accounts. Posts Dr receiving / Cr paying account (once), and matches this
     * statement line; the other account's statement line matches the same transfer later (or now, if already imported).
     */
    public static function transfer(BankStatementLine $line, int $otherBankAccountId, ?int $userId): string
    {
        $line->loadMissing('import');
        $thisLedger = (int) $line->import->chart_of_account_id;
        $other = self::find($otherBankAccountId);
        $otherLedger = (int) $other->chart_of_account_id;
        if ($otherLedger === $thisLedger) {
            throw new RuntimeException('Choose the other account of the transfer.');
        }
        if ($line->match_status === 'matched') {
            throw new RuntimeException('This bank line is already matched.');
        }
        $signed = BankReconciliationService::signedLine($line);
        $amount = abs($signed);
        $date = $line->txn_date?->toDateString() ?? now()->toDateString();
        [$dr, $cr] = $signed > 0 ? [$thisLedger, $otherLedger] : [$otherLedger, $thisLedger];
        $thisName = DB::table('chart_of_accounts')->where('id', $thisLedger)->value('name');

        return DB::transaction(function () use ($line, $dr, $cr, $amount, $date, $thisLedger, $otherLedger, $other, $thisName, $signed, $userId) {
            $txn = AccountingService::postOnce('bank-transfer:' . $line->id, 'bank_transfer', (int) $line->id, $dr, $cr, $amount, $date,
                'Transfer ' . ($signed > 0 ? 'from ' . $other->display_name : 'to ' . $other->display_name), $line->reference, 'bank_transfer');
            $thisSide = (int) DB::table('journal_entries')->where('transaction_number', $txn)->where('chart_of_account_id', $thisLedger)->value('id');
            BankReconciliationService::match($line, [['type' => 'journal_entry', 'id' => $thisSide]], false, $userId);

            // If the other account's statement already has the opposite line (same amount, ±5 days), match it too.
            $otherSide = (int) DB::table('journal_entries')->where('transaction_number', $txn)->where('chart_of_account_id', $otherLedger)->value('id');
            $twin = BankReconciliationService::linesQuery($otherLedger)
                ->whereIn('match_status', ['unmatched', 'suggested'])
                ->whereBetween('txn_date', [date('Y-m-d', strtotime($date . ' -5 days')), date('Y-m-d', strtotime($date . ' +5 days'))])
                ->get()
                ->first(fn ($l) => abs(BankReconciliationService::signedLine($l) + $signed) < 0.005);
            if ($twin) {
                BankReconciliationService::match($twin, [['type' => 'journal_entry', 'id' => $otherSide]], false, $userId);
            }

            return $txn;
        });
    }

    // ------------------------------------------------------------------ reconcile (statement → cleared ledger lines → difference 0)

    /** Ending balance of the last completed reconciliation (0 before the first one). */
    public static function beginningBalance(object $acc): float
    {
        if (!\App\Support\SchemaCache::hasTable('bank_cleared_entries')) {
            return 0.0;
        }

        return round((float) DB::table('bank_cleared_entries as c')
            ->join('bank_reconciliations as r', 'r.id', '=', 'c.bank_reconciliation_id')
            ->where('r.bank_account_id', $acc->id)->where('r.status', 'completed')->sum('c.amount'), 2);
    }

    /**
     * Ledger lines not cleared yet, up to the statement date. Lines already matched to a bank statement line come pre-ticked.
     *
     * @return Collection<int, object>
     */
    public static function unclearedLines(object $acc, string $statementDate): Collection
    {
        $sign = $acc->account_type === 'credit_card' ? -1 : 1;
        $cleared = DB::table('bank_cleared_entries')->pluck('journal_entry_id')->flip();
        $matched = BankReconciliationService::matchedSourceKeys();

        return DB::table('journal_entries')
            ->where('chart_of_account_id', $acc->chart_of_account_id)
            ->where('reversed', 0)
            ->whereDate('date', '<=', $statementDate)
            ->orderBy('date')->orderBy('id')
            ->get()
            ->reject(fn ($j) => isset($cleared[$j->id]))
            ->map(function ($j) use ($sign, $matched) {
                $j->signed = round($sign * ((float) $j->debit - (float) $j->credit), 2);
                $short = SourceDocumentResolver::shortType((string) $j->source_type);
                $j->on_statement = isset($matched['journal_entry#' . $j->id])
                    || ($short === 'payment' && isset($matched['payment#' . $j->source_id]))
                    || ($short === 'purchase_payment' && isset($matched['purchase_payment#' . $j->source_id]))
                    || $short === 'bank_fee' || $short === 'bank_statement_line' || $short === 'bank_transfer';
                $j->source = SourceDocumentResolver::resolve($j->source_type, $j->source_id);

                return $j;
            })
            ->values();
    }

    /** @param array<int, int> $journalIds */
    public static function finishReconciliation(object $acc, string $statementDate, float $statementBalance, array $journalIds, ?string $notes, ?int $userId): int
    {
        $last = DB::table('bank_reconciliations')->where('bank_account_id', $acc->id)->where('status', 'completed')->max('statement_date');
        if ($last && $statementDate < (string) $last) {
            throw new RuntimeException('The statement date must be after the last reconciliation (' . $last . ').');
        }
        $lines = self::unclearedLines($acc, $statementDate)->keyBy('id');
        $selected = collect($journalIds)->map(fn ($i) => (int) $i)->unique()->filter(fn ($i) => $lines->has($i));
        $begin = self::beginningBalance($acc);
        $cleared = round((float) $selected->sum(fn ($i) => $lines[$i]->signed), 2);
        $diff = round($statementBalance - ($begin + $cleared), 2);
        if (abs($diff) > 0.009) {
            throw new RuntimeException('Difference is ' . number_format($diff, 2) . ' — it must be 0.00 to finish. Tick the cleared lines, or record the missing transaction.');
        }

        return DB::transaction(function () use ($acc, $statementDate, $statementBalance, $selected, $lines, $begin, $cleared, $notes, $userId) {
            $id = DB::table('bank_reconciliations')->insertGetId([
                'bank_account_id' => $acc->id, 'statement_date' => $statementDate, 'beginning_balance' => $begin,
                'statement_balance' => round($statementBalance, 2), 'cleared_total' => $cleared, 'cleared_count' => $selected->count(),
                'status' => 'completed', 'notes' => $notes, 'completed_by' => $userId, 'completed_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($selected as $jid) {
                DB::table('bank_cleared_entries')->insert(['bank_reconciliation_id' => $id, 'journal_entry_id' => $jid, 'amount' => $lines[$jid]->signed, 'created_at' => now(), 'updated_at' => now()]);
            }

            return $id;
        });
    }

    /** Undo the most recent reconciliation only (keeps the chain of balances intact). */
    public static function undoLastReconciliation(object $acc, int $reconciliationId, ?int $userId): void
    {
        $last = DB::table('bank_reconciliations')->where('bank_account_id', $acc->id)->where('status', 'completed')->orderByDesc('statement_date')->orderByDesc('id')->first();
        if (!$last || (int) $last->id !== $reconciliationId) {
            throw new RuntimeException('Only the most recent reconciliation can be undone.');
        }
        DB::transaction(function () use ($reconciliationId, $userId) {
            DB::table('bank_cleared_entries')->where('bank_reconciliation_id', $reconciliationId)->delete();
            DB::table('bank_reconciliations')->where('id', $reconciliationId)->update(['status' => 'undone', 'undone_by' => $userId, 'undone_at' => now(), 'updated_at' => now()]);
        });
    }
}
