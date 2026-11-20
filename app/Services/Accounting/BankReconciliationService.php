<?php

namespace App\Services\Accounting;

use App\Models\Accounting\BankStatementImport;
use App\Models\Accounting\BankStatementLine;
use App\Services\AccountingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Phase 7 — Xero-style bank reconciliation workspace.
 *
 * Sign convention everywhere: money INTO the bank is positive, money OUT is negative.
 *  - statement line: credit − debit
 *  - customer payment: +amount (refund: −amount)
 *  - supplier payment: −amount (refund received: +amount)
 *  - journal line on the bank account: debit − credit
 */
class BankReconciliationService
{
    public const GL_BANK_CHARGES = '6050';

    public const SOURCE_TYPES = ['payment', 'purchase_payment', 'journal_entry'];

    // ------------------------------------------------------------------ mapping

    /** @return array<string, int|null>|null 0-based column indexes */
    public static function mappingFor(int $accountId): ?array
    {
        if (!\App\Support\SchemaCache::hasTable('bank_import_mappings')) {
            return null;
        }
        $row = DB::table('bank_import_mappings')->where('chart_of_account_id', $accountId)->first();

        return $row ? json_decode((string) $row->column_map, true) : null;
    }

    /** @param array<string, int|null> $map 0-based column indexes */
    public static function saveMapping(int $accountId, array $map, ?int $userId = null): void
    {
        $clean = [];
        foreach (['date', 'description', 'reference', 'debit', 'credit', 'amount'] as $k) {
            $clean[$k] = isset($map[$k]) && $map[$k] !== '' && $map[$k] !== null ? (int) $map[$k] : null;
        }
        if ($clean['date'] === null || $clean['description'] === null) {
            throw new RuntimeException('Date and description columns are required in the mapping.');
        }
        if ($clean['amount'] === null && $clean['debit'] === null && $clean['credit'] === null) {
            throw new RuntimeException('Map either an Amount column, or Debit / Credit columns.');
        }
        DB::table('bank_import_mappings')->updateOrInsert(
            ['chart_of_account_id' => $accountId],
            ['column_map' => json_encode($clean), 'updated_by' => $userId, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    // ------------------------------------------------------------------ workspace

    /**
     * @return array<string, mixed>
     */
    public static function workspace(int $accountId): array
    {
        $imports = BankStatementImport::query()
            ->where('chart_of_account_id', $accountId)
            ->where('status', 'imported')
            ->orderByDesc('id')
            ->get();
        $latest = $imports->first(fn ($i) => $i->closing_balance !== null) ?? $imports->first();
        $asOf = $latest?->statement_to
            ? \Illuminate\Support\Carbon::parse($latest->statement_to)->toDateString()
            : (self::lastLineDate($accountId) ?? now()->toDateString());

        $queue = self::linesQuery($accountId)->whereIn('match_status', ['unmatched', 'suggested'])->orderBy('txn_date')->orderBy('id')->get();
        $statementBalance = $latest?->closing_balance !== null ? round((float) $latest->closing_balance, 2) : null;
        $glBalance = self::glBalance($accountId, $asOf);
        $unpresented = self::unpresentedGlLines($accountId, $asOf);
        $unmatchedStatement = round((float) $queue->filter(fn ($l) => $l->txn_date && $l->txn_date->toDateString() <= $asOf)->sum(fn ($l) => self::signedLine($l)), 2);

        return [
            'imports' => $imports,
            'latest' => $latest,
            'as_of' => $asOf,
            'queue' => $queue,
            'recent_matched' => self::linesQuery($accountId)->where('match_status', 'matched')->orderByDesc('matched_at')->limit(20)->get(),
            'statement_balance' => $statementBalance,
            'gl_balance' => $glBalance,
            'difference' => $statementBalance === null ? null : round($statementBalance - $glBalance, 2),
            'unmatched_statement_total' => $unmatchedStatement,
            'unpresented' => $unpresented,
            'unpresented_total' => round((float) $unpresented->sum('signed'), 2),
            // statement − GL should equal (statement lines not in GL) − (GL lines not on statement)
            'explained' => round($unmatchedStatement - (float) $unpresented->sum('signed'), 2),
        ];
    }

    public static function linesQuery(int $accountId)
    {
        return BankStatementLine::query()
            ->whereHas('import', fn ($q) => $q->where('chart_of_account_id', $accountId)->where('status', 'imported'));
    }

    public static function signedLine(BankStatementLine $line): float
    {
        return round((float) $line->credit - (float) $line->debit, 2);
    }

    public static function glBalance(int $accountId, string $asOf): float
    {
        return round((float) DB::table('journal_entries')
            ->where('chart_of_account_id', $accountId)
            ->where('reversed', 0)
            ->whereDate('date', '<=', $asOf)
            ->selectRaw('COALESCE(SUM(debit),0) - COALESCE(SUM(credit),0) as b')
            ->value('b'), 2);
    }

    /**
     * Bank-account journal lines up to $asOf that no statement line has been matched to
     * (cheques not yet presented, deposits in transit, or simply not reconciled yet).
     */
    public static function unpresentedGlLines(int $accountId, string $asOf): Collection
    {
        $matched = self::matchedSourceKeys();

        return DB::table('journal_entries')
            ->where('chart_of_account_id', $accountId)
            ->where('reversed', 0)
            ->whereDate('date', '<=', $asOf)
            ->where(fn ($w) => $w->whereNull('source_type')->orWhere('source_type', '!=', 'bank_fee'))
            ->orderBy('date')->orderBy('id')
            ->get()
            ->map(function ($j) {
                $j->signed = round((float) $j->debit - (float) $j->credit, 2);
                $j->match_key = self::journalMatchKey($j);

                return $j;
            })
            ->reject(fn ($j) => isset($matched[$j->match_key]) || isset($matched['journal_entry#' . $j->id]))
            ->values();
    }

    /** A GL bank line belongs to the payment / supplier payment it was posted for. */
    private static function journalMatchKey(object $j): string
    {
        $short = SourceDocumentResolver::shortType((string) $j->source_type);
        if ($short === 'payment' && $j->source_id) {
            return 'payment#' . $j->source_id;
        }
        if ($short === 'purchase_payment' && $j->source_id) {
            return 'purchase_payment#' . $j->source_id;
        }

        return 'journal_entry#' . $j->id;
    }

    /** @return array<string, true> "type#id" of every ERP transaction already matched to a bank line */
    public static function matchedSourceKeys(): array
    {
        $keys = [];
        foreach (DB::table('bank_statement_lines')->where('match_status', 'matched')->whereNotNull('matched_source_type')->get(['matched_source_type', 'matched_source_id']) as $r) {
            if ($r->matched_source_type !== 'multiple') {
                $keys[$r->matched_source_type . '#' . $r->matched_source_id] = true;
            }
        }
        if (\App\Support\SchemaCache::hasTable('bank_statement_line_matches')) {
            foreach (DB::table('bank_statement_line_matches')->get(['source_type', 'source_id']) as $r) {
                $keys[$r->source_type . '#' . $r->source_id] = true;
            }
        }

        return $keys;
    }

    private static function lastLineDate(int $accountId): ?string
    {
        $d = self::linesQuery($accountId)->max('txn_date');

        return $d ? \Illuminate\Support\Carbon::parse($d)->toDateString() : null;
    }

    // ------------------------------------------------------------------ candidates

    /**
     * Unmatched ERP transactions in the same direction as the bank line, within ±$days.
     *
     * @return Collection<int, object{type: string, id: int, date: ?string, label: string, reference: ?string, amount: float, url: ?string}>
     */
    public static function candidates(BankStatementLine $line, int $days = 30, ?string $search = null): Collection
    {
        $signed = self::signedLine($line);
        $date = $line->txn_date?->toDateString() ?? now()->toDateString();
        $from = date('Y-m-d', strtotime($date . " -{$days} days"));
        $to = date('Y-m-d', strtotime($date . " +{$days} days"));
        $matched = self::matchedSourceKeys();
        $out = collect();

        if (\App\Support\SchemaCache::hasTable('payments')) {
            $rows = DB::table('payments')
                ->leftJoin('orders', 'orders.id', '=', 'payments.order_id')
                ->leftJoin('customers', 'customers.id', '=', 'orders.customer_id')
                ->whereBetween('payments.payment_date', [$from, $to])
                ->select('payments.*', 'orders.invoice_number', 'customers.name as customer_name', 'customers.business_name')
                ->when($search, fn ($q, $s) => $q->where(fn ($w) => $w->where('payments.reference_number', 'like', "%{$s}%")
                    ->orWhere('orders.invoice_number', 'like', "%{$s}%")->orWhere('customers.name', 'like', "%{$s}%")->orWhere('customers.business_name', 'like', "%{$s}%")))
                ->limit(300)->get();
            foreach ($rows as $p) {
                $method = strtolower(trim((string) $p->payment_method));
                if ($method === 'cash') {
                    continue;
                }
                $amt = round((float) $p->amount, 2) * (($p->entry_type ?? null) === 'refund' ? -1 : 1);
                if (isset($matched['payment#' . $p->id]) || ($amt > 0) !== ($signed > 0)) {
                    continue;
                }
                $out->push((object) [
                    'type' => 'payment', 'id' => (int) $p->id, 'date' => $p->payment_date,
                    'label' => 'Customer payment · ' . ($p->business_name ?: $p->customer_name ?: '—') . ($p->invoice_number ? ' · ' . $p->invoice_number : ''),
                    'reference' => $p->reference_number, 'amount' => $amt,
                    'url' => $p->order_id ? url('accounting/phase6/invoices/' . $p->order_id) . '#payments' : null,
                ]);
            }
        }

        if (\App\Support\SchemaCache::hasTable('purchase_payments')) {
            $rows = DB::table('purchase_payments')
                ->leftJoin('suppliers', 'suppliers.id', '=', 'purchase_payments.supplier_id')
                ->leftJoin('purchases', 'purchases.id', '=', 'purchase_payments.purchase_id')
                ->whereBetween('purchase_payments.payment_date', [$from, $to])
                ->select('purchase_payments.*', 'suppliers.name as supplier_name', 'suppliers.company_name', 'purchases.purchase_no')
                ->when($search, fn ($q, $s) => $q->where(fn ($w) => $w->where('purchase_payments.reference', 'like', "%{$s}%")
                    ->orWhere('purchase_payments.cheque_number', 'like', "%{$s}%")->orWhere('suppliers.name', 'like', "%{$s}%")->orWhere('purchases.purchase_no', 'like', "%{$s}%")))
                ->limit(300)->get();
            foreach ($rows as $p) {
                if (strtolower(trim((string) $p->payment_method)) === 'cash') {
                    continue;
                }
                $amt = -round((float) $p->amount, 2) * (($p->entry_type ?? null) === 'refund' ? -1 : 1);
                if (isset($matched['purchase_payment#' . $p->id]) || ($amt > 0) !== ($signed > 0)) {
                    continue;
                }
                $out->push((object) [
                    'type' => 'purchase_payment', 'id' => (int) $p->id, 'date' => $p->payment_date,
                    'label' => 'Supplier payment · ' . ($p->company_name ?: $p->supplier_name ?: '—') . ($p->purchase_no ? ' · ' . $p->purchase_no : ''),
                    'reference' => $p->reference ?: $p->cheque_number, 'amount' => $amt,
                    'url' => $p->purchase_id ? url('accounting/phase6/bills/' . $p->purchase_id) . '#payments' : null,
                ]);
            }
        }

        // Other bank-account journal lines (manual journals, transfers, expenses paid by bank …)
        $accountId = (int) $line->import?->chart_of_account_id;
        if ($accountId) {
            $rows = DB::table('journal_entries')
                ->where('chart_of_account_id', $accountId)
                ->where('reversed', 0)
                ->whereBetween('date', [$from, $to])
                ->when($search, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('reference', 'like', "%{$s}%")))
                ->limit(300)->get();
            foreach ($rows as $j) {
                $short = SourceDocumentResolver::shortType((string) $j->source_type);
                if (in_array($short, ['payment', 'purchase_payment', 'bank_fee'], true)) {
                    continue; // offered above as the payment itself
                }
                $amt = round((float) $j->debit - (float) $j->credit, 2);
                if (isset($matched['journal_entry#' . $j->id]) || ($amt > 0) !== ($signed > 0)) {
                    continue;
                }
                $src = SourceDocumentResolver::resolve($j->source_type, $j->source_id);
                $out->push((object) [
                    'type' => 'journal_entry', 'id' => (int) $j->id, 'date' => $j->date,
                    'label' => 'Journal · ' . $j->name, 'reference' => $j->reference ?: $j->transaction_number, 'amount' => $amt,
                    'url' => $src['url'] ?? url('accounting/phase6/journal/' . urlencode($j->transaction_number)),
                ]);
            }
        }

        // Best first: exact amount, then closest date.
        return $out->sortBy(fn ($c) => [abs(abs($c->amount) - abs($signed)) < 0.005 ? 0 : 1, abs(strtotime((string) $c->date) - strtotime($date))])->values();
    }

    // ------------------------------------------------------------------ matching

    /**
     * Match one bank line to one or more ERP transactions.
     * When the transactions add up to MORE than the bank line, the difference is a bank fee
     * (net settlement) and is posted: Dr Bank charges / Cr Bank.
     *
     * @param  array<int, array{type: string, id: int}>  $sources
     * @return array{line: BankStatementLine, fee: float}
     */
    public static function match(BankStatementLine $line, array $sources, bool $allowFee = true, ?int $userId = null): array
    {
        if ($sources === []) {
            throw new RuntimeException('Select at least one transaction.');
        }

        return DB::transaction(function () use ($line, $sources, $allowFee, $userId) {
            $locked = BankStatementLine::query()->with('import')->where('id', $line->id)->lockForUpdate()->firstOrFail();
            if ($locked->match_status === 'matched') {
                throw new RuntimeException('This bank line is already matched.');
            }
            $matched = self::matchedSourceKeys();
            $rows = [];
            $sum = 0.0;
            foreach ($sources as $s) {
                $type = (string) ($s['type'] ?? '');
                $id = (int) ($s['id'] ?? 0);
                if (!in_array($type, self::SOURCE_TYPES, true) || $id <= 0) {
                    throw new RuntimeException('Unsupported transaction type.');
                }
                if (isset($matched[$type . '#' . $id])) {
                    throw new RuntimeException(ucfirst(str_replace('_', ' ', $type)) . " #{$id} is already matched to another bank line.");
                }
                $amount = self::sourceAmount($type, $id);
                $rows[] = ['type' => $type, 'id' => $id, 'amount' => $amount];
                $sum = round($sum + $amount, 2);
            }

            $lineSigned = self::signedLine($locked);
            if (($sum > 0) !== ($lineSigned > 0)) {
                throw new RuntimeException('Selected transactions go the other way from the bank line (money in vs money out).');
            }
            // Fee = what the ERP says moved minus what the bank actually moved (always a cost).
            $fee = round(abs($sum) - abs($lineSigned), 2);
            if ($fee < -0.009) {
                throw new RuntimeException('Selected transactions total Rs ' . number_format(abs($sum), 2) . ', less than the bank line Rs '
                    . number_format(abs($lineSigned), 2) . '. Add the missing transaction or record it first.');
            }
            if ($fee > 0.009 && !$allowFee) {
                throw new RuntimeException('Difference of Rs ' . number_format($fee, 2) . ' — tick "post the difference as a bank fee" to match.');
            }

            $feeTxn = null;
            if ($fee > 0.009) {
                $bankId = (int) $locked->import->chart_of_account_id;
                $chargesId = AccountingService::accountId(self::GL_BANK_CHARGES) ?? AccountingService::accountId(AccountingService::GL_GENERAL_EXPENSE);
                if (!$chargesId) {
                    throw new RuntimeException('Bank charges account (6050) is missing.');
                }
                $feeTxn = AccountingService::postOnce(
                    'bank-fee:' . $locked->id,
                    'bank_fee',
                    (int) $locked->id,
                    $chargesId,
                    $bankId,
                    $fee,
                    $locked->txn_date?->toDateString() ?? now()->toDateString(),
                    'Bank charges — ' . mb_substr((string) $locked->description, 0, 120),
                    $locked->reference,
                    'bank_fee'
                );
            }

            foreach ($rows as $r) {
                DB::table('bank_statement_line_matches')->insert([
                    'bank_statement_line_id' => $locked->id,
                    'source_type' => $r['type'],
                    'source_id' => $r['id'],
                    'amount' => $r['amount'],
                    'matched_by' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $before = $locked->toArray();
            $locked->match_status = 'matched';
            $locked->matched_source_type = count($rows) === 1 ? $rows[0]['type'] : 'multiple';
            $locked->matched_source_id = count($rows) === 1 ? $rows[0]['id'] : null;
            $locked->matched_at = now();
            $locked->matched_by = $userId;
            $locked->fee_amount = $fee > 0.009 ? $fee : null;
            $locked->fee_transaction_number = $feeTxn;
            $locked->save();

            AccountingAuditService::record('bank_match.confirmed', $locked, $before, $locked->toArray(),
                count($rows) . ' transaction(s)' . ($fee > 0.009 ? ', bank fee ' . number_format($fee, 2) : ''), 0, $userId);

            return ['line' => $locked, 'fee' => max(0, $fee)];
        });
    }

    /** Undo a match: removes the links and reverses any bank fee that was posted for it. */
    public static function unmatch(BankStatementLine $line, ?int $userId = null): BankStatementLine
    {
        return DB::transaction(function () use ($line, $userId) {
            $locked = BankStatementLine::query()->where('id', $line->id)->lockForUpdate()->firstOrFail();
            if ($locked->match_status !== 'matched') {
                throw new RuntimeException('Line is not matched.');
            }
            $before = $locked->toArray();
            DB::table('bank_statement_line_matches')->where('bank_statement_line_id', $locked->id)->delete();
            AccountingService::reverseEvent('bank-fee:' . $locked->id);
            $locked->forceFill([
                'match_status' => 'unmatched', 'matched_source_type' => null, 'matched_source_id' => null,
                'matched_at' => null, 'matched_by' => null, 'fee_amount' => null, 'fee_transaction_number' => null,
            ])->save();
            AccountingAuditService::record('bank_match.unmatched', $locked, $before, $locked->toArray(), null, 0, $userId);

            return $locked;
        });
    }

    /** @return Collection<int, object> what a matched line is matched to */
    public static function matchesFor(BankStatementLine $line): Collection
    {
        $rows = \App\Support\SchemaCache::hasTable('bank_statement_line_matches')
            ? DB::table('bank_statement_line_matches')->where('bank_statement_line_id', $line->id)->get()
            : collect();
        if ($rows->isEmpty() && $line->matched_source_type && $line->matched_source_type !== 'multiple') {
            $rows = collect([(object) ['source_type' => $line->matched_source_type, 'source_id' => $line->matched_source_id, 'amount' => self::signedLine($line)]]);
        }

        return $rows->map(function ($r) {
            $r->label = SourceDocumentResolver::resolve($r->source_type, $r->source_id);

            return $r;
        });
    }

    public static function sourceAmount(string $type, int $id): float
    {
        switch ($type) {
            case 'payment':
                $p = DB::table('payments')->where('id', $id)->first();
                if (!$p) {
                    throw new RuntimeException("Customer payment #{$id} not found.");
                }

                return round((float) $p->amount, 2) * (($p->entry_type ?? null) === 'refund' ? -1 : 1);
            case 'purchase_payment':
                $p = DB::table('purchase_payments')->where('id', $id)->first();
                if (!$p) {
                    throw new RuntimeException("Supplier payment #{$id} not found.");
                }

                return -round((float) $p->amount, 2) * (($p->entry_type ?? null) === 'refund' ? -1 : 1);
            case 'journal_entry':
                $j = DB::table('journal_entries')->where('id', $id)->where('reversed', 0)->first();
                if (!$j) {
                    throw new RuntimeException("Journal line #{$id} not found.");
                }

                return round((float) $j->debit - (float) $j->credit, 2);
        }
        throw new RuntimeException('Unsupported transaction type.');
    }
}
