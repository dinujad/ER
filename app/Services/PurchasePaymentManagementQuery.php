<?php

namespace App\Services;

use App\Models\Purchase;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchasePaymentManagementQuery
{
    public static function purchaseReturnsSubquery(): string
    {
        return '(SELECT COALESCE(SUM(pr.total), 0) FROM purchase_returns pr WHERE pr.purchase_id = purchases.id)';
    }

    public static function purchasePaymentsOutSubquery(): string
    {
        return '(SELECT COALESCE(SUM(pp.amount), 0) FROM purchase_payments pp WHERE pp.purchase_id = purchases.id AND (pp.entry_type = \'payment\' OR pp.entry_type IS NULL))';
    }

    public static function purchaseRefundsSubquery(): string
    {
        return '(SELECT COALESCE(SUM(pp.amount), 0) FROM purchase_payments pp WHERE pp.purchase_id = purchases.id AND pp.entry_type = \'refund\')';
    }

    public static function purchasePaidSql(): string
    {
        return '(COALESCE(purchases.paid_amount, 0) + ' . self::purchasePaymentsOutSubquery() . ' - ' . self::purchaseRefundsSubquery() . ')';
    }

    public static function purchaseNetTotalSql(): string
    {
        return '(COALESCE(purchases.total, 0) - ' . self::purchaseReturnsSubquery() . ')';
    }

    public static function purchaseBalanceSql(): string
    {
        return '(' . self::purchaseNetTotalSql() . ' - ' . self::purchasePaidSql() . ')';
    }

    public function purchases(Request $request)
    {
        $query = Purchase::query()
            ->with(['supplier'])
            ->withSum(['payments as payments_out_sum' => fn ($q) => $q->where(function ($q2) {
                $q2->where('entry_type', 'payment')->orWhereNull('entry_type');
            })], 'amount')
            ->withSum(['payments as refunds_sum' => fn ($q) => $q->where('entry_type', 'refund')], 'amount')
            ->withSum('returns as returns_sum_total', 'total')
            ->where('status', '!=', Purchase::STATUS_CANCELLED);

        if ($request->filled('purchase_no')) {
            $needle = '%' . trim((string) $request->purchase_no) . '%';
            $query->where('purchase_no', 'like', $needle);
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('purchase_type')) {
            $query->where('purchase_type', $request->purchase_type);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        if ($request->filled('payment_method')) {
            $method = strtolower(trim((string) $request->payment_method));
            $query->where(function ($q) use ($method) {
                $q->whereRaw('LOWER(TRIM(payment_method)) = ?', [$method])
                    ->orWhereHas('payments', function ($pq) use ($method) {
                        $pq->whereRaw('LOWER(TRIM(payment_method)) = ?', [$method]);
                    });
            });
        }

        $balanceSql = self::purchaseBalanceSql();

        if ($request->get('payment_state') === 'due') {
            $query->whereRaw("{$balanceSql} > 0.00001");
        } elseif ($request->get('payment_state') === 'paid') {
            $query->whereRaw("{$balanceSql} <= 0.00001");
        } elseif ($request->get('payment_state') === 'partial') {
            $paidSql = self::purchasePaidSql();
            $query->whereRaw("{$paidSql} > 0 AND {$balanceSql} > 0.00001");
        } elseif ($request->get('payment_state') === 'unpaid') {
            $paidSql = self::purchasePaidSql();
            $query->whereRaw("{$paidSql} <= 0.00001 AND {$balanceSql} > 0.00001");
        }

        return $query->orderByDesc('date')->orderByDesc('id');
    }

    public function purchasesWithPayments(Request $request)
    {
        return $this->purchases($request)->with(['payments:id,purchase_id,payment_method,amount,payment_date']);
    }

    /**
     * All payment lines (initial on purchase + purchase_payments rows).
     */
    public function paymentRows(Request $request): Builder
    {
        $initial = DB::table('purchases')
            ->select([
                DB::raw("'initial' as source"),
                'purchases.id as source_id',
                'purchases.id as purchase_id',
                'purchases.supplier_id',
                'purchases.purchase_no',
                'purchases.date as payment_date',
                'purchases.payment_method',
                'purchases.paid_amount as amount',
                'purchases.cheque_date',
                'purchases.cheque_bank',
                'purchases.cheque_number',
                'purchases.payment_reference as reference',
                'purchases.total as purchase_total',
            ])
            ->where('paid_amount', '>', 0)
            ->where('status', '!=', Purchase::STATUS_CANCELLED);

        $additional = DB::table('purchase_payments')
            ->join('purchases', 'purchases.id', '=', 'purchase_payments.purchase_id')
            ->select([
                DB::raw("'additional' as source"),
                'purchase_payments.id as source_id',
                'purchase_payments.purchase_id',
                'purchase_payments.supplier_id',
                'purchases.purchase_no',
                'purchase_payments.payment_date',
                'purchase_payments.payment_method',
                'purchase_payments.amount',
                'purchase_payments.cheque_date',
                'purchase_payments.cheque_bank',
                'purchase_payments.cheque_number',
                'purchase_payments.reference',
                'purchases.total as purchase_total',
            ])
            ->where('purchases.status', '!=', Purchase::STATUS_CANCELLED)
            ->where(function ($q) {
                $q->where('purchase_payments.entry_type', 'payment')
                    ->orWhereNull('purchase_payments.entry_type');
            });

        $this->applyPaymentRowFilters($initial, $request, 'purchases');
        $this->applyPaymentRowFilters($additional, $request, 'purchase_payments', 'purchases');

        $union = $initial->unionAll($additional);

        return DB::table(DB::raw("({$union->toSql()}) as payment_rows"))
            ->mergeBindings($union)
            ->orderByDesc('payment_date')
            ->orderByDesc('source_id');
    }

    public function chequeRows(Request $request): Builder
    {
        $initial = DB::table('purchases')
            ->select([
                DB::raw("'initial' as source"),
                'purchases.id as source_id',
                'purchases.id as purchase_id',
                'purchases.supplier_id',
                'purchases.purchase_no',
                'purchases.date as payment_date',
                'purchases.paid_amount as amount',
                'purchases.cheque_date',
                'purchases.cheque_bank',
                'purchases.cheque_number',
                'purchases.payment_reference as reference',
            ])
            ->where('paid_amount', '>', 0)
            ->whereRaw("LOWER(TRIM(payment_method)) = 'cheque'")
            ->where('status', '!=', Purchase::STATUS_CANCELLED);

        $additional = DB::table('purchase_payments')
            ->join('purchases', 'purchases.id', '=', 'purchase_payments.purchase_id')
            ->select([
                DB::raw("'additional' as source"),
                'purchase_payments.id as source_id',
                'purchase_payments.purchase_id',
                'purchase_payments.supplier_id',
                'purchases.purchase_no',
                'purchase_payments.payment_date',
                'purchase_payments.amount',
                'purchase_payments.cheque_date',
                'purchase_payments.cheque_bank',
                'purchase_payments.cheque_number',
                'purchase_payments.reference',
            ])
            ->whereRaw("LOWER(TRIM(purchase_payments.payment_method)) = 'cheque'")
            ->where('purchases.status', '!=', Purchase::STATUS_CANCELLED)
            ->where(function ($q) {
                $q->where('purchase_payments.entry_type', 'payment')
                    ->orWhereNull('purchase_payments.entry_type');
            });

        $this->applyChequeFilters($initial, $request, 'purchases');
        $this->applyChequeFilters($additional, $request, 'purchase_payments', 'purchases');

        $union = $initial->unionAll($additional);

        return DB::table(DB::raw("({$union->toSql()}) as cheque_rows"))
            ->mergeBindings($union)
            ->orderBy('cheque_date')
            ->orderByDesc('payment_date');
    }

    protected function applyPaymentRowFilters(Builder $query, Request $request, string $paymentTable, ?string $purchaseTable = null): void
    {
        $purchasePrefix = $purchaseTable ? "{$purchaseTable}." : '';
        $methodCol = $paymentTable === 'purchases' ? 'purchases.payment_method' : "{$paymentTable}.payment_method";

        if ($request->filled('payment_method')) {
            $query->whereRaw('LOWER(TRIM(' . $methodCol . ')) = ?', [strtolower(trim((string) $request->payment_method))]);
        }

        if ($request->filled('purchase_no')) {
            $needle = '%' . trim((string) $request->purchase_no) . '%';
            $query->where("{$purchasePrefix}purchase_no", 'like', $needle);
        }

        if ($request->filled('supplier_id')) {
            $col = $paymentTable === 'purchases' ? 'purchases.supplier_id' : "{$paymentTable}.supplier_id";
            $query->where($col, $request->supplier_id);
        }

        if ($request->filled('payment_date_from')) {
            $dateCol = $paymentTable === 'purchases' ? 'purchases.date' : "{$paymentTable}.payment_date";
            $query->whereDate($dateCol, '>=', $request->payment_date_from);
        }

        if ($request->filled('payment_date_to')) {
            $dateCol = $paymentTable === 'purchases' ? 'purchases.date' : "{$paymentTable}.payment_date";
            $query->whereDate($dateCol, '<=', $request->payment_date_to);
        }

        if ($request->filled('cheque_date_from')) {
            $query->whereDate("{$paymentTable}.cheque_date", '>=', $request->cheque_date_from);
        }

        if ($request->filled('cheque_date_to')) {
            $query->whereDate("{$paymentTable}.cheque_date", '<=', $request->cheque_date_to);
        }

        if ($request->get('payment_state') === 'due') {
            $query->whereRaw(self::purchaseBalanceSql() . ' > 0.00001');
        }
    }

    protected function applyChequeFilters(Builder $query, Request $request, string $paymentTable, ?string $purchaseTable = null): void
    {
        $purchasePrefix = $purchaseTable ? "{$purchaseTable}." : '';

        if ($request->filled('cheque_date_from')) {
            $query->whereDate("{$paymentTable}.cheque_date", '>=', $request->cheque_date_from);
        }

        if ($request->filled('cheque_date_to')) {
            $query->whereDate("{$paymentTable}.cheque_date", '<=', $request->cheque_date_to);
        }

        if ($request->filled('payment_date_from')) {
            $dateCol = $paymentTable === 'purchases' ? 'purchases.date' : "{$paymentTable}.payment_date";
            $query->whereDate($dateCol, '>=', $request->payment_date_from);
        }

        if ($request->filled('payment_date_to')) {
            $dateCol = $paymentTable === 'purchases' ? 'purchases.date' : "{$paymentTable}.payment_date";
            $query->whereDate($dateCol, '<=', $request->payment_date_to);
        }

        if ($request->filled('purchase_no')) {
            $needle = '%' . trim((string) $request->purchase_no) . '%';
            $query->where("{$purchasePrefix}purchase_no", 'like', $needle);
        }

        if ($request->filled('supplier_id')) {
            $col = $paymentTable === 'purchases' ? 'purchases.supplier_id' : "{$paymentTable}.supplier_id";
            $query->where($col, $request->supplier_id);
        }

        if ($request->get('cheque_state') === 'overdue') {
            $query->whereNotNull("{$paymentTable}.cheque_date")
                ->whereDate("{$paymentTable}.cheque_date", '<', now()->toDateString());
        } elseif ($request->get('cheque_state') === 'upcoming') {
            $query->whereNotNull("{$paymentTable}.cheque_date")
                ->whereDate("{$paymentTable}.cheque_date", '>=', now()->toDateString());
        } elseif ($request->get('cheque_state') === 'today') {
            $query->whereDate("{$paymentTable}.cheque_date", now()->toDateString());
        }
    }

    public static function purchasePaidAmount(Purchase $purchase): float
    {
        $out = (float) ($purchase->payments_out_sum ?? 0);
        $refunds = (float) ($purchase->refunds_sum ?? 0);

        return (float) $purchase->paid_amount + $out - $refunds;
    }

    public static function purchaseNetTotal(Purchase $purchase): float
    {
        $returned = (float) ($purchase->returns_sum_total ?? 0);

        return max(0, round((float) $purchase->total - $returned, 2));
    }

    public static function purchaseDueAmount(Purchase $purchase): float
    {
        return max(0, round(self::purchaseNetTotal($purchase) - self::purchasePaidAmount($purchase), 2));
    }

    /** @return array<string, float> method key => total amount */
    public static function paymentTotalsByMethod(Purchase $purchase): array
    {
        $totals = [];
        if ((float) $purchase->paid_amount > 0 && filled($purchase->payment_method)) {
            $key = strtolower(trim((string) $purchase->payment_method));
            $totals[$key] = ($totals[$key] ?? 0) + (float) $purchase->paid_amount;
        }
        foreach ($purchase->payments ?? [] as $pay) {
            if ($pay->isRefund() || ! filled($pay->payment_method)) {
                continue;
            }
            $key = strtolower(trim((string) $pay->payment_method));
            $totals[$key] = ($totals[$key] ?? 0) + (float) $pay->amount;
        }

        return $totals;
    }

    public static function paymentMethodLabel(string $method): string
    {
        return match (strtolower(trim($method))) {
            'bank_transfer' => 'Bank Transfer',
            'cheque' => 'Cheque',
            'cash' => 'Cash',
            default => ucfirst(str_replace('_', ' ', $method)),
        };
    }
}
