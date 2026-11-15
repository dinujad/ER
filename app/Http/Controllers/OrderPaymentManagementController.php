<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;

class OrderPaymentManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query()
            ->with(['customer', 'salesPerson'])
            ->withSum(['payments as payments_sum_amount' => fn ($q) => $q->effectiveForOrderPaid()], 'amount')
            ->where('status', '!=', 'cancelled');

        if ($request->filled('order_no')) {
            $query->where('order_number', 'like', '%' . trim((string) $request->order_no) . '%');
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('sales_person_id')) {
            $query->where('user_id', $request->sales_person_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('order_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('order_date', '<=', $request->to_date);
        }

        if ($request->get('payment_state') === 'due') {
            $paidSub = \App\Models\Order::effectivePaidAmountSubquery();
            $query->whereRaw("total_amount > {$paidSub}");
        } elseif ($request->get('payment_state') === 'paid') {
            $paidSub = \App\Models\Order::effectivePaidAmountSubquery();
            $query->whereRaw("total_amount <= {$paidSub}");
        }

        $orders = (clone $query)
            ->orderByDesc('order_date')
            ->paginate(25)
            ->withQueryString();

        $summaryRows = (clone $query)
            ->withSum(['payments as payments_sum_amount' => fn ($q) => $q->effectiveForOrderPaid()], 'amount')
            ->get(['orders.id', 'orders.total_amount']);
        $ordersWithDue = $summaryRows->filter(function ($order) {
            $paid = (float) ($order->payments_sum_amount ?? 0);

            return ((float) $order->total_amount - $paid) > 0.00001;
        });
        $totalOutstanding = (float) $ordersWithDue->sum(function ($order) {
            $paid = (float) ($order->payments_sum_amount ?? 0);

            return max(0, (float) $order->total_amount - $paid);
        });
        $dueOrderCount = $ordersWithDue->count();

        $salesPersons = User::where('role', 'sales_person')->orderBy('name')->get(['id', 'name']);
        $customers = Customer::orderBy('name')->get(['id', 'name', 'phone']);

        return view('erp.payment-management.index', compact(
            'orders',
            'salesPersons',
            'customers',
            'totalOutstanding',
            'dueOrderCount'
        ));
    }

    /**
     * Cheque (check) payments: one row per cheque; filter by cheque realization date and payment date.
     */
    public function chequePayments(Request $request)
    {
        $with = [
            'order' => function ($q) {
                $q->with(['customer', 'salesPerson']);
            },
        ];
        if (\Illuminate\Support\Facades\Schema::hasTable('cheque_batches')) {
            $with[] = 'chequeBatch';
        }

        $query = Payment::query()
            ->customerPayments()
            ->whereRaw('LOWER(TRIM(payment_method)) = ?', ['cheque'])
            ->with($with);

        if ($request->filled('cheque_date_from')) {
            $query->whereDate('cheque_date', '>=', $request->cheque_date_from);
        }

        if ($request->filled('cheque_date_to')) {
            $query->whereDate('cheque_date', '<=', $request->cheque_date_to);
        }

        if ($request->filled('payment_date_from')) {
            $query->whereDate('payment_date', '>=', $request->payment_date_from);
        }

        if ($request->filled('payment_date_to')) {
            $query->whereDate('payment_date', '<=', $request->payment_date_to);
        }

        if ($request->filled('order_no')) {
            $needle = '%' . trim((string) $request->order_no) . '%';
            $query->whereHas('order', function ($q) use ($needle) {
                $q->where('order_number', 'like', $needle);
            });
        }

        if ($request->filled('customer_id')) {
            $query->whereHas('order', function ($q) use ($request) {
                $q->where('customer_id', $request->customer_id);
            });
        }

        if ($request->filled('cheque_status')) {
            $query->where('cheque_status', $request->cheque_status);
        }

        $chequeCount = (clone $query)->count();
        $chequeAmountSum = (float) (clone $query)->sum('amount');

        $payments = (clone $query)
            ->orderBy('cheque_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $customers = Customer::orderBy('name')->get(['id', 'name', 'phone']);

        return view('erp.payment-management.cheques', compact(
            'payments',
            'customers',
            'chequeCount',
            'chequeAmountSum'
        ));
    }
}
