<?php

namespace Modules\Accounting\Http\Controllers;

use App\Models\Accounting\CustomerPaymentAllocation;
use App\Models\Accounting\SupplierPaymentAllocation;
use App\Services\Accounting\PaymentAllocationService;
use App\Services\Accounting\StatementPdfService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Services\FlashService;

class PaymentAllocationController extends Controller
{
    public function customerIndex(Request $request)
    {
        $payments = collect();
        if (Schema::hasTable('payments')) {
            $payments = DB::table('payments')
                ->leftJoin('orders', 'orders.id', '=', 'payments.order_id')
                ->orderByDesc('payments.id')
                ->limit(200)
                ->get([
                    'payments.id', 'payments.amount', 'payments.payment_date', 'payments.order_id',
                    'orders.customer_id', 'orders.total_amount',
                ]);
        }

        $paymentId = (int) $request->get('payment_id');
        $openInvoices = collect();
        $allocations = collect();
        $payment = null;

        if ($paymentId > 0 && Schema::hasTable('payments')) {
            $payment = DB::table('payments')->where('id', $paymentId)->first();
            $customerId = null;
            if ($payment && Schema::hasTable('orders')) {
                $customerId = DB::table('orders')->where('id', $payment->order_id)->value('customer_id');
            }
            if ($customerId) {
                $openInvoices = DB::table('orders')
                    ->where('customer_id', $customerId)
                    ->whereNotIn('status', ['cancelled', 'quotation'])
                    ->orderByDesc('id')
                    ->limit(100)
                    ->get();
            }
            $allocations = CustomerPaymentAllocation::where('payment_id', $paymentId)->get();
        }

        $unallocated = $paymentId > 0 ? PaymentAllocationService::customerUnallocated($paymentId) : 0.0;
        $arReconcile = StatementPdfService::arControlReconciliation();

        return view('accounting::phase2.ar.payment_allocation', compact(
            'payments', 'paymentId', 'payment', 'openInvoices', 'allocations', 'unallocated', 'arReconcile'
        ));
    }

    public function customerStore(Request $request)
    {
        $request->validate([
            'payment_id' => 'required|integer',
            'allocations' => 'required|array|min:1',
            'allocations.*.order_id' => 'required|integer',
            'allocations.*.amount' => 'required|numeric|min:0.01',
        ]);

        try {
            PaymentAllocationService::allocateCustomerPayment(
                (int) $request->payment_id,
                $request->allocations,
                Auth::id()
            );
            (new FlashService())->onSave();
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBackWithInput();
        }

        return redirect('accounting/receivables/payment-allocation?payment_id='.$request->payment_id);
    }

    public function supplierIndex(Request $request)
    {
        $payments = collect();
        if (Schema::hasTable('purchase_payments')) {
            $payments = DB::table('purchase_payments')->orderByDesc('id')->limit(200)->get();
        }

        $paymentId = (int) $request->get('purchase_payment_id');
        $openBills = collect();
        $allocations = collect();
        $payment = null;

        if ($paymentId > 0) {
            $payment = DB::table('purchase_payments')->where('id', $paymentId)->first();
            if ($payment) {
                $openBills = DB::table('purchases')
                    ->where('supplier_id', $payment->supplier_id)
                    ->where('status', '!=', 'cancelled')
                    ->orderByDesc('id')
                    ->limit(100)
                    ->get();
                $allocations = SupplierPaymentAllocation::where('purchase_payment_id', $paymentId)->get();
            }
        }

        return view('accounting::phase2.ap.payment_allocation', compact('payments', 'paymentId', 'payment', 'openBills', 'allocations'));
    }

    public function supplierStore(Request $request)
    {
        $request->validate([
            'purchase_payment_id' => 'required|integer',
            'allocations' => 'required|array|min:1',
            'allocations.*.purchase_id' => 'required|integer',
            'allocations.*.amount' => 'required|numeric|min:0.01',
        ]);

        try {
            PaymentAllocationService::allocateSupplierPayment(
                (int) $request->purchase_payment_id,
                $request->allocations,
                Auth::id()
            );
            (new FlashService())->onSave();
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBackWithInput();
        }

        return redirect('accounting/payables/payment-allocation?purchase_payment_id='.$request->purchase_payment_id);
    }
}
