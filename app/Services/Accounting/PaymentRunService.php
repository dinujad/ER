<?php

namespace App\Services\Accounting;

use App\Models\AuditLog;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Observers\PurchasePaymentObserver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Phase 7 — supplier payment runs (Xero "Batch payments" / QuickBooks "Pay bills"):
 * select bills → draft run → approve → export bank file → confirm what the bank paid / mark failures.
 * Payments are created only on confirmation, once per bill (no duplicate postings).
 */
class PaymentRunService
{
    public const ACTIVE = ['draft', 'approved', 'exported'];

    public const FORMATS = [
        'generic_csv' => 'Generic CSV (any bank — upload / manual entry)',
        'slips_csv' => 'LankaPay SLIPS / bulk-transfer CSV (bank code, branch code, account, amount)',
    ];

    /**
     * @param  array<int, int>  $purchaseIds
     * @param  array<int, float>  $amounts  optional per-bill partial amounts (bill id => amount)
     */
    public function create(array $purchaseIds, string $paymentDate, ?int $bankAccountId, ?string $notes, ?int $userId, array $amounts = []): object
    {
        $purchaseIds = array_values(array_unique(array_map('intval', $purchaseIds)));
        if ($purchaseIds === []) {
            throw new RuntimeException('Select at least one bill.');
        }

        return DB::transaction(function () use ($purchaseIds, $paymentDate, $bankAccountId, $notes, $userId, $amounts) {
            $bills = Purchase::query()->with(['supplier', 'items', 'payments', 'returns'])->whereIn('id', $purchaseIds)->lockForUpdate()->get();
            $inRun = $this->billsInActiveRuns($purchaseIds);
            $items = [];
            foreach ($bills as $bill) {
                if ($bill->status !== Purchase::STATUS_CONFIRMED) {
                    throw new RuntimeException("Bill {$bill->purchase_no} is not confirmed.");
                }
                if (isset($inRun[$bill->id])) {
                    throw new RuntimeException("Bill {$bill->purchase_no} is already in payment run {$inRun[$bill->id]}.");
                }
                if (config('dermas.require_bill_approval') && Purchase::hasPayablesColumns() && !$bill->approved_at) {
                    throw new RuntimeException("Bill {$bill->purchase_no} is not approved for payment.");
                }
                $balance = round((float) $bill->balance, 2);
                if ($balance <= 0.009) {
                    throw new RuntimeException("Bill {$bill->purchase_no} has nothing to pay.");
                }
                $amount = isset($amounts[$bill->id]) && (float) $amounts[$bill->id] > 0 ? round(min((float) $amounts[$bill->id], $balance), 2) : $balance;
                $items[] = ['purchase_id' => $bill->id, 'supplier_id' => $bill->supplier_id, 'amount' => $amount];
            }

            $id = DB::table('acct_payment_runs')->insertGetId([
                'run_no' => $this->nextRunNo(),
                'bank_account_id' => $bankAccountId,
                'payment_date' => $paymentDate,
                'status' => 'draft',
                'total' => round(array_sum(array_column($items, 'amount')), 2),
                'notes' => $notes,
                'created_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            foreach ($items as $i) {
                DB::table('acct_payment_run_items')->insert($i + ['payment_run_id' => $id, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
            }
            $run = $this->find($id);
            $this->audit($run, 'created', ['bills' => count($items), 'total' => $run->total]);

            return $run;
        });
    }

    public function approve(int $runId, ?int $userId): object
    {
        $run = $this->find($runId);
        if ($run->status !== 'draft') {
            throw new RuntimeException('Only draft payment runs can be approved.');
        }
        if ((int) $run->created_by === (int) $userId && config('dermas.payment_run_four_eyes')) {
            throw new RuntimeException('The person who prepared the run cannot approve it (four-eyes rule).');
        }
        DB::table('acct_payment_runs')->where('id', $runId)->update(['status' => 'approved', 'approved_by' => $userId, 'approved_at' => now(), 'updated_at' => now()]);
        $this->audit($run, 'approved', []);

        return $this->find($runId);
    }

    /** @return array{filename: string, content: string} */
    public function export(int $runId, string $format): array
    {
        $run = $this->find($runId);
        if (!in_array($run->status, ['approved', 'exported'], true)) {
            throw new RuntimeException('Approve the payment run before exporting the bank file.');
        }
        if (!isset(self::FORMATS[$format])) {
            throw new RuntimeException('Unknown bank file format.');
        }
        $items = $this->items($runId)->where('status', 'pending');
        $missing = $items->filter(fn ($i) => blank($i->bank_account_no))->pluck('supplier_name')->unique()->values();
        if ($missing->isNotEmpty()) {
            throw new RuntimeException('Bank account missing for: ' . $missing->implode(', ') . '. Add it on the supplier first.');
        }

        if ($format === 'slips_csv') {
            $noCodes = $items->filter(fn ($i) => blank($i->bank_code) || blank($i->branch_code))->pluck('supplier_name')->unique()->values();
            if ($noCodes->isNotEmpty()) {
                throw new RuntimeException('SLIPS needs the bank code and branch code for: ' . $noCodes->implode(', ') . '. Add them on the supplier, or download the generic CSV.');
            }
        }

        $out = fopen('php://temp', 'r+');
        if ($format === 'slips_csv') {
            fputcsv($out, ['Bank Code', 'Branch Code', 'Account No', 'Account Name', 'Amount', 'Reference', 'Particulars']);
            foreach ($items as $i) {
                fputcsv($out, [$i->bank_code, $i->branch_code, $i->bank_account_no, mb_substr((string) ($i->bank_account_name ?: $i->supplier_name), 0, 20),
                    number_format((float) $i->amount, 2, '.', ''), mb_substr($run->run_no, 0, 15), mb_substr((string) $i->purchase_no, 0, 15)]);
            }
        } else {
            fputcsv($out, ['Payment date', 'Beneficiary', 'Account name', 'Account no', 'Bank', 'Bank code', 'Branch code', 'Amount', 'Reference', 'Bill', 'Supplier ref']);
            foreach ($items as $i) {
                fputcsv($out, [$run->payment_date, $i->supplier_name, $i->bank_account_name, $i->bank_account_no, $i->bank_name, $i->bank_code, $i->branch_code,
                    number_format((float) $i->amount, 2, '.', ''), $run->run_no, $i->purchase_no, $i->reference_no]);
            }
        }
        rewind($out);
        $content = stream_get_contents($out);
        fclose($out);

        DB::table('acct_payment_runs')->where('id', $runId)->update(['status' => 'exported', 'exported_at' => now(), 'export_format' => $format, 'updated_at' => now()]);
        $this->audit($run, 'exported', ['format' => $format, 'lines' => $items->count()]);

        return ['filename' => $run->run_no . '-' . $format . '.csv', 'content' => $content];
    }

    public function markFailed(int $runId, int $itemId, string $reason): void
    {
        $run = $this->find($runId);
        if (!in_array($run->status, self::ACTIVE, true)) {
            throw new RuntimeException('This payment run is closed.');
        }
        $n = DB::table('acct_payment_run_items')->where('id', $itemId)->where('payment_run_id', $runId)->where('status', 'pending')
            ->update(['status' => 'failed', 'failure_reason' => mb_substr($reason, 0, 255), 'updated_at' => now()]);
        if (!$n) {
            throw new RuntimeException('Only unpaid lines can be marked as failed.');
        }
        $this->refreshTotal($runId);
        $this->audit($run, 'item_failed', ['item' => $itemId], $reason);
    }

    /**
     * Bank confirmed the transfer: create one supplier payment per pending bill (capped at what is still owed).
     * Re-running is safe — paid lines are skipped, and a bill already settled elsewhere is marked failed instead of paid twice.
     *
     * @return array{paid: int, skipped: array<int, string>}
     */
    public function confirmPaid(int $runId, ?string $paidOn, ?int $userId): array
    {
        $run = $this->find($runId);
        if (!in_array($run->status, ['approved', 'exported'], true)) {
            throw new RuntimeException('Approve the payment run before confirming payments.');
        }
        $date = $paidOn ?: (string) $run->payment_date;
        $paid = 0;
        $skipped = [];

        DB::transaction(function () use ($runId, $run, $date, $userId, &$paid, &$skipped) {
            $items = DB::table('acct_payment_run_items')->where('payment_run_id', $runId)->where('status', 'pending')->lockForUpdate()->get();
            foreach ($items as $item) {
                if ($item->purchase_payment_id) {
                    continue;
                }
                $bill = Purchase::query()->with(['items', 'payments', 'returns'])->lockForUpdate()->find($item->purchase_id);
                $balance = $bill ? round((float) $bill->balance, 2) : 0.0;
                $amount = round(min((float) $item->amount, $balance), 2);
                if (!$bill || $amount <= 0.009) {
                    DB::table('acct_payment_run_items')->where('id', $item->id)->update(['status' => 'failed', 'failure_reason' => 'Already paid elsewhere — not paid again', 'updated_at' => now()]);
                    $skipped[] = ($bill->purchase_no ?? '#' . $item->purchase_id) . ': already paid';
                    continue;
                }
                $payment = PurchasePayment::query()->create([
                    'supplier_id' => $bill->supplier_id,
                    'purchase_id' => $bill->id,
                    'entry_type' => PurchasePayment::ENTRY_PAYMENT,
                    'amount' => $amount,
                    'payment_method' => 'bank_transfer',
                    'payment_date' => $date,
                    'reference' => $run->run_no,
                    'notes' => 'Payment run ' . $run->run_no,
                    'created_by' => $userId,
                ]);
                DB::table('acct_payment_run_items')->where('id', $item->id)->update([
                    'status' => 'paid', 'amount' => $amount, 'purchase_payment_id' => $payment->id, 'updated_at' => now(),
                ]);
                // post now (the observer also defers a sync; posting is idempotent by event key)
                try {
                    app(PurchasePaymentObserver::class)->sync((int) $payment->id);
                } catch (\Throwable) {
                }
                $paid++;
            }

            $this->refreshTotal($runId);
            $pending = DB::table('acct_payment_run_items')->where('payment_run_id', $runId)->where('status', 'pending')->count();
            if ($pending === 0) {
                DB::table('acct_payment_runs')->where('id', $runId)->update(['status' => 'completed', 'completed_at' => now(), 'updated_at' => now()]);
            }
        });
        $this->audit($run, 'confirmed_paid', ['paid' => $paid, 'skipped' => count($skipped)]);

        return ['paid' => $paid, 'skipped' => $skipped];
    }

    public function cancel(int $runId): void
    {
        $run = $this->find($runId);
        if (!in_array($run->status, self::ACTIVE, true)) {
            throw new RuntimeException('This payment run is already closed.');
        }
        if (DB::table('acct_payment_run_items')->where('payment_run_id', $runId)->where('status', 'paid')->exists()) {
            throw new RuntimeException('Some bills in this run are already paid — mark the rest as failed instead.');
        }
        DB::table('acct_payment_runs')->where('id', $runId)->update(['status' => 'cancelled', 'updated_at' => now()]);
        $this->audit($run, 'cancelled', []);
    }

    // ------------------------------------------------------------------ queries

    public function find(int $id): object
    {
        $run = DB::table('acct_payment_runs')->where('id', $id)->first();
        if (!$run) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException('Payment run not found.'));
        }

        return $run;
    }

    public function items(int $runId): Collection
    {
        return DB::table('acct_payment_run_items as i')
            ->leftJoin('purchases as p', 'p.id', '=', 'i.purchase_id')
            ->leftJoin('suppliers as s', 's.id', '=', 'i.supplier_id')
            ->where('i.payment_run_id', $runId)
            ->orderBy('s.name')->orderBy('p.purchase_no')
            ->select('i.*', 'p.purchase_no', 'p.reference_no', 'p.date as bill_date', 'p.due_date',
                DB::raw('COALESCE(s.company_name, s.name) as supplier_name'),
                's.bank_account_name', 's.bank_account_no', 's.bank_name', 's.bank_code', 's.branch_code')
            ->get();
    }

    /** @return array<int, string> purchase id => run no */
    public function billsInActiveRuns(?array $purchaseIds = null): array
    {
        return DB::table('acct_payment_run_items as i')
            ->join('acct_payment_runs as r', 'r.id', '=', 'i.payment_run_id')
            ->whereIn('r.status', self::ACTIVE)
            ->where('i.status', 'pending')
            ->when($purchaseIds, fn ($q, $ids) => $q->whereIn('i.purchase_id', $ids))
            ->pluck('r.run_no', 'i.purchase_id')
            ->all();
    }

    private function refreshTotal(int $runId): void
    {
        $total = (float) DB::table('acct_payment_run_items')->where('payment_run_id', $runId)->whereIn('status', ['pending', 'paid'])->sum('amount');
        DB::table('acct_payment_runs')->where('id', $runId)->update(['total' => round($total, 2), 'updated_at' => now()]);
    }

    private function nextRunNo(): string
    {
        $prefix = 'PR-' . now()->format('Ym') . '-';
        $last = DB::table('acct_payment_runs')->where('run_no', 'like', $prefix . '%')->orderByDesc('id')->value('run_no');
        $n = $last && preg_match('/(\d+)$/', $last, $m) ? (int) $m[1] + 1 : 1;

        return $prefix . str_pad((string) $n, 3, '0', STR_PAD_LEFT);
    }

    private function audit(object $run, string $action, array $values, ?string $note = null): void
    {
        if (!\App\Support\SchemaCache::hasTable('audit_logs')) {
            return;
        }
        $user = auth()->user();
        AuditLog::query()->create([
            'user_id' => $user?->getAuthIdentifier(),
            'user_name' => $user->name ?? 'System',
            'event' => 'payment_run_' . $action,
            'auditable_type' => 'payment_run',
            'auditable_id' => $run->id,
            'document_label' => 'Payment run ' . $run->run_no,
            'new_values' => $values ?: null,
            'note' => $note,
            'created_at' => now(),
        ]);
    }
}
