<?php

namespace App\Services\Accounting;

use App\Models\Order;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\SaleReturn;
use App\Services\AccountingService;
use App\Services\SalesAccountingService;
use App\Support\VatTreatment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6.3 — VAT return and SSCL return for a period, built from the documents
 * (and reconciled to the VAT / SSCL ledger accounts), with drill-down rows for every figure.
 */
class VatReturnService
{
    private const ISSUED = [Order::STATUS_COMPLETED, Order::STATUS_PAID, Order::STATUS_CREDIT];

    /**
     * @return array<string, mixed>
     */
    public static function build(string $from, string $to): array
    {
        $sales = self::salesRows($from, $to);
        $returns = self::saleReturnRows($from, $to);
        $credits = self::creditNoteRows($from, $to);
        $bills = self::billRows($from, $to);
        $billReturns = self::billReturnRows($from, $to);

        $byTreatment = [];
        foreach (VatTreatment::options() as $code => $label) {
            $rows = $sales->where('treatment', $code);
            $byTreatment[$code] = [
                'label' => VatTreatment::label($code),
                'count' => $rows->count(),
                'goods' => round($rows->sum('goods'), 2),
                'sscl' => round($rows->sum('sscl'), 2),
                'vat' => round($rows->sum('vat'), 2),
            ];
        }

        $outputVat = round($sales->sum('vat') - $returns->sum('vat') - $credits->sum('vat'), 2);
        $inputVat = round($bills->sum('vat') - $billReturns->sum('vat'), 2);
        $sscl = round($sales->sum('sscl') - $returns->sum('sscl') - $credits->sum('sscl'), 2);

        return [
            'from' => $from,
            'to' => $to,
            'by_treatment' => $byTreatment,
            'sales' => [
                'count' => $sales->count(),
                'goods' => round($sales->sum('goods'), 2),
                'sscl' => round($sales->sum('sscl'), 2),
                'vat' => round($sales->sum('vat'), 2),
            ],
            'returns' => [
                'count' => $returns->count(),
                'goods' => round($returns->sum('goods'), 2),
                'sscl' => round($returns->sum('sscl'), 2),
                'vat' => round($returns->sum('vat'), 2),
            ],
            'credit_notes' => [
                'count' => $credits->count(),
                'goods' => round($credits->sum('goods'), 2),
                'sscl' => round($credits->sum('sscl'), 2),
                'vat' => round($credits->sum('vat'), 2),
            ],
            'bills' => [
                'count' => $bills->count(),
                'net' => round($bills->sum('net'), 2),
                'vat' => round($bills->sum('vat'), 2),
            ],
            'bill_returns' => [
                'count' => $billReturns->count(),
                'net' => round($billReturns->sum('net'), 2),
                'vat' => round($billReturns->sum('vat'), 2),
            ],
            'output_vat' => $outputVat,
            'input_vat' => $inputVat,
            'net_vat' => round($outputVat - $inputVat, 2),
            'sscl_payable' => $sscl,
            'ledger' => [
                'vat_output' => self::ledgerMovement(AccountingService::GL_VAT_OUTPUT, $from, $to, true),
                'vat_input' => self::ledgerMovement(AccountingService::GL_VAT_INPUT, $from, $to, false),
                'sscl' => self::ledgerMovement(AccountingService::GL_SSCL_PAYABLE, $from, $to, true),
            ],
        ];
    }

    /** Drill-down rows behind one figure. */
    public static function detail(string $section, string $from, string $to): Collection
    {
        return match ($section) {
            'sales' => self::salesRows($from, $to),
            'returns' => self::saleReturnRows($from, $to),
            'credit_notes' => self::creditNoteRows($from, $to),
            'bills' => self::billRows($from, $to),
            'bill_returns' => self::billReturnRows($from, $to),
            default => collect(),
        };
    }

    /**
     * Tax invoices issued in the period with numbering check (gaps / duplicates in the serial).
     *
     * @return array{rows: Collection, gaps: array<int, int>, missing_tin: int}
     */
    public static function taxInvoiceRegister(string $from, string $to): array
    {
        $rows = self::salesQuery($from, $to, false)
            ->with('customer')
            ->orderBy('id')
            ->get()
            ->map(fn (Order $o) => (object) [
                'id' => $o->id,
                'number' => $o->invoice_number,
                'serial' => self::serialNumber((string) $o->invoice_number),
                'date' => $o->invoiceDate()?->toDateString(),
                'customer' => $o->customer?->business_name ?: $o->customer?->name,
                'tin' => $o->customer?->tin,
                'type' => filled($o->customer?->tin) ? 'Tax invoice' : 'Invoice',
                'total' => $o->invoice_total,
                'voided' => $o->invoiceStatus() === 'voided',
            ]);

        $serials = $rows->pluck('serial')->filter()->sort()->values();
        $gaps = [];
        for ($i = 1; $i < $serials->count(); $i++) {
            for ($n = $serials[$i - 1] + 1; $n < $serials[$i] && count($gaps) < 200; $n++) {
                $gaps[] = $n;
            }
        }

        return [
            'rows' => $rows,
            'gaps' => $gaps,
            'missing_tin' => $rows->where('type', 'Invoice')->count(),
        ];
    }

    public static function serialNumber(string $invoiceNumber): ?int
    {
        return preg_match('/(\d+)\s*$/', $invoiceNumber, $m) ? (int) $m[1] : null;
    }

    private static function salesQuery(string $from, string $to, bool $taxedOnly = true)
    {
        $q = Order::query()
            ->whereIn('status', self::ISSUED)
            ->whereNotNull('invoice_number')
            ->whereDate(DB::raw('COALESCE(invoice_date, order_date, created_at)'), '>=', $from)
            ->whereDate(DB::raw('COALESCE(invoice_date, order_date, created_at)'), '<=', $to);
        if (\App\Support\SchemaCache::hasColumn('orders', 'invoice_voided_at')) {
            $q->whereNull('invoice_voided_at');
        }
        if ($taxedOnly) {
            $q->where(fn ($w) => $w->whereNotNull('invoice_sscl_amount')->orWhereNotNull('invoice_vat_amount'));
        }

        return $q;
    }

    private static function salesRows(string $from, string $to): Collection
    {
        return self::salesQuery($from, $to)->with('customer')->orderBy('id')->get()->map(fn (Order $o) => (object) [
            'id' => $o->id,
            'date' => $o->invoiceDate()?->toDateString(),
            'document' => 'Invoice ' . $o->invoice_number,
            'url' => url('accounting/phase6/invoices/' . $o->id),
            'party' => $o->customer?->business_name ?: $o->customer?->name,
            'tin' => $o->customer?->tin,
            'treatment' => VatTreatment::normalize($o->invoice_vat_code),
            'goods' => $o->goodsValue(),
            'sscl' => round((float) $o->invoice_sscl_amount, 2),
            'vat' => round((float) $o->invoice_vat_amount, 2),
        ]);
    }

    private static function saleReturnRows(string $from, string $to): Collection
    {
        if (!\App\Support\SchemaCache::hasTable('sale_returns')) {
            return collect();
        }
        $shares = app(SalesAccountingService::class);

        return SaleReturn::query()
            ->with('order.customer')
            ->where('status', SaleReturn::STATUS_CONFIRMED)
            ->whereDate('return_date', '>=', $from)
            ->whereDate('return_date', '<=', $to)
            ->orderBy('id')
            ->get()
            ->filter(fn (SaleReturn $r) => $r->order && $r->order->chargesInvoiceTax())
            ->map(function (SaleReturn $r) use ($shares) {
                $s = $shares->returnTaxShares($r->order, (float) $r->total);
                $goods = round($r->order->withInvoiceTax((float) $r->total) - $s['sscl'] - $s['vat'], 2);

                return (object) [
                    'id' => $r->id,
                    'date' => \Illuminate\Support\Carbon::parse($r->return_date)->toDateString(),
                    'document' => 'Sale return ' . $r->return_no . ' (invoice ' . $r->order->invoice_number . ')',
                    'url' => url('accounting/phase6/invoices/' . $r->order_id) . '#returns',
                    'party' => $r->order->customer?->business_name ?: $r->order->customer?->name,
                    'tin' => $r->order->customer?->tin,
                    'goods' => $goods,
                    'sscl' => $s['sscl'],
                    'vat' => $s['vat'],
                ];
            })->values();
    }

    private static function creditNoteRows(string $from, string $to): Collection
    {
        if (!\App\Support\SchemaCache::hasTable('credit_notes')) {
            return collect();
        }

        return \App\Models\CreditNote::query()
            ->with('order.customer')
            ->where('type', 'adjustment')
            ->where('status', '!=', 'void')
            ->whereDate('credit_note_date', '>=', $from)
            ->whereDate('credit_note_date', '<=', $to)
            ->orderBy('id')
            ->get()
            ->map(fn ($cn) => (object) [
                'id' => $cn->id,
                'date' => $cn->credit_note_date?->toDateString(),
                'document' => 'Credit note ' . $cn->credit_note_number,
                'url' => url('accounting/phase6/invoices/' . $cn->order_id) . '#credit-notes',
                'party' => $cn->order?->customer?->business_name ?: $cn->order?->customer?->name,
                'tin' => $cn->order?->customer?->tin,
                'goods' => round((float) $cn->goods_amount, 2),
                'sscl' => round((float) $cn->sscl_amount, 2),
                'vat' => round((float) $cn->vat_amount, 2),
            ]);
    }

    private static function billRows(string $from, string $to): Collection
    {
        if (!\App\Support\SchemaCache::hasColumn('purchases', 'input_vat_claimable')) {
            return collect();
        }

        return Purchase::query()
            ->with(['supplier', 'items'])
            ->where('status', Purchase::STATUS_CONFIRMED)
            ->where('input_vat_claimable', true)
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->orderBy('id')
            ->get()
            ->map(function (Purchase $p) {
                $vat = $p->inputVatAmount();

                return (object) [
                    'id' => $p->id,
                    'date' => $p->date?->toDateString(),
                    'document' => 'Bill ' . $p->purchase_no,
                    'url' => url('accounting/phase6/bills/' . $p->id),
                    'party' => $p->supplier?->company_name ?: $p->supplier?->name,
                    'tin' => $p->supplier?->tax_vat_no,
                    'net' => round((float) $p->total - $vat, 2),
                    'vat' => $vat,
                ];
            })
            ->filter(fn ($r) => $r->vat > 0)
            ->values();
    }

    private static function billReturnRows(string $from, string $to): Collection
    {
        if (!\App\Support\SchemaCache::hasTable('purchase_returns') || !\App\Support\SchemaCache::hasColumn('purchases', 'input_vat_claimable')) {
            return collect();
        }

        return PurchaseReturn::query()
            ->with(['purchase.supplier', 'purchase.items'])
            ->where('status', PurchaseReturn::STATUS_CONFIRMED)
            ->whereDate('return_date', '>=', $from)
            ->whereDate('return_date', '<=', $to)
            ->orderBy('id')
            ->get()
            ->map(function (PurchaseReturn $r) {
                $vat = $r->purchase ? $r->purchase->returnedInputVat((float) $r->total) : 0.0;

                return (object) [
                    'id' => $r->id,
                    'date' => $r->return_date?->toDateString(),
                    'document' => 'Purchase return ' . $r->return_no . ' (bill ' . ($r->purchase?->purchase_no ?? '') . ')',
                    'url' => $r->purchase_id ? url('accounting/phase6/bills/' . $r->purchase_id) . '#returns' : null,
                    'party' => $r->purchase?->supplier?->company_name ?: $r->purchase?->supplier?->name,
                    'tin' => $r->purchase?->supplier?->tax_vat_no,
                    'net' => round((float) $r->total, 2),
                    'vat' => $vat,
                ];
            })
            ->filter(fn ($r) => $r->vat > 0)
            ->values();
    }

    /** Net movement on a GL account in the period (credit-normal → credits − debits). */
    private static function ledgerMovement(string $glCode, string $from, string $to, bool $creditNormal): ?float
    {
        $id = AccountingService::accountId($glCode);
        if (!$id || !\App\Support\SchemaCache::hasTable('journal_entries')) {
            return null;
        }
        $net = (float) DB::table('journal_entries')
            ->where('chart_of_account_id', $id)
            ->where('reversed', 0)
            ->whereBetween('date', [$from, $to])
            ->selectRaw('COALESCE(SUM(debit),0) - COALESCE(SUM(credit),0) as b')
            ->value('b');

        return round($creditNormal ? -$net : $net, 2);
    }
}
