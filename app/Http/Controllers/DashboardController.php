<?php

namespace App\Http\Controllers;

use App\Models\ManufacturingJob;
use App\Services\DashboardProductionSummary;
use App\Models\Material;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\QcCheck;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected function getDateRange(string $duration): array
    {
        $today = Carbon::today();
        switch ($duration) {
            case 'today':
                return [$today->copy(), $today->copy()];
            case 'yesterday':
                $y = $today->copy()->subDay();
                return [$y, $y];
            case 'week':
                return [$today->copy()->subDays(6), $today->copy()];
            case 'month':
                return [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()];
            case 'year':
                return [$today->copy()->startOfYear(), $today->copy()->endOfYear()];
            default:
                return [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()];
        }
    }

    protected function getDurationLabel(string $duration): string
    {
        return match ($duration) {
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'week' => 'Last 7 Days',
            'month' => 'This Month',
            'year' => 'This Year',
            default => 'This Month',
        };
    }

    public function index(Request $request)
    {
        $duration = $request->get('duration', 'month');
        if (!in_array($duration, ['today', 'yesterday', 'week', 'month', 'year'], true)) {
            $duration = 'month';
        }
        [$from, $to] = $this->getDateRange($duration);
        $fromStr = $from->format('Y-m-d');
        $toStr = $to->format('Y-m-d');

        // Ongoing jobs (active manufacturing — not ended / warehouse)
        $ongoingStatuses = [
            'FABRIC_ISSUED', 'SENT_TO_CUTTING', 'CUTTING_IN_PROGRESS', 'CUTTING_COMPLETED',
            'LINE_ASSIGNED', 'LINE_IN_PROGRESS', 'WASHING_OUT', 'AFTER_WASH_RECEIVED', 'PACKING_COMPLETED',
        ];
        $ongoingJobsQuery = ManufacturingJob::whereIn('status', $ongoingStatuses);
        $ongoingJobsCount = $ongoingJobsQuery->count();
        $ongoingJobs = (clone $ongoingJobsQuery)->with(['product', 'hourlyProductions'])
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get();

        // Inventory summary (not time-filtered)
        $materialsCount = Material::count();
        $materialsLowStock = Material::where('quantity', '<=', 10)->count();
        $productsCount = Product::count();
        $totalMaterialsValue = Material::selectRaw('COALESCE(SUM(quantity * COALESCE(unit_price, 0)), 0) as total')->value('total');

        // Purchases in period (confirmed only)
        $purchaseAmount = (float) Purchase::where('status', Purchase::STATUS_CONFIRMED)
            ->whereDate('date', '>=', $fromStr)
            ->whereDate('date', '<=', $toStr)
            ->sum('total');
        $purchasesInPeriod = Purchase::with('payments')->where('status', Purchase::STATUS_CONFIRMED)
            ->whereDate('date', '>=', $fromStr)
            ->whereDate('date', '<=', $toStr)
            ->get();
        $duePurchase = $purchasesInPeriod->sum(fn ($p) => $p->balance);

        // Sales = payments received in period minus customer refunds
        $paymentsInPeriod = Payment::query()
            ->customerPayments()
            ->whereDate('payment_date', '>=', $fromStr)
            ->whereDate('payment_date', '<=', $toStr);

        $refundsInPeriod = Payment::query()
            ->customerRefunds()
            ->whereDate('payment_date', '>=', $fromStr)
            ->whereDate('payment_date', '<=', $toStr);

        $salesTotal = (float) (clone $paymentsInPeriod)->sum('amount') - (float) (clone $refundsInPeriod)->sum('amount');
        $salesWholesale = (float) (clone $paymentsInPeriod)
            ->whereHas('order.customer', fn ($q) => $q->where('customer_type', 'wholesale'))
            ->sum('amount')
            - (float) (clone $refundsInPeriod)
                ->whereHas('order.customer', fn ($q) => $q->where('customer_type', 'wholesale'))
                ->sum('amount');
        $salesRetail = (float) (clone $paymentsInPeriod)
            ->whereHas('order.customer', fn ($q) => $q->where('customer_type', 'retail'))
            ->sum('amount')
            - (float) (clone $refundsInPeriod)
                ->whereHas('order.customer', fn ($q) => $q->where('customer_type', 'retail'))
                ->sum('amount');

        // Legacy / report summary
        $totalSales = Sale::sum('total_amount');
        $ordersPending = Order::where('status', 'pending')->count();
        $ordersThisMonth = Payment::query()
            ->customerPayments()
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->distinct()
            ->count('order_id');
        $ordersTotalAmount = (float) Payment::query()
            ->customerPayments()
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount')
            - (float) Payment::query()
                ->customerRefunds()
                ->whereMonth('payment_date', now()->month)
                ->whereYear('payment_date', now()->year)
                ->sum('amount');
        $finishedGoods = QcCheck::sum('finished_good_qty');
        $damagedGoods = QcCheck::sum('damaged_qty');

        $durationLabel = $this->getDurationLabel($duration);

        $productionSummary = app(DashboardProductionSummary::class)->build($fromStr, $toStr);

        // Keep query simple (same filters as before) — dashboard only shows order numbers.
        $urgentOrders = Order::query()
            ->where('is_urgent', true)
            ->whereNotIn('status', [
                Order::STATUS_QUOTATION,
                Order::STATUS_CANCELLED,
                Order::STATUS_COMPLETED,
                Order::STATUS_DRAFT,
                Order::STATUS_SUSPENDED,
            ])
            ->latest()
            ->limit(20)
            ->get(['id', 'order_number']);

        return view('erp.dashboard', compact(
            'productionSummary',
            'ongoingJobsCount',
            'ongoingJobs',
            'materialsCount',
            'materialsLowStock',
            'productsCount',
            'totalMaterialsValue',
            'totalSales',
            'ordersPending',
            'ordersThisMonth',
            'ordersTotalAmount',
            'finishedGoods',
            'damagedGoods',
            'duration',
            'durationLabel',
            'purchaseAmount',
            'duePurchase',
            'salesTotal',
            'salesWholesale',
            'salesRetail',
            'urgentOrders'
        ));
    }
}
