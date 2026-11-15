<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchasePaymentManagementQuery;
use Illuminate\Http\Request;

class PurchasePaymentManagementController extends Controller
{
    public function __construct(
        protected PurchasePaymentManagementQuery $queries
    ) {}

    public function index(Request $request)
    {
        $baseQuery = $this->queries->purchasesWithPayments($request);

        $purchases = (clone $baseQuery)
            ->paginate(25)
            ->withQueryString();

        $summaryRows = (clone $baseQuery)->get(['id', 'total', 'paid_amount']);
        $summaryRows->loadSum(['payments as payments_out_sum' => fn ($q) => $q->where(function ($q2) {
            $q2->where('entry_type', 'payment')->orWhereNull('entry_type');
        })], 'amount')
            ->loadSum(['payments as refunds_sum' => fn ($q) => $q->where('entry_type', 'refund')], 'amount')
            ->loadSum('returns as returns_sum_total', 'total');

        $duePurchases = $summaryRows->filter(fn ($p) => PurchasePaymentManagementQuery::purchaseDueAmount($p) > 0.00001);
        $totalOutstanding = (float) $duePurchases->sum(fn ($p) => PurchasePaymentManagementQuery::purchaseDueAmount($p));
        $duePurchaseCount = $duePurchases->count();

        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get(['id', 'name']);

        return view('erp.purchase-payment-management.index', compact(
            'purchases',
            'suppliers',
            'totalOutstanding',
            'duePurchaseCount'
        ));
    }

    public function payments(Request $request)
    {
        $baseQuery = $this->queries->paymentRows($request);

        $paymentCount = (clone $baseQuery)->count();
        $paymentAmountSum = (float) (clone $baseQuery)->sum('amount');

        $paymentRows = (clone $baseQuery)
            ->paginate(25)
            ->withQueryString();

        $purchaseIds = $paymentRows->getCollection()->pluck('purchase_id')->filter()->unique()->values();
        $purchasesById = Purchase::query()
            ->with(['supplier'])
            ->withSum(['payments as payments_out_sum' => fn ($q) => $q->where(function ($q2) {
                $q2->where('entry_type', 'payment')->orWhereNull('entry_type');
            })], 'amount')
            ->withSum(['payments as refunds_sum' => fn ($q) => $q->where('entry_type', 'refund')], 'amount')
            ->withSum('returns as returns_sum_total', 'total')
            ->whereIn('id', $purchaseIds)
            ->get()
            ->keyBy('id');

        $supplierIds = $paymentRows->getCollection()->pluck('supplier_id')->filter()->unique();
        $suppliersById = Supplier::whereIn('id', $supplierIds)->get(['id', 'name'])->keyBy('id');

        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get(['id', 'name']);

        return view('erp.purchase-payment-management.payments', compact(
            'paymentRows',
            'suppliers',
            'suppliersById',
            'purchasesById',
            'paymentCount',
            'paymentAmountSum'
        ));
    }

    public function cheques(Request $request)
    {
        $baseQuery = $this->queries->chequeRows($request);

        $chequeCount = (clone $baseQuery)->count();
        $chequeAmountSum = (float) (clone $baseQuery)->sum('amount');

        $cheques = (clone $baseQuery)
            ->paginate(25)
            ->withQueryString();

        $purchaseIds = $cheques->getCollection()->pluck('purchase_id')->filter()->unique()->values();
        $purchasesById = Purchase::query()
            ->with(['supplier'])
            ->withSum(['payments as payments_out_sum' => fn ($q) => $q->where(function ($q2) {
                $q2->where('entry_type', 'payment')->orWhereNull('entry_type');
            })], 'amount')
            ->withSum(['payments as refunds_sum' => fn ($q) => $q->where('entry_type', 'refund')], 'amount')
            ->withSum('returns as returns_sum_total', 'total')
            ->whereIn('id', $purchaseIds)
            ->get()
            ->keyBy('id');

        $supplierIds = $cheques->getCollection()->pluck('supplier_id')->filter()->unique();
        $suppliersById = Supplier::whereIn('id', $supplierIds)->get(['id', 'name', 'phone'])->keyBy('id');

        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get(['id', 'name']);

        return view('erp.purchase-payment-management.cheques', compact(
            'cheques',
            'suppliers',
            'suppliersById',
            'purchasesById',
            'chequeCount',
            'chequeAmountSum'
        ));
    }
}
