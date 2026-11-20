<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AccountingPeriod;
use App\Models\Order;
use App\Models\Purchase;
use App\Services\AccountingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Phase 7 — month-end close checklist (QuickBooks "Close the books" / Xero "lock dates" with review):
 * run the checks, review, sign off, then lock the period so nothing can be posted into it.
 */
class MonthEndCloseService
{
    /**
     * @return array{key: string, from: string, to: string, label: string}
     */
    public static function period(string $key): array
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $key)) {
            throw new RuntimeException('Period must look like 2026-11.');
        }
        $from = Carbon::parse($key . '-01')->startOfMonth();

        return ['key' => $key, 'from' => $from->toDateString(), 'to' => $from->copy()->endOfMonth()->toDateString(), 'label' => $from->format('F Y')];
    }

    /**
     * Every check: key, title, status (pass|warn|fail), detail, value, url.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function checks(string $key): array
    {
        $p = self::period($key);
        [$from, $to] = [$p['from'], $p['to']];
        $q = 'from=' . $from . '&to=' . $to;
        $checks = [];

        // 1. Bank reconciliation
        if (\App\Support\SchemaCache::hasTable('bank_statement_lines')) {
            $open = DB::table('bank_statement_lines as l')
                ->join('bank_statement_imports as i', 'i.id', '=', 'l.bank_statement_import_id')
                ->where('i.status', 'imported')
                ->whereIn('l.match_status', ['unmatched', 'suggested'])
                ->whereDate('l.txn_date', '<=', $to)
                ->selectRaw('COUNT(*) as n, COALESCE(SUM(l.credit - l.debit),0) as net')->first();
            $imported = DB::table('bank_statement_lines as l')->join('bank_statement_imports as i', 'i.id', '=', 'l.bank_statement_import_id')
                ->where('i.status', 'imported')->whereBetween('l.txn_date', [$from, $to])->count();
            $checks[] = [
                'key' => 'bank', 'title' => 'Bank statements reconciled',
                'status' => $imported === 0 ? 'warn' : ((int) $open->n > 0 ? 'fail' : 'pass'),
                'detail' => $imported === 0
                    ? 'No bank statement lines imported for this month.'
                    : ((int) $open->n > 0 ? $open->n . ' bank line(s) up to month end not matched (net ' . number_format((float) $open->net, 2) . ').' : 'All bank lines up to month end are matched.'),
                'url' => url('accounting/phase7/reconcile'),
            ];
        }

        // 2. Documents not finished
        $draftBills = \App\Support\SchemaCache::hasTable('purchases') ? DB::table('purchases')->where('status', 'draft')->whereBetween('date', [$from, $to])->count() : 0;
        $notInvoiced = \App\Support\SchemaCache::hasTable('orders') ? DB::table('orders')->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_AFTERCHECKING, Order::STATUS_PROCESSING])
            ->whereBetween(DB::raw('DATE(COALESCE(order_date, created_at))'), [$from, $to])->count() : 0;
        $draftJournals = \App\Support\SchemaCache::hasTable('accounting_manual_journals') ? DB::table('accounting_manual_journals')->whereIn('status', ['draft', 'submitted', 'approved'])->whereBetween('journal_date', [$from, $to])->count() : 0;
        $pending = \App\Support\SchemaCache::hasTable('accounting_postings') ? DB::table('accounting_postings')->where('transaction_number', 'PENDING')->where('reversed', false)->count() : 0;
        $failedRec = \App\Support\SchemaCache::hasTable('accounting_recurring_runs') ? DB::table('accounting_recurring_runs')->where('status', 'failed')->whereBetween('occurrence_date', [$from, $to])->count() : 0;
        $parts = array_filter([
            $draftBills ? "{$draftBills} draft bill(s)" : null,
            $draftJournals ? "{$draftJournals} manual journal(s) not posted" : null,
            $pending ? "{$pending} posting(s) stuck as PENDING" : null,
            $failedRec ? "{$failedRec} recurring run(s) failed" : null,
        ]);
        $checks[] = [
            'key' => 'documents', 'title' => 'No unposted documents',
            'status' => $parts ? 'fail' : ($notInvoiced ? 'warn' : 'pass'),
            'detail' => $parts ? implode(' · ', $parts) . '.' : ($notInvoiced ? "{$notInvoiced} order(s) from this month not invoiced yet (no revenue posted)." : 'Every bill, journal and recurring document for the month is posted.'),
            'url' => $draftBills ? url('accounting/phase6/bills?status=draft') : ($draftJournals ? url('accounting/manual-journals?status=draft') : ($failedRec ? url('accounting/phase7/recurring-queue') : url('accounting/phase6/invoices?status=draft'))),
        ];

        // 3. Receivables sub-ledger vs GL (invoices of the month)
        [$arBad, $arDiff] = self::subledgerMismatches('ar', $from, $to);
        $checks[] = [
            'key' => 'ar', 'title' => 'Receivables agree with the ledger',
            'status' => $arBad ? 'fail' : 'pass',
            'detail' => $arBad ? "{$arBad} invoice(s) where AR in the ledger ≠ balance due (total difference " . number_format($arDiff, 2) . ').' : 'Every invoice of the month agrees with Accounts Receivable.',
            'url' => url('accounting/phase6/invoices?' . $q),
        ];

        // 4. Payables sub-ledger vs GL (bills of the month)
        [$apBad, $apDiff] = self::subledgerMismatches('ap', $from, $to);
        $checks[] = [
            'key' => 'ap', 'title' => 'Payables agree with the ledger',
            'status' => $apBad ? 'fail' : 'pass',
            'detail' => $apBad ? "{$apBad} bill(s) where AP in the ledger ≠ balance (total difference " . number_format($apDiff, 2) . ').' : 'Every bill of the month agrees with Accounts Payable.',
            'url' => url('accounting/phase6/bills'),
        ];

        // 5. VAT / SSCL return agrees with the tax accounts
        $vat = VatReturnService::build($from, $to);
        $diffs = [];
        foreach (['vat_output' => ['Output VAT', $vat['output_vat']], 'vat_input' => ['Input VAT', $vat['input_vat']], 'sscl' => ['SSCL', $vat['sscl_payable']]] as $k => [$label, $docs]) {
            $ledger = $vat['ledger'][$k];
            if ($ledger !== null && abs($ledger - $docs) > 0.009) {
                $diffs[] = $label . ' ' . number_format($ledger - $docs, 2);
            }
        }
        $checks[] = [
            'key' => 'vat', 'title' => 'VAT & SSCL return agrees with the ledger',
            'status' => $diffs ? 'fail' : 'pass',
            'detail' => $diffs ? 'Differences: ' . implode(' · ', $diffs) . '.' : 'Net VAT ' . number_format($vat['net_vat'], 2) . ' · SSCL ' . number_format($vat['sscl_payable'], 2) . ' — matches the tax accounts.',
            'url' => url('accounting/phase6/vat-return?' . $q),
        ];

        // 6. Trial balance balances
        $tb = DB::table('journal_entries')->where('reversed', 0)->whereDate('date', '<=', $to)
            ->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')->first();
        $tbDiff = round((float) $tb->d - (float) $tb->c, 2);
        $checks[] = [
            'key' => 'tb', 'title' => 'Trial balance balances',
            'status' => abs($tbDiff) > 0.009 ? 'fail' : 'pass',
            'detail' => abs($tbDiff) > 0.009 ? 'Debits and credits differ by ' . number_format($tbDiff, 2) . '.' : 'Debits = credits (' . number_format((float) $tb->d, 2) . ').',
            'url' => url('accounting/phase4/statements/trial-balance?from=' . substr($to, 0, 4) . '-01-01&to=' . $to),
        ];

        return $checks;
    }

    /** @return array{0: int, 1: float} count of mismatching documents and total difference */
    private static function subledgerMismatches(string $side, string $from, string $to): array
    {
        $bad = 0;
        $total = 0.0;
        if ($side === 'ar') {
            $arId = AccountingService::accountId(AccountingService::GL_ACCOUNTS_RECV);
            if (!$arId || !\App\Support\SchemaCache::hasTable('orders')) {
                return [0, 0.0];
            }
            $orders = Order::query()->with(['payments', 'saleReturns'])
                ->whereIn('status', [Order::STATUS_COMPLETED, Order::STATUS_PAID, Order::STATUS_CREDIT])
                ->whereNotNull('invoice_number')
                ->whereBetween(DB::raw('DATE(COALESCE(invoice_date, order_date, created_at))'), [$from, $to])
                ->get();
            foreach ($orders as $o) {
                $ledger = self::ledgerFor($arId, array_filter([
                    Order::class => [$o->id],
                    \App\Models\Payment::class => $o->payments->filter(fn ($p) => !$p->isCheque())->pluck('id')->all(),
                    \App\Models\SaleReturn::class => $o->saleReturns->pluck('id')->all(),
                    \App\Models\CreditNote::class => \App\Support\SchemaCache::hasTable('credit_notes') ? DB::table('credit_notes')->where('order_id', $o->id)->pluck('id')->all() : [],
                ]));
                // Cheques and return claims are settled outside these postings — exclude them from the comparison.
                $chequePaid = (float) $o->payments->filter(fn ($p) => $p->isCheque() && $p->countsTowardPaid() && !$p->isRefund())->sum(fn ($p) => $p->effectiveSettlementAmount());
                $diff = round($ledger - ((float) $o->due_amount + $chequePaid + (float) $o->claim_credit_amount), 2);
                if (abs($diff) > 0.009) {
                    $bad++;
                    $total = round($total + $diff, 2);
                }
            }

            return [$bad, $total];
        }

        $apId = AccountingService::accountId(AccountingService::GL_ACCOUNTS_PAY);
        if (!$apId || !\App\Support\SchemaCache::hasTable('purchases')) {
            return [0, 0.0];
        }
        $bills = Purchase::query()->with(['items', 'payments', 'returns'])->where('status', Purchase::STATUS_CONFIRMED)->whereBetween('date', [$from, $to])->get();
        foreach ($bills as $b) {
            $ledger = -self::ledgerFor($apId, array_filter([
                Purchase::class => [$b->id],
                'purchase' => [$b->id],
                \App\Models\PurchasePayment::class => $b->payments->pluck('id')->all(),
                \App\Models\PurchaseReturn::class => $b->returns->pluck('id')->all(),
            ]));
            $diff = round($ledger - (float) $b->balance, 2);
            if (abs($diff) > 0.009) {
                $bad++;
                $total = round($total + $diff, 2);
            }
        }

        return [$bad, $total];
    }

    private static function ledgerFor(int $accountId, array $sources): float
    {
        $q = DB::table('journal_entries')->where('chart_of_account_id', $accountId)->where('reversed', 0)
            ->where(function ($w) use ($sources) {
                foreach ($sources as $type => $ids) {
                    $w->orWhere(fn ($x) => $x->where('source_type', $type)->whereIn('source_id', $ids));
                }
            });

        return round((float) $q->selectRaw('COALESCE(SUM(debit),0) - COALESCE(SUM(credit),0) as b')->value('b'), 2);
    }

    // ------------------------------------------------------------------ review & lock

    public static function review(string $key): object
    {
        $row = DB::table('acct_period_close_reviews')->where('period_key', $key)->first();

        return $row ?: (object) ['period_key' => $key, 'status' => 'in_review', 'notes' => null, 'checks' => null, 'signed_off_by' => null, 'signed_off_at' => null, 'locked_at' => null, 'locked_by' => null];
    }

    public static function periodRow(string $key): ?AccountingPeriod
    {
        $p = self::period($key);

        return AccountingPeriod::query()->where('business_id', 0)->whereDate('starts_on', $p['from'])->whereDate('ends_on', $p['to'])->first();
    }

    public static function signOff(string $key, ?string $notes, ?int $userId, bool $acceptExceptions): object
    {
        $checks = self::checks($key);
        $failed = array_filter($checks, fn ($c) => $c['status'] === 'fail');
        if ($failed && !$acceptExceptions) {
            throw new RuntimeException(count($failed) . ' check(s) failed. Fix them, or tick "I reviewed the exceptions" and explain in the notes.');
        }
        if ($failed && blank($notes)) {
            throw new RuntimeException('Explain the accepted exceptions in the notes.');
        }
        DB::table('acct_period_close_reviews')->updateOrInsert(['period_key' => $key], [
            'checks' => json_encode($checks),
            'status' => 'signed_off',
            'notes' => $notes,
            'signed_off_by' => $userId,
            'signed_off_at' => now(),
            'updated_at' => now(),
            'created_at' => now(),
        ]);

        return self::review($key);
    }

    public static function lock(string $key, ?int $userId): AccountingPeriod
    {
        $review = self::review($key);
        if (!in_array($review->status, ['signed_off'], true)) {
            throw new RuntimeException('Sign off the checklist before locking the period.');
        }
        $p = self::period($key);
        $period = self::periodRow($key) ?? AccountingPeriod::query()->create([
            'business_id' => 0, 'name' => $p['label'], 'starts_on' => $p['from'], 'ends_on' => $p['to'], 'status' => 'open',
        ]);
        AccountingPeriodService::close($period, $userId);
        DB::table('acct_period_close_reviews')->where('period_key', $key)->update(['status' => 'locked', 'locked_by' => $userId, 'locked_at' => now(), 'updated_at' => now()]);

        return $period->fresh();
    }

    public static function reopen(string $key, string $reason, ?int $userId): void
    {
        $period = self::periodRow($key);
        if ($period && $period->isClosed()) {
            AccountingPeriodService::reopen($period, $userId, $reason);
        }
        $notes = (string) DB::table('acct_period_close_reviews')->where('period_key', $key)->value('notes');
        DB::table('acct_period_close_reviews')->where('period_key', $key)->update([
            'status' => 'reopened',
            'notes' => trim($notes . "\n[Reopened " . now()->format('Y-m-d') . '] ' . $reason),
            'locked_at' => null, 'locked_by' => null, 'updated_at' => now(),
        ]);
    }
}
