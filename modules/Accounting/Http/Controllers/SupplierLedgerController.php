<?php

namespace Modules\Accounting\Http\Controllers;

use App\Models\Accounting\SupplierPaymentAllocation;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SupplierLedgerController extends Controller
{
    public function index(Request $request)
    {
        $suppliers = collect();
        if (Schema::hasTable('suppliers')) {
            $suppliers = DB::table('suppliers')->orderBy('name')->limit(500)->get(['id', 'name']);
        }

        $supplierId = (int) $request->get('supplier_id');
        $ledger = null;

        if ($supplierId > 0 && Schema::hasTable('purchases')) {
            $bills = DB::table('purchases')->where('supplier_id', $supplierId)->orderBy('created_at')->get();
            $payments = Schema::hasTable('purchase_payments')
                ? DB::table('purchase_payments')->where('supplier_id', $supplierId)->orderBy('created_at')->get()
                : collect();

            $billTotal = round((float) $bills->sum(fn ($b) => (float) ($b->final_total ?? $b->total ?? 0)), 2);
            $paidTotal = round((float) $payments->sum(fn ($p) => (float) ($p->amount ?? 0)), 2);
            $allocated = Schema::hasTable('supplier_payment_allocations')
                ? round((float) SupplierPaymentAllocation::query()
                    ->whereIn('purchase_id', $bills->pluck('id'))
                    ->sum('amount'), 2)
                : $paidTotal;

            $ledger = [
                'bills' => $bills,
                'payments' => $payments,
                'bill_total' => number_format($billTotal, 2, '.', ''),
                'paid_total' => number_format($paidTotal, 2, '.', ''),
                'allocated' => number_format($allocated, 2, '.', ''),
                'outstanding' => number_format(max(0, round($billTotal - $allocated, 2)), 2, '.', ''),
            ];
        }

        return view('accounting::phase2.ap.supplier_ledger', compact('suppliers', 'supplierId', 'ledger'));
    }

    public function ageing()
    {
        return redirect('report/accounting/accounts_payable_ageing_summary');
    }
}
