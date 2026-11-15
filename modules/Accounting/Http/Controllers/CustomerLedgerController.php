<?php

namespace Modules\Accounting\Http\Controllers;

use App\Models\Accounting\CustomerPaymentAllocation;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomerLedgerController extends Controller
{
    public function index(Request $request)
    {
        $customers = collect();
        if (Schema::hasTable('customers')) {
            $customers = DB::table('customers')->orderBy('name')->limit(500)->get(['id', 'name', 'phone']);
        } elseif (Schema::hasTable('contacts')) {
            $customers = DB::table('contacts')->where('type', 'customer')->orderBy('name')->limit(500)->get(['id', 'name', 'mobile as phone']);
        }

        $customerId = (int) $request->get('customer_id');
        $ledger = null;

        if ($customerId > 0 && Schema::hasTable('orders')) {
            $orders = DB::table('orders')->where('customer_id', $customerId)->orderBy('created_at')->get();
            $payments = Schema::hasTable('payments')
                ? DB::table('payments')->where('customer_id', $customerId)->orderBy('created_at')->get()
                : collect();

            $invoiceTotal = round((float) $orders->sum(fn ($o) => (float) ($o->final_total ?? $o->total ?? 0)), 2);
            $paidTotal = round((float) $payments->sum(fn ($p) => (float) ($p->amount ?? 0)), 2);
            $allocated = Schema::hasTable('customer_payment_allocations')
                ? round((float) CustomerPaymentAllocation::query()
                    ->whereIn('order_id', $orders->pluck('id'))
                    ->sum('amount'), 2)
                : $paidTotal;

            $ledger = [
                'orders' => $orders,
                'payments' => $payments,
                'invoice_total' => number_format($invoiceTotal, 2, '.', ''),
                'paid_total' => number_format($paidTotal, 2, '.', ''),
                'allocated' => number_format($allocated, 2, '.', ''),
                'outstanding' => number_format(max(0, round($invoiceTotal - $allocated, 2)), 2, '.', ''),
            ];
        }

        return view('accounting::phase2.ar.customer_ledger', compact('customers', 'customerId', 'ledger'));
    }

    public function ageing()
    {
        return redirect('report/accounting/accounts_receivable_ageing_summary');
    }
}
