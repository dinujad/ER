<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PendingChequesReportService
{
    /**
     * Deep report: cheques received from wholesale customers but not yet deposited to our bank.
     */
    public function build(Request $request): array
    {
        $query = Payment::query()
            ->pendingBankDeposit()
            ->with([
                'order.customer',
                'order.salesPerson:id,name,role',
            ])
            ->whereHas('order', function ($q) {
                $q->where('status', '!=', 'cancelled')
                    ->whereHas('customer', fn ($c) => $c->where('customer_type', 'wholesale'));
            });

        if ($request->filled('customer_id')) {
            $query->whereHas('order', fn ($q) => $q->where('customer_id', $request->customer_id));
        }

        if ($request->filled('sales_person_id')) {
            $query->whereHas('order', fn ($q) => $q->where('user_id', $request->sales_person_id));
        }

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

        if ($request->filled('overdue_days') && is_numeric($request->overdue_days)) {
            $days = (int) $request->overdue_days;
            $cutoff = now()->subDays($days)->format('Y-m-d');
            $query->where(function ($q) use ($cutoff) {
                $q->whereDate('cheque_date', '<=', $cutoff)
                    ->orWhere(function ($q2) use ($cutoff) {
                        $q2->whereNull('cheque_date')->whereDate('payment_date', '<=', $cutoff);
                    });
            });
        }

        $payments = $query
            ->orderByRaw('COALESCE(cheque_date, payment_date) ASC')
            ->orderBy('id')
            ->get();

        $rows = $payments->map(function (Payment $payment) {
            $order = $payment->order;
            $customer = $order?->customer;
            $chequeDate = $payment->cheque_date ?? $payment->payment_date;
            $daysPending = null;
            if ($chequeDate) {
                $daysPending = (int) $chequeDate->copy()->startOfDay()->diffInDays(now()->startOfDay(), false);
            }

            return (object) [
                'payment' => $payment,
                'order' => $order,
                'customer' => $customer,
                'business_name' => $customer?->display_name ?? '—',
                'contact_name' => $customer?->name ?? '—',
                'sales_person' => ($order?->salesPerson && $order->salesPerson->role === 'sales_person')
                    ? $order->salesPerson->name
                    : '—',
                'days_pending' => $daysPending,
                'is_overdue' => $daysPending !== null && $daysPending > 0,
            ];
        });

        $byCustomer = $rows
            ->groupBy(fn ($row) => $row->customer?->id ?? 0)
            ->map(function (Collection $group) {
                $first = $group->first();

                return (object) [
                    'customer' => $first->customer,
                    'business_name' => $first->business_name,
                    'cheque_count' => $group->count(),
                    'amount' => round($group->sum(fn ($r) => (float) $r->payment->amount), 2),
                ];
            })
            ->sortByDesc('amount')
            ->values();

        return [
            'rows' => $rows,
            'byCustomer' => $byCustomer,
            'totalAmount' => round($rows->sum(fn ($r) => (float) $r->payment->amount), 2),
            'chequeCount' => $rows->count(),
            'customerCount' => $byCustomer->count(),
            'customers' => Customer::wholesale()
                ->orderByRaw("COALESCE(NULLIF(TRIM(business_name), ''), name)")
                ->get(['id', 'name', 'business_name', 'phone']),
            'salesPersons' => User::where('role', 'sales_person')->orderBy('name')->get(['id', 'name']),
        ];
    }
}
