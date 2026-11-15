<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SalesPersonExpense;
use App\Models\SalesRouteRun;
use App\Services\RouteCashSettlementService;
use App\Services\SalesPersonCommissionService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SalesDashboardController extends Controller
{
    public function __construct(
        protected SalesPersonCommissionService $commissionService
    ) {}

    public function index()
    {
        $userId = auth()->id();
        $customersCount = auth()->user()->customers()->count();
        $ordersQuery = Order::visibleToSalesPerson($userId);
        $ordersCount = (clone $ordersQuery)->count();
        $ordersThisMonth = (clone $ordersQuery)
            ->whereMonth('order_date', now()->month)
            ->whereYear('order_date', now()->year)
            ->count();
        $recentOrders = (clone $ordersQuery)
            ->with('customer')
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $expenseMonthStart = now()->startOfMonth()->toDateString();
        $expenseMonthEnd = now()->endOfMonth()->toDateString();
        $expensesThisMonthTotal = (float) SalesPersonExpense::query()
            ->where('user_id', $userId)
            ->whereBetween('expense_date', [$expenseMonthStart, $expenseMonthEnd])
            ->sum('amount');
        $recentExpenses = SalesPersonExpense::query()
            ->where('user_id', $userId)
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $routeRuns = app(\App\Services\SalesRouteRunService::class);
        $assignedRoute = auth()->user()->salesRoute;
        $activeRouteRun = $routeRuns->activeRunFor(auth()->user());
        $cashRun = null;
        $routeCash = null;
        if ($routeRuns->isReady()) {
            $cashRun = $activeRouteRun ?: SalesRouteRun::query()
                ->with('salesRoute')
                ->where('user_id', $userId)
                ->latest('started_at')
                ->first();
            $routeCash = $cashRun ? app(RouteCashSettlementService::class)->forRun($cashRun) : null;
        }

        $pendingChequeBase = \App\Models\Payment::query()
            ->customerPayments()
            ->pendingBankDeposit()
            ->whereHas('order', function ($q) use ($userId) {
                $q->visibleToSalesPerson($userId)
                    ->where('status', '!=', 'cancelled');
            });

        // Compute totals over ALL pending cheques (no limit)
        $pendingChequeAmount = round((float) (clone $pendingChequeBase)->sum('amount'), 2);
        $pendingChequeCount = (clone $pendingChequeBase)->count();

        // Load records for display (limit to 50 most overdue)
        $pendingChequePayments = (clone $pendingChequeBase)
            ->with([
                'order:id,order_number,customer_id,user_id,order_date,status',
                'order.customer:id,name,business_name,phone,customer_type,added_by',
            ])
            ->orderByRaw('COALESCE(cheque_date, payment_date) ASC')
            ->limit(50)
            ->get();

        $pendingChequesByCustomer = $pendingChequePayments
            ->groupBy(fn ($p) => $p->order?->customer_id ?? 0)
            ->map(function ($group) {
                $first = $group->first();
                $customer = $first->order?->customer;

                return (object) [
                    'customer' => $customer,
                    'business_name' => $customer?->display_name ?? '—',
                    'cheque_count' => $group->count(),
                    'amount' => round((float) $group->sum('amount'), 2),
                ];
            })
            ->sortByDesc('amount')
            ->values();

        return view('sales_person.dashboard', compact(
            'customersCount',
            'ordersCount',
            'ordersThisMonth',
            'recentOrders',
            'expensesThisMonthTotal',
            'recentExpenses',
            'pendingChequePayments',
            'pendingChequeAmount',
            'pendingChequeCount',
            'pendingChequesByCustomer',
            'assignedRoute',
            'activeRouteRun',
            'cashRun',
            'routeCash'
        ));
    }

    public function sales(Request $request)
    {
        $userId = auth()->id();
        $month = $request->input('month', now()->format('Y-m'));

        try {
            $startDate = Carbon::parse($month . '-01')->startOfMonth();
        } catch (\Throwable) {
            $month = now()->format('Y-m');
            $startDate = now()->startOfMonth();
        }
        $endDate = $startDate->copy()->endOfMonth();

        $orders = Order::query()
            ->visibleToSalesPerson($userId)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('order_date', [$startDate, $endDate])
            ->with(['customer', 'payments'])
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->get();

        $totalSales = (float) $orders->sum('total_amount');
        $orderCount = $orders->count();
        // Use effectiveForOrderPaid to exclude bounced cheques from paid total
        $totalPaid = round((float) \App\Models\Payment::query()
            ->effectiveForOrderPaid()
            ->whereIn('order_id', $orders->pluck('id'))
            ->sum('amount'), 2);
        $totalDue = max(0, round($totalSales - $totalPaid, 2));

        $monthOptions = collect(range(0, 11))->map(function ($i) {
            $d = now()->subMonths($i);

            return [
                'value' => $d->format('Y-m'),
                'label' => $d->format('F Y'),
            ];
        });

        $commissionSummary = $this->commissionService->summaryForUser($userId);
        $monthCommission = $this->commissionService->forMonth($userId, $month);

        return view('sales_person.sales.index', compact(
            'orders',
            'month',
            'monthOptions',
            'startDate',
            'totalSales',
            'orderCount',
            'totalPaid',
            'totalDue',
            'commissionSummary',
            'monthCommission'
        ));
    }
}
