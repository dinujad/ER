<?php

namespace Modules\Accounting\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchasePayment;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use App\Services\Accounting\DocumentJournalService;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;

/**
 * Phase 6.5 — Xero-style bills (purchases): list with statuses, drill-down, approval, payment run.
 */
class BillController extends Controller
{
    use RequiresAccountingPermission;

    public function index(Request $request)
    {
        $f = $request->validate([
            'status' => 'nullable|in:all,draft,unpaid,overdue,paid,cancelled,awaiting_approval',
            'supplier_id' => 'nullable|integer',
            'q' => 'nullable|string|max:80',
        ]);
        $status = $f['status'] ?? 'all';
        $hasPayables = Purchase::hasPayablesColumns();

        $bills = Purchase::query()
            ->with(['supplier', 'items', 'payments', 'returns'])
            ->when($f['supplier_id'] ?? null, fn ($q, $v) => $q->where('supplier_id', $v))
            ->when($f['q'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w->where('purchase_no', 'like', "%{$v}%")->orWhere('reference_no', 'like', "%{$v}%")))
            ->when($status === 'draft', fn ($q) => $q->where('status', Purchase::STATUS_DRAFT))
            ->when($status === 'cancelled', fn ($q) => $q->where('status', Purchase::STATUS_CANCELLED))
            ->when(in_array($status, ['unpaid', 'overdue', 'paid', 'awaiting_approval'], true), fn ($q) => $q->where('status', Purchase::STATUS_CONFIRMED))
            ->when($status === 'overdue' && $hasPayables, fn ($q) => $q->whereDate('due_date', '<', now()->toDateString()))
            ->when($status === 'awaiting_approval' && $hasPayables, fn ($q) => $q->whereNull('approved_at'))
            ->orderByDesc('date')->orderByDesc('id');
        $suppliers = Supplier::query()->orderBy('name')->get(['id', 'name', 'company_name']);

        if (in_array($status, ['all', 'draft', 'cancelled'], true)) {
            $summaryQuery = (clone $bills)->reorder();
            $summary = [
                'count' => (clone $summaryQuery)->count(),
                'total' => round((float) (clone $summaryQuery)->sum('total'), 2),
                'balance' => null,
            ];

            return view('accounting::phase6.bills.index', [
                'bills' => $bills->paginate(30)->withQueryString(), 'suppliers' => $suppliers, 'f' => $f, 'status' => $status, 'summary' => $summary,
            ]);
        }

        // Balance depends on payments, returns and refunds — filter paid / unpaid after loading.
        $bills = $bills->get();
        $bills = ($status === 'paid'
            ? $bills->filter(fn (Purchase $p) => (float) $p->balance <= 0.009)
            : $bills->filter(fn (Purchase $p) => (float) $p->balance > 0.009))->values();

        $summary = [
            'count' => $bills->count(),
            'total' => round((float) $bills->sum(fn ($p) => (float) $p->total), 2),
            'balance' => round((float) $bills->where('status', Purchase::STATUS_CONFIRMED)->sum(fn ($p) => max(0, (float) $p->balance)), 2),
        ];
        $page = max(1, (int) $request->get('page', 1));
        $paged = new \Illuminate\Pagination\LengthAwarePaginator($bills->forPage($page, 30), $bills->count(), 30, $page, ['path' => $request->url(), 'query' => $request->query()]);

        return view('accounting::phase6.bills.index', ['bills' => $paged, 'suppliers' => $suppliers, 'f' => $f, 'status' => $status, 'summary' => $summary]);
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['supplier', 'items.material', 'payments', 'returns.items', 'creator']);
        $payments = $purchase->payments->sortBy('payment_date')->values();
        $returns = $purchase->returns->sortBy('return_date')->values();

        $journal = DocumentJournalService::forSources(array_filter([
            Purchase::class => [$purchase->id],
            'purchase' => [$purchase->id],
            PurchasePayment::class => $payments->pluck('id')->all(),
            PurchaseReturn::class => $returns->pluck('id')->all(),
        ]));
        $history = Schema::hasTable('audit_logs')
            ? AuditLog::query()->where(function ($w) use ($purchase, $payments, $returns) {
                $w->orWhere(fn ($q) => $q->where('auditable_type', Purchase::class)->where('auditable_id', $purchase->id));
                if ($purchase->items->isNotEmpty()) {
                    $w->orWhere(fn ($q) => $q->where('auditable_type', PurchaseItem::class)->whereIn('auditable_id', $purchase->items->pluck('id')));
                }
                if ($payments->isNotEmpty()) {
                    $w->orWhere(fn ($q) => $q->where('auditable_type', PurchasePayment::class)->whereIn('auditable_id', $payments->pluck('id')));
                }
                if ($returns->isNotEmpty()) {
                    $w->orWhere(fn ($q) => $q->where('auditable_type', PurchaseReturn::class)->whereIn('auditable_id', $returns->pluck('id')));
                }
            })->orderByDesc('id')->limit(300)->get()
            : collect();
        $inputVat = $purchase->inputVatAmount();
        $apCheck = $this->apCheck($journal, $purchase);

        return view('accounting::phase6.bills.show', compact('purchase', 'payments', 'returns', 'journal', 'history', 'inputVat', 'apCheck'));
    }

    public function approve(Purchase $purchase)
    {
        $this->requireAccountingAction('accounting.bills.approve');
        if ($purchase->status !== Purchase::STATUS_CONFIRMED || !Purchase::hasPayablesColumns()) {
            return back()->withErrors(['approve' => 'Only confirmed bills can be approved for payment.']);
        }
        if ($purchase->approved_at) {
            return back()->with('status', 'Bill already approved.');
        }
        $purchase->forceFill(['approved_at' => now(), 'approved_by' => auth()->id()])->save();
        AuditLogger::record($purchase, 'bill_approved', [], ['approved_at' => now()->toDateTimeString()]);

        return back()->with('status', 'Bill ' . $purchase->purchase_no . ' approved for payment.');
    }

    /** Bills to pay, grouped by supplier, due on or before a date — for planning a payment run (Xero "Batch payment"). */
    public function paymentRun(Request $request)
    {
        $data = $request->validate(['due_by' => 'nullable|date', 'export' => 'nullable|in:csv']);
        $dueBy = $data['due_by'] ?? now()->addDays(7)->toDateString();

        $bills = Purchase::query()
            ->with(['supplier', 'items', 'payments', 'returns'])
            ->where('status', Purchase::STATUS_CONFIRMED)
            ->when(Purchase::hasPayablesColumns(), fn ($q) => $q->whereDate('due_date', '<=', $dueBy))
            ->when(Purchase::hasPayablesColumns() && config('dermas.require_bill_approval'), fn ($q) => $q->whereNotNull('approved_at'))
            ->orderBy(Purchase::hasPayablesColumns() ? 'due_date' : 'date')
            ->get()
            ->filter(fn (Purchase $p) => (float) $p->balance > 0.009)
            ->values();
        $bySupplier = $bills->groupBy('supplier_id');

        if (($data['export'] ?? null) === 'csv') {
            return response()->streamDownload(function () use ($bills) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Supplier', 'Supplier VAT no.', 'Bill', 'Reference', 'Bill date', 'Due date', 'Days overdue', 'Balance to pay']);
                foreach ($bills as $b) {
                    fputcsv($out, [
                        $b->supplier?->company_name ?: $b->supplier?->name, $b->supplier?->tax_vat_no, $b->purchase_no, $b->reference_no,
                        $b->date?->toDateString(), $b->dueDate()?->toDateString(), $b->daysOverdue(), number_format((float) $b->balance, 2, '.', ''),
                    ]);
                }
                fclose($out);
            }, 'payment-run-' . $dueBy . '.csv', ['Content-Type' => 'text/csv']);
        }

        $inRuns = \Illuminate\Support\Facades\Schema::hasTable('acct_payment_runs')
            ? app(\App\Services\Accounting\PaymentRunService::class)->billsInActiveRuns()
            : [];
        $bankAccounts = DB::table('chart_of_accounts')->where('active', 1)
            ->where(fn ($q) => $q->whereIn('gl_code', ['1010'])->orWhere('name', 'like', '%bank%'))->orderBy('gl_code')->get(['id', 'gl_code', 'name']);

        return view('accounting::phase6.bills.payment_run', compact('bills', 'bySupplier', 'dueBy', 'inRuns', 'bankAccounts'));
    }

    /** @return array{ledger: float, bill: float, difference: float, account_found: bool} */
    private function apCheck(Collection $journal, Purchase $purchase): array
    {
        $apId = \App\Services\AccountingService::accountId(\App\Services\AccountingService::GL_ACCOUNTS_PAY);
        $ledger = 0.0;
        foreach ($journal as $txn) {
            if ($txn['reversed']) {
                continue;
            }
            foreach ($txn['lines'] as $line) {
                if ($apId && $line['account_id'] === $apId) {
                    $ledger = round($ledger + $line['credit'] - $line['debit'], 2);
                }
            }
        }
        $bill = $purchase->status === Purchase::STATUS_CONFIRMED ? round((float) $purchase->balance, 2) : 0.0;

        return ['ledger' => $ledger, 'bill' => $bill, 'difference' => round($ledger - $bill, 2), 'account_found' => (bool) $apId];
    }
}
