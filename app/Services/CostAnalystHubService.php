<?php

namespace App\Services;

use App\Models\ManufacturingJob;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Cost Analyst hub — completed manufacturing jobs, pricing, sales history.
 */
class CostAnalystHubService
{
    public function __construct(
        private readonly ProductFinalCostReportService $finalCost,
        private readonly CostAnalystAdvisorService $advisor
    ) {}

    /** @return array<string, mixed> */
    public function dashboard(string $from, string $to, Request $request): array
    {
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $marginPercent = $this->marginFromRequest($request);
        $jobs = $this->completedJobsQuery($from, $to, $request)->limit(100)->get();

        $rows = $jobs->map(function (ManufacturingJob $job) use ($marginPercent) {
            $report = $this->finalCost->buildForJob($job);
            $actualPerUnit = (float) ($report['actual']['per_unit'] ?? 0);
            $suggested = $this->advisor->suggestedSellPrice($actualPerUnit, $marginPercent);

            return array_merge($report, [
                'margin_percent' => $marginPercent,
                'suggested_sell_price' => $suggested['price'],
                'suggested_profit_per_unit' => $suggested['profit_per_unit'],
                'historical_avg_sell' => $this->historicalAvgSellPrice($job->product_id),
            ]);
        })->values()->all();

        return [
            'filters' => [
                'from' => $from,
                'to' => $to,
                'margin_percent' => $marginPercent,
                'status' => $request->get('status', 'all'),
            ],
            'rows' => $rows,
            'summary' => [
                'job_count' => count($rows),
                'avg_cost_per_unit' => count($rows) > 0
                    ? round(collect($rows)->avg(fn ($r) => $r['actual']['per_unit'] ?? 0), 2)
                    : 0,
                'avg_suggested_price' => count($rows) > 0
                    ? round(collect($rows)->avg(fn ($r) => $r['suggested_sell_price'] ?? 0), 2)
                    : 0,
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function jobDetail(ManufacturingJob $job, Request $request): array
    {
        $marginPercent = $this->marginFromRequest($request);
        $report = $this->finalCost->buildForJob($job);
        $actualPerUnit = (float) ($report['actual']['per_unit'] ?? 0);
        $suggested = $this->advisor->suggestedSellPrice($actualPerUnit, $marginPercent);
        $salesHistory = $this->productSalesHistory($job->product_id);
        $advisory = $this->advisor->buildAdvisory($job, $report, $marginPercent, $salesHistory);

        return array_merge($report, [
            'margin_percent' => $marginPercent,
            'suggested_sell_price' => $suggested['price'],
            'suggested_profit_per_unit' => $suggested['profit_per_unit'],
            'markup_amount' => $suggested['markup_amount'],
            'historical_avg_sell' => $this->historicalAvgSellPrice($job->product_id),
            'sales_history' => $salesHistory,
            'advisory' => $advisory,
        ]);
    }

    private function marginFromRequest(Request $request): float
    {
        $default = (float) config('cost_analyst.default_margin_percent', 35);
        $margin = (float) $request->get('margin', $default);
        $min = (float) config('cost_analyst.min_margin_percent', 5);
        $max = (float) config('cost_analyst.max_margin_percent', 200);

        return max($min, min($max, $margin));
    }

    private function completedJobsQuery(string $from, string $to, Request $request)
    {
        $query = ManufacturingJob::query()
            ->with(['product.latestRecipe', 'packingBatches'])
            ->whereNotNull('product_id')
            ->where(function ($q) use ($from, $to) {
                $q->whereHas('packingBatches', function ($b) use ($from, $to) {
                    $b->where('type', 'good')
                        ->whereIn('status', ['sent_to_final_check', 'completed'])
                        ->whereDate('updated_at', '>=', $from)
                        ->whereDate('updated_at', '<=', $to);
                })->orWhereIn('status', [
                    ManufacturingJob::STATUS_WAREHOUSE_RECEIVED,
                    ManufacturingJob::STATUS_SOLD,
                    ManufacturingJob::STATUS_JOB_ENDED,
                ]);
            });

        if ($request->get('status') === 'converted') {
            $query->whereHas('packingBatches', fn ($b) => $b->where('type', 'good')->where('status', 'completed'));
        }

        return $query->orderByDesc('updated_at');
    }

    private function historicalAvgSellPrice(?int $productId): ?float
    {
        if (!$productId) {
            return null;
        }

        $avg = OrderItem::query()
            ->where('product_id', $productId)
            ->whereHas('order', fn ($q) => $q->countedInSales())
            ->avg('unit_price');

        return $avg !== null ? round((float) $avg, 2) : null;
    }

    /** @return array{labels: array<int, string>, quantities: array<int, float>, avg_prices: array<int, float>, totals: array<int, float>} */
    public function productSalesHistory(?int $productId): array
    {
        $empty = ['labels' => [], 'quantities' => [], 'avg_prices' => [], 'totals' => []];
        if (!$productId) {
            return $empty;
        }

        $months = (int) config('cost_analyst.sales_history_months', 12);
        $from = now()->subMonths($months)->startOfMonth();

        $rows = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.product_id', $productId)
            ->whereIn('orders.status', [
                Order::STATUS_COMPLETED,
                Order::STATUS_PAID,
                Order::STATUS_CREDIT,
            ])
            ->where(function ($q) use ($from) {
                $q->where('orders.order_date', '>=', $from)
                    ->orWhere(function ($f) use ($from) {
                        $f->whereNull('orders.order_date')->where('orders.created_at', '>=', $from);
                    });
            })
            ->select([
                DB::raw("DATE_FORMAT(COALESCE(orders.order_date, orders.created_at), '%Y-%m') as month_key"),
                DB::raw('SUM(order_items.quantity) as total_qty'),
                DB::raw('SUM(order_items.quantity * order_items.unit_price) as total_amount'),
                DB::raw('AVG(order_items.unit_price) as avg_price'),
            ])
            ->groupBy('month_key')
            ->orderBy('month_key')
            ->get();

        return [
            'labels' => $rows->pluck('month_key')->map(fn ($m) => \Carbon\Carbon::parse($m . '-01')->format('M Y'))->all(),
            'quantities' => $rows->pluck('total_qty')->map(fn ($v) => (float) $v)->all(),
            'avg_prices' => $rows->pluck('avg_price')->map(fn ($v) => round((float) $v, 2))->all(),
            'totals' => $rows->pluck('total_amount')->map(fn ($v) => round((float) $v, 2))->all(),
        ];
    }
}
