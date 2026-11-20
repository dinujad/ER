<?php

namespace Modules\Accounting\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\SaleReturn;
use App\Services\Accounting\DocumentJournalService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6.2 — Xero-style sales invoices: list with statuses, and a drill-down page per invoice
 * (lines, tax, payments, returns, credit notes, journal entries, source chain, history).
 */
class SalesInvoiceController extends Controller
{
    private const ISSUED_STATUSES = [Order::STATUS_COMPLETED, Order::STATUS_PAID, Order::STATUS_CREDIT];

    public function index(Request $request)
    {
        $f = $request->validate([
            'status' => 'nullable|in:all,draft,awaiting_payment,overdue,paid,voided',
            'customer_id' => 'nullable|integer',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'q' => 'nullable|string|max:80',
        ]);
        $status = $f['status'] ?? 'all';
        $billed = Order::invoiceTotalSql();
        $paidSub = Order::effectivePaidAmountSubquery();
        $today = now()->toDateString();

        $query = Order::query()
            ->with(['customer', 'salesPerson'])
            ->when($f['customer_id'] ?? null, fn ($q, $v) => $q->where('customer_id', $v))
            ->when($f['from'] ?? null, fn ($q, $v) => $q->whereDate(DB::raw('COALESCE(invoice_date, order_date, created_at)'), '>=', $v))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->whereDate(DB::raw('COALESCE(invoice_date, order_date, created_at)'), '<=', $v))
            ->when($f['q'] ?? null, function ($q, $v) {
                $q->where(function ($w) use ($v) {
                    $w->where('invoice_number', 'like', "%{$v}%")
                        ->orWhere('order_number', 'like', "%{$v}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$v}%")->orWhere('business_name', 'like', "%{$v}%"));
                });
            });

        $hasVoid = Schema::hasColumn('orders', 'invoice_voided_at');
        if ($hasVoid && in_array($status, ['paid', 'awaiting_payment', 'overdue'], true)) {
            $query->whereNull('invoice_voided_at');
        }
        match ($status) {
            'draft' => $query->whereNotIn('status', array_merge(self::ISSUED_STATUSES, [Order::STATUS_CANCELLED, Order::STATUS_QUOTATION])),
            'voided' => $query->where(fn ($q) => $hasVoid
                ? $q->whereNotNull('invoice_voided_at')->orWhere(fn ($w) => $w->where('status', Order::STATUS_CANCELLED)->whereNotNull('invoice_number'))
                : $q->where('status', Order::STATUS_CANCELLED)->whereNotNull('invoice_number')),
            'paid' => $query->whereIn('status', self::ISSUED_STATUSES)->whereRaw("{$billed} <= {$paidSub} + 0.009"),
            'awaiting_payment' => $query->whereIn('status', self::ISSUED_STATUSES)->whereRaw("{$billed} > {$paidSub} + 0.009"),
            'overdue' => $query->whereIn('status', self::ISSUED_STATUSES)->whereRaw("{$billed} > {$paidSub} + 0.009")
                ->whereNotNull('invoice_due_date')->whereDate('invoice_due_date', '<', $today),
            default => $query->whereNotNull('invoice_number'),
        };

        $summaryQuery = (clone $query)->reorder();
        $summary = [
            'count' => (clone $summaryQuery)->count(),
            'billed' => round((float) (clone $summaryQuery)->sum(DB::raw($billed)), 2),
        ];
        $orders = $query->orderByDesc(DB::raw('COALESCE(invoice_date, order_date, created_at)'))->orderByDesc('id')
            ->paginate(25)->withQueryString();
        $customers = Customer::query()->orderBy('name')->get(['id', 'name', 'business_name']);

        return view('accounting::phase6.invoices.index', compact('orders', 'customers', 'f', 'status', 'summary'));
    }

    public function show(Order $order)
    {
        $order->load(['customer', 'salesPerson', 'items.product', 'items.variation', 'payments', 'saleReturns.items']);

        $creditNotes = $this->creditNotes($order);
        $payments = $order->payments->sortBy('payment_date')->values();
        $returns = $order->saleReturns->sortBy('return_date')->values();

        $journal = DocumentJournalService::forSources(array_filter([
            Order::class => [$order->id],
            Payment::class => $payments->pluck('id')->all(),
            SaleReturn::class => $returns->pluck('id')->all(),
            \App\Models\CreditNote::class => $creditNotes->pluck('id')->all(),
        ]));

        $history = $this->history($order, $payments, $returns, $creditNotes);
        $recordedBy = $history->where('event', 'created')
            ->mapWithKeys(fn (AuditLog $l) => [$l->auditable_type . '#' . $l->auditable_id => $l->user_name]);
        $issuedLog = $history->firstWhere('event', 'invoice_issued');
        $stockMoves = Schema::hasTable('stock_ledgers')
            ? DB::table('stock_ledgers')->where('reference_type', Order::class)->where('reference_id', $order->id)->orderBy('id')->get()
            : collect();
        $tax = $order->taxInvoiceVatBreakdown();
        $timeline = $this->timeline($order, $payments, $returns, $creditNotes, $issuedLog, $stockMoves, $recordedBy);
        $arCheck = $this->arCheck($journal, $order);

        return view('accounting::phase6.invoices.show', compact(
            'order', 'payments', 'returns', 'creditNotes', 'journal', 'history', 'recordedBy', 'stockMoves', 'tax', 'timeline', 'arCheck'
        ));
    }

    private function creditNotes(Order $order): Collection
    {
        if (!Schema::hasTable('credit_notes') || !class_exists(\App\Models\CreditNote::class)) {
            return collect();
        }

        return \App\Models\CreditNote::query()->where('order_id', $order->id)->orderBy('credit_note_date')->orderBy('id')->get();
    }

    private function history(Order $order, Collection $payments, Collection $returns, Collection $creditNotes): Collection
    {
        if (!Schema::hasTable('audit_logs')) {
            return collect();
        }

        $sets = [
            Order::class => [$order->id],
            OrderItem::class => $order->items->pluck('id')->all(),
            Payment::class => $payments->pluck('id')->all(),
            SaleReturn::class => $returns->pluck('id')->all(),
            \App\Models\CreditNote::class => $creditNotes->pluck('id')->all(),
        ];

        return AuditLog::query()
            ->where(function ($w) use ($sets) {
                foreach ($sets as $type => $ids) {
                    if ($ids) {
                        $w->orWhere(fn ($q) => $q->where('auditable_type', $type)->whereIn('auditable_id', $ids));
                    }
                }
            })
            ->orderByDesc('id')
            ->limit(300)
            ->get();
    }

    /**
     * "Where did this come from" — ordered chain of events that built this invoice.
     *
     * @return Collection<int, array{when: mixed, icon: string, title: string, detail: string, url: ?string}>
     */
    private function timeline(Order $order, Collection $payments, Collection $returns, Collection $creditNotes, ?AuditLog $issuedLog, Collection $stockMoves, Collection $recordedBy): Collection
    {
        $events = collect();
        $events->push([
            'when' => $order->created_at,
            'icon' => 'bi-cart-plus',
            'title' => 'Order ' . ($order->order_number ?? '#' . $order->id) . ' created',
            'detail' => 'Sales person: ' . ($order->salesPerson?->name ?? '—') . ' · Customer: ' . ($order->customer?->display_name ?? '—')
                . ' · ' . $order->items->count() . ' line(s)',
            'url' => \Illuminate\Support\Facades\Route::has('orders.show') ? route('orders.show', $order->id) : null,
        ]);
        if ($stockMoves->isNotEmpty()) {
            $events->push([
                'when' => $stockMoves->first()->created_at,
                'icon' => 'bi-box-seam',
                'title' => 'Stock issued from ' . ($stockMoves->first()->channel ?? 'stock'),
                'detail' => $stockMoves->count() . ' stock movement(s), ' . number_format(abs((float) $stockMoves->sum('quantity')), 2) . ' units',
                'url' => null,
            ]);
        }
        if ($order->isInvoiceIssued() || filled($order->invoice_number)) {
            $events->push([
                'when' => $issuedLog?->created_at ?? $order->invoice_date ?? $order->updated_at,
                'icon' => 'bi-receipt',
                'title' => 'Invoice ' . $order->invoice_number . ' issued',
                'detail' => ($issuedLog ? 'By ' . $issuedLog->user_name . ' · ' : '') . 'Total Rs ' . number_format($order->invoice_total, 2)
                    . ($order->chargesInvoiceTax() ? ' (incl. SSCL & VAT)' : ''),
                'url' => null,
            ]);
        }
        foreach ($payments as $p) {
            $events->push([
                'when' => $p->payment_date ?? $p->created_at,
                'icon' => $p->isRefund() ? 'bi-arrow-return-left' : 'bi-cash-coin',
                'title' => ($p->isRefund() ? 'Refund' : 'Payment') . ' Rs ' . number_format((float) $p->amount, 2) . ' (' . ucfirst((string) $p->payment_method) . ')',
                'detail' => trim(($p->reference_number ? 'Ref ' . $p->reference_number . ' · ' : '')
                    . ($p->isCheque() ? 'Cheque ' . $p->chequeStatusLabel() . ' · ' : '')
                    . 'Recorded by ' . ($recordedBy[Payment::class . '#' . $p->id] ?? '—')),
                'url' => '#payments',
            ]);
        }
        foreach ($returns as $r) {
            $events->push([
                'when' => $r->return_date ?? $r->created_at,
                'icon' => 'bi-arrow-counterclockwise',
                'title' => 'Sale return ' . ($r->return_no ?? '#' . $r->id),
                'detail' => 'Goods Rs ' . number_format((float) $r->total, 2) . ' · credited Rs ' . number_format($order->withInvoiceTax((float) $r->total), 2),
                'url' => '#returns',
            ]);
        }
        foreach ($creditNotes as $cn) {
            $events->push([
                'when' => $cn->credit_note_date ?? $cn->created_at,
                'icon' => 'bi-file-earmark-minus',
                'title' => 'Credit note ' . $cn->credit_note_number,
                'detail' => 'Rs ' . number_format((float) $cn->total, 2) . ' · ' . $cn->reason,
                'url' => '#credit-notes',
            ]);
        }
        if ($order->invoice_voided_at) {
            $events->push([
                'when' => $order->invoice_voided_at,
                'icon' => 'bi-x-octagon',
                'title' => 'Invoice voided',
                'detail' => (string) $order->invoice_void_reason,
                'url' => null,
            ]);
        }

        return $events->sortBy(fn ($e) => $e['when'] ? \Illuminate\Support\Carbon::parse($e['when'])->timestamp : 0)->values();
    }

    /**
     * Receivable balance this invoice left in the ledger vs. the balance due shown on the invoice.
     *
     * @return array{ledger: float, invoice: float, difference: float, account_found: bool}
     */
    private function arCheck(Collection $journal, Order $order): array
    {
        $arId = \App\Services\AccountingService::accountId(\App\Services\AccountingService::GL_ACCOUNTS_RECV);
        $ledger = 0.0;
        foreach ($journal as $txn) {
            if ($txn['reversed']) {
                continue;
            }
            foreach ($txn['lines'] as $line) {
                if ($arId && $line['account_id'] === $arId) {
                    $ledger = round($ledger + $line['debit'] - $line['credit'], 2);
                }
            }
        }
        $invoice = $order->invoiceStatus() === 'voided' ? 0.0 : (float) $order->due_amount;

        return [
            'ledger' => $ledger,
            'invoice' => $invoice,
            'difference' => round($ledger - $invoice, 2),
            'account_found' => (bool) $arId,
        ];
    }
}
