<?php

namespace App\Services\Accounting;

use App\Models\Accounting\BankStatementLine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class BankMatchingService
{
    /**
     * Suggest ERP matches for a statement line. Never auto-finalizes.
     *
     * @return array<int, array{source_type:string, source_id:int, amount:string, date:?string, reference:?string, description:string, confidence:int, reason:string}>
     */
    public static function suggestions(BankStatementLine $line, int $chartOfAccountId, int $limit = 10): array
    {
        $amount = self::lineAmount($line);
        if ($amount <= 0) {
            return [];
        }

        $date = $line->txn_date?->format('Y-m-d');
        $ref = strtolower(trim((string) $line->reference));
        $desc = strtolower(trim((string) $line->description));
        $isInflow = (float) $line->credit > 0 && (float) $line->debit == 0.0;

        $candidates = collect();

        if ($isInflow && Schema::hasTable('payments')) {
            $q = DB::table('payments')
                ->leftJoin('orders', 'orders.id', '=', 'payments.order_id')
                ->select([
                    'payments.id',
                    'payments.payment_date',
                    'payments.reference_number',
                    'payments.notes',
                    'payments.payment_method',
                    'payments.amount',
                    'payments.settlement_amount',
                    'orders.customer_id',
                ])
                ->limit(200);

            if ($date) {
                $q->whereBetween('payments.payment_date', [
                    date('Y-m-d', strtotime($date.' -7 days')),
                    date('Y-m-d', strtotime($date.' +7 days')),
                ]);
            }

            foreach ($q->get() as $row) {
                $method = strtolower(trim((string) ($row->payment_method ?? '')));
                if ($method !== '' && ! in_array($method, ['bank', 'bank_transfer', 'card', 'online', 'mobile', 'cheque'], true)) {
                    continue;
                }
                $amt = round((float) (($row->settlement_amount !== null && (float) $row->settlement_amount > 0)
                    ? $row->settlement_amount
                    : $row->amount), 2);
                if (abs($amt - $amount) > 0.009) {
                    continue;
                }
                if (self::isSourceAlreadyMatched('payment', (int) $row->id)) {
                    continue;
                }
                $candidates->push(self::scoreCandidate(
                    'payment',
                    (int) $row->id,
                    $amt,
                    $row->payment_date ? (string) $row->payment_date : null,
                    $row->reference_number,
                    'Customer payment #'.$row->id.' ('.($row->payment_method ?? '').')',
                    $amount,
                    $date,
                    $ref,
                    $desc,
                    strtolower((string) ($row->reference_number ?? '').' '.($row->notes ?? ''))
                ));
            }
        }

        if (! $isInflow && Schema::hasTable('purchase_payments')) {
            $q = DB::table('purchase_payments')
                ->select(['id', 'payment_date', 'reference', 'notes', 'payment_method', 'amount', 'supplier_id'])
                ->limit(200);

            if ($date) {
                $q->whereBetween('payment_date', [
                    date('Y-m-d', strtotime($date.' -7 days')),
                    date('Y-m-d', strtotime($date.' +7 days')),
                ]);
            }

            foreach ($q->get() as $row) {
                $amt = round((float) $row->amount, 2);
                if (abs($amt - $amount) > 0.009) {
                    continue;
                }
                if (self::isSourceAlreadyMatched('purchase_payment', (int) $row->id)) {
                    continue;
                }
                $candidates->push(self::scoreCandidate(
                    'purchase_payment',
                    (int) $row->id,
                    $amt,
                    $row->payment_date ? (string) $row->payment_date : null,
                    $row->reference,
                    'Supplier payment #'.$row->id.' ('.($row->payment_method ?? '').')',
                    $amount,
                    $date,
                    $ref,
                    $desc,
                    strtolower((string) ($row->reference ?? '').' '.($row->notes ?? ''))
                ));
            }
        }

        // Journal transfers / bank lines on same GL
        if (Schema::hasTable('journal_entries')) {
            $side = $isInflow ? 'debit' : 'credit';
            $jq = DB::table('journal_entries')
                ->where('chart_of_account_id', $chartOfAccountId)
                ->where('reversed', 0)
                ->where('active', 1)
                ->whereRaw("ROUND({$side}, 2) = ?", [$amount])
                ->select(['id', 'date', 'transaction_number', 'reference', 'name', 'debit', 'credit'])
                ->limit(40);

            if ($date) {
                $jq->whereBetween('date', [
                    date('Y-m-d', strtotime($date.' -7 days')),
                    date('Y-m-d', strtotime($date.' +7 days')),
                ]);
            }

            foreach ($jq->get() as $row) {
                if (self::isSourceAlreadyMatched('journal_entry', (int) $row->id)) {
                    continue;
                }
                $candidates->push(self::scoreCandidate(
                    'journal_entry',
                    (int) $row->id,
                    $isInflow ? (float) $row->debit : (float) $row->credit,
                    $row->date ? (string) $row->date : null,
                    $row->reference,
                    'Journal '.$row->transaction_number.' — '.$row->name,
                    $amount,
                    $date,
                    $ref,
                    $desc,
                    strtolower((string) ($row->reference ?? '').' '.($row->name ?? '').' '.($row->transaction_number ?? ''))
                ));
            }
        }

        return $candidates
            ->sortByDesc('confidence')
            ->take($limit)
            ->values()
            ->all();
    }

    public static function confirmMatch(
        BankStatementLine $line,
        string $sourceType,
        int $sourceId,
        ?int $userId = null
    ): BankStatementLine {
        $allowed = ['payment', 'purchase_payment', 'journal_entry'];
        if (! in_array($sourceType, $allowed, true)) {
            throw new RuntimeException('Unsupported match source type.');
        }
        if (! in_array($line->match_status, ['unmatched', 'suggested'], true) && $line->match_status !== 'excluded') {
            if ($line->match_status === 'matched') {
                throw new RuntimeException('Statement line already matched.');
            }
        }

        return DB::transaction(function () use ($line, $sourceType, $sourceId, $userId) {
            $locked = BankStatementLine::query()->where('id', $line->id)->lockForUpdate()->firstOrFail();

            if ($locked->match_status === 'matched') {
                throw new RuntimeException('Statement line already matched.');
            }

            if (self::isSourceAlreadyMatched($sourceType, $sourceId, $locked->id)) {
                throw new RuntimeException('ERP transaction is already matched to another statement line.');
            }

            $before = $locked->toArray();
            $locked->match_status = 'matched';
            $locked->matched_source_type = $sourceType;
            $locked->matched_source_id = $sourceId;
            $locked->matched_at = now();
            $locked->matched_by = $userId;
            $locked->excluded_reason = null;
            $locked->save();

            AccountingAuditService::record(
                'bank_match.confirmed',
                $locked,
                $before,
                $locked->toArray(),
                null,
                (int) ($locked->import?->business_id ?? 0),
                $userId
            );

            return $locked;
        });
    }

    public static function unmatch(BankStatementLine $line, ?int $userId = null): BankStatementLine
    {
        return DB::transaction(function () use ($line, $userId) {
            $locked = BankStatementLine::query()->where('id', $line->id)->lockForUpdate()->firstOrFail();
            if ($locked->match_status !== 'matched') {
                throw new RuntimeException('Line is not matched.');
            }
            $before = $locked->toArray();
            $locked->match_status = 'unmatched';
            $locked->matched_source_type = null;
            $locked->matched_source_id = null;
            $locked->matched_at = null;
            $locked->matched_by = null;
            $locked->save();

            AccountingAuditService::record(
                'bank_match.unmatched',
                $locked,
                $before,
                $locked->toArray(),
                null,
                0,
                $userId
            );

            return $locked;
        });
    }

    public static function exclude(BankStatementLine $line, ?string $reason = null, ?int $userId = null): BankStatementLine
    {
        return DB::transaction(function () use ($line, $reason, $userId) {
            $locked = BankStatementLine::query()->where('id', $line->id)->lockForUpdate()->firstOrFail();
            if ($locked->match_status === 'matched') {
                throw new RuntimeException('Unmatch before excluding.');
            }
            $locked->match_status = 'excluded';
            $locked->excluded_reason = $reason;
            $locked->save();

            return $locked;
        });
    }

    public static function lineAmount(BankStatementLine $line): float
    {
        $debit = round((float) $line->debit, 2);
        $credit = round((float) $line->credit, 2);
        if ($debit > 0) {
            return $debit;
        }
        if ($credit > 0) {
            return $credit;
        }

        return round(abs((float) $line->amount), 2);
    }

    protected static function isSourceAlreadyMatched(string $type, int $id, ?int $exceptLineId = null): bool
    {
        $q = BankStatementLine::query()
            ->where('match_status', 'matched')
            ->where('matched_source_type', $type)
            ->where('matched_source_id', $id);

        if ($exceptLineId) {
            $q->where('id', '!=', $exceptLineId);
        }

        if ($q->exists()) {
            return true;
        }

        // Phase 7 — one bank line matched to several transactions.
        return \Illuminate\Support\Facades\Schema::hasTable('bank_statement_line_matches')
            && DB::table('bank_statement_line_matches')->where('source_type', $type)->where('source_id', $id)->exists();
    }

    /**
     * @return array{source_type:string, source_id:int, amount:string, date:?string, reference:?string, description:string, confidence:int, reason:string}
     */
    protected static function scoreCandidate(
        string $type,
        int $id,
        float $candAmount,
        ?string $candDate,
        ?string $candRef,
        string $description,
        float $stmtAmount,
        ?string $stmtDate,
        string $stmtRef,
        string $stmtDesc,
        string $haystack
    ): array {
        $confidence = 40;
        $reasons = ['amount'];

        if (abs($candAmount - $stmtAmount) < 0.009) {
            $confidence += 35;
        }

        if ($stmtDate && $candDate) {
            $days = abs((strtotime($stmtDate) - strtotime($candDate)) / 86400);
            if ($days <= 1) {
                $confidence += 15;
                $reasons[] = 'date±1';
            } elseif ($days <= 7) {
                $confidence += 8;
                $reasons[] = 'date±7';
            }
        }

        $candRefL = strtolower(trim((string) $candRef));
        if ($stmtRef !== '' && $candRefL !== '' && ($stmtRef === $candRefL || str_contains($haystack, $stmtRef))) {
            $confidence += 15;
            $reasons[] = 'reference';
        }

        if ($stmtDesc !== '' && (str_contains($haystack, $stmtDesc) || similar_text($stmtDesc, $haystack) > 40)) {
            $confidence += 5;
            $reasons[] = 'description';
        }

        $confidence = min(99, $confidence);

        return [
            'source_type' => $type,
            'source_id' => $id,
            'amount' => number_format($candAmount, 2, '.', ''),
            'date' => $candDate,
            'reference' => $candRef,
            'description' => $description,
            'confidence' => $confidence,
            'reason' => implode(', ', $reasons),
        ];
    }
}
