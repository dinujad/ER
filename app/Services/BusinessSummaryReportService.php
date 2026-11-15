<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ManufacturingJob;
use App\Models\ManufacturingJobAdditionalCost;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use Illuminate\Http\Request;

class BusinessSummaryReportService
{
    /** @return array<string, mixed> */
    public function build(string $from, string $to, Request $request): array
    {
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $orders = Order::query()
            ->countedInSales()
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('order_date', [$from . ' 00:00:00', $to . ' 23:59:59'])
                    ->orWhere(function ($fallback) use ($from, $to) {
                        $fallback->whereNull('order_date')
                            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);
                    });
            })
            ->get();

        $orderCount = $orders->count();

        $collections = Payment::query()
            ->customerPayments()
            ->whereDate('payment_date', '>=', $from)
            ->whereDate('payment_date', '<=', $to)
            ->sum('amount');
        $refunds = Payment::query()
            ->customerRefunds()
            ->whereDate('payment_date', '>=', $from)
            ->whereDate('payment_date', '<=', $to)
            ->sum('amount');
        $collections = round((float) $collections - (float) $refunds, 2);

        $salesTotal = $collections;

        $purchases = Purchase::query()
            ->reportable()
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->get();

        $purchaseTotal = round((float) $purchases->sum('total'), 2);
        $purchasePaid = round((float) $purchases->sum(fn ($p) => $p->total_paid), 2);

        $purchasePayments = PurchasePayment::query()
            ->whereHas('purchase', fn ($q) => $q->reportable())
            ->whereDate('payment_date', '>=', $from)
            ->whereDate('payment_date', '<=', $to)
            ->sum('amount');
        $purchasePayments = round((float) $purchasePayments, 2);

        $expenses = Expense::query()
            ->with('category')
            ->whereBetween('expense_date', [$from, $to])
            ->orderBy('expense_date')
            ->get();

        $expenseTotal = round((float) $expenses->sum('amount'), 2);
        $expensesByCategory = $expenses
            ->groupBy(fn ($e) => $e->category?->name ?? 'Uncategorized')
            ->map(fn ($group) => round((float) $group->sum('amount'), 2))
            ->sortDesc();

        $jobs = ManufacturingJob::query()
            ->with('additionalCosts')
            ->where(function ($q) use ($from, $to) {
                $q->whereDate('issue_date', '>=', $from)
                    ->whereDate('issue_date', '<=', $to)
                    ->orWhereBetween('updated_at', [$from . ' 00:00:00', $to . ' 23:59:59']);
            })
            ->get();

        $productionCost = 0.0;
        $productionFabric = 0.0;
        $productionAccessory = 0.0;
        $productionAdditional = 0.0;
        $transportCost = 0.0;
        $sewingCost = 0.0;
        $washingCost = 0.0;

        foreach ($jobs as $job) {
            $breakdown = $job->costBreakdown();
            $productionFabric += $breakdown['fabric'];
            $productionAccessory += $breakdown['accessory'];
            $productionAdditional += $breakdown['additional_total'];
            $productionCost += $breakdown['total'];

            $transportCost += (float) $job->additionalCosts->where('category', ManufacturingJobAdditionalCost::CAT_TRANSPORT)->sum('amount');
            $sewingCost += (float) $job->additionalCosts->where('category', ManufacturingJobAdditionalCost::CAT_SEWING)->sum('amount');
            $washingCost += (float) $job->additionalCosts->where('category', ManufacturingJobAdditionalCost::CAT_WASHING)->sum('amount');
        }

        $productionCost = round($productionCost, 2);
        $productionFabric = round($productionFabric, 2);
        $productionAccessory = round($productionAccessory, 2);
        $productionAdditional = round($productionAdditional, 2);
        $transportCost = round($transportCost, 2);
        $sewingCost = round($sewingCost, 2);
        $washingCost = round($washingCost, 2);

        $totalOutflow = round($purchaseTotal + $expenseTotal + $productionAdditional, 2);
        $estimatedMargin = round($salesTotal - $purchaseTotal - $expenseTotal - $productionAdditional, 2);

        return [
            'filters' => ['from' => $from, 'to' => $to],
            'sales' => [
                'total' => $salesTotal,
                'orders' => $orderCount,
                'collected' => $collections,
                'outstanding' => max(0, round($salesTotal - $collections, 2)),
            ],
            'purchases' => [
                'total' => $purchaseTotal,
                'paid' => $purchasePaid,
                'payments_in_period' => $purchasePayments,
                'count' => $purchases->count(),
            ],
            'expenses' => [
                'total' => $expenseTotal,
                'count' => $expenses->count(),
                'by_category' => $expensesByCategory,
                'items' => $expenses,
            ],
            'production' => [
                'job_count' => $jobs->count(),
                'total_cost' => $productionCost,
                'fabric' => $productionFabric,
                'accessory' => $productionAccessory,
                'additional' => $productionAdditional,
                'transport' => $transportCost,
                'sewing' => $sewingCost,
                'washing' => $washingCost,
            ],
            'summary' => [
                'total_inflow' => $salesTotal,
                'total_outflow_estimate' => $totalOutflow,
                'estimated_margin' => $estimatedMargin,
            ],
        ];
    }
}
