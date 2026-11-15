<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class SalesPersonCreditReportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:100',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $q = trim((string) ($filters['q'] ?? ''));
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;
        $creditOnly = $request->exists('credit_only')
            ? $request->boolean('credit_only')
            : true;

        $query = Order::query()
            ->visibleToSalesPerson((int) auth()->id())
            ->countedInSales()
            ->with(['customer', 'payments', 'saleReturns'])
            ->orderByDesc('order_date')
            ->orderByDesc('id');

        if ($from) {
            $query->whereDate('order_date', '>=', $from);
        }
        if ($to) {
            $query->whereDate('order_date', '<=', $to);
        }
        if ($q !== '') {
            $query->where(function ($inner) use ($q) {
                $inner->where('order_number', 'like', '%'.$q.'%')
                    ->orWhere('invoice_number', 'like', '%'.$q.'%')
                    ->orWhereHas('customer', function ($customer) use ($q) {
                        $customer->where('business_name', 'like', '%'.$q.'%')
                            ->orWhere('name', 'like', '%'.$q.'%');
                    });
            });
        }

        $rows = $query->get()->map(function (Order $order) {
            $paid = round((float) $order->payments
                ->filter(fn (Payment $payment) => $payment->entry_type === null || $payment->entry_type === Payment::ENTRY_PAYMENT)
                ->filter(fn (Payment $payment) => $payment->countsTowardPaid())
                ->sum(fn (Payment $payment) => $payment->effectiveSettlementAmount()), 2);
            $refunded = round((float) $order->payments
                ->filter(fn (Payment $payment) => $payment->isRefund())
                ->sum('amount'), 2);
            $returned = round((float) $order->saleReturns->sum('total'), 2);
            $net = max(0, round((float) $order->total_amount - $returned, 2));
            $credit = max(0, round($net - $paid + $refunded - (float) ($order->claim_credit_amount ?? 0), 2));

            return (object) [
                'order' => $order,
                'business' => $order->customer?->display_name ?: '—',
                'net' => $net,
                'paid' => $paid,
                'credit' => $credit,
            ];
        });

        if ($creditOnly) {
            $rows = $rows->filter(fn ($row) => $row->credit > 0.009)->values();
        }

        $byBusiness = $rows
            ->groupBy(fn ($row) => $row->order->customer_id ?: 0)
            ->map(function ($group) {
                $first = $group->first();

                return (object) [
                    'business' => $first->business,
                    'orders' => $group->count(),
                    'net' => round((float) $group->sum('net'), 2),
                    'paid' => round((float) $group->sum('paid'), 2),
                    'credit' => round((float) $group->sum('credit'), 2),
                ];
            })
            ->sortByDesc('credit')
            ->values();

        $summary = (object) [
            'businesses' => $byBusiness->count(),
            'orders' => $rows->count(),
            'net' => round((float) $rows->sum('net'), 2),
            'paid' => round((float) $rows->sum('paid'), 2),
            'credit' => round((float) $rows->sum('credit'), 2),
        ];

        $page = max(1, (int) $request->input('page', 1));
        $perPage = 25;
        $orders = new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('sales_person.credit-report.index', compact(
            'orders',
            'byBusiness',
            'summary',
            'q',
            'from',
            'to',
            'creditOnly'
        ));
    }
}
