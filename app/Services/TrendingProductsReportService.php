<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TrendingProductsReportService
{
    public const CHANNEL_ALL = 'all';

    public const CHANNEL_RETAIL = 'retail';

    public const CHANNEL_WHOLESALE = 'wholesale';

    public function __construct(
        private readonly TrendingProductsAdvisorService $advisor,
        private readonly CostAnalystHubService $salesHistory
    ) {}

    /** @return array<string, mixed> */
    public function buildIndex(string $from, string $to, Request $request): array
    {
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $days = max(1, Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1);
        $prevTo = Carbon::parse($from)->subDay()->format('Y-m-d');
        $prevFrom = Carbon::parse($from)->subDays($days)->format('Y-m-d');
        $channel = $this->channel($request);

        $current = $this->aggregateByProduct($from, $to, $channel);
        $previous = $this->aggregateByProduct($prevFrom, $prevTo, $channel)->keyBy('product_id');
        $variationRows = $this->aggregateByProductVariation($from, $to, $channel);

        $products = Product::query()
            ->with(['category', 'variations'])
            ->whereIn('id', $current->pluck('product_id'))
            ->get()
            ->keyBy('id');

        $rows = [];
        $rank = 0;
        foreach ($current->sortByDesc('total_qty')->values() as $row) {
            $rank++;
            $productId = (int) $row->product_id;
            $product = $products->get($productId);
            if (!$product) {
                continue;
            }

            $prev = $previous->get($productId);
            $prevQty = (float) ($prev->total_qty ?? 0);
            $totalQty = (float) $row->total_qty;
            $growth = $prevQty > 0
                ? round((($totalQty - $prevQty) / $prevQty) * 100, 1)
                : ($totalQty > 0 ? 100.0 : 0.0);

            $variations = $variationRows
                ->where('product_id', $productId)
                ->sortByDesc('total_qty')
                ->values()
                ->map(fn ($v) => [
                    'variation_id' => $v->product_variation_id,
                    'variation_name' => $v->variation_name ?? '—',
                    'sub_sku' => $v->sub_sku ?? '',
                    'total_qty' => (float) $v->total_qty,
                    'retail_qty' => (float) ($v->retail_qty ?? 0),
                    'wholesale_qty' => (float) ($v->wholesale_qty ?? 0),
                    'total_sales' => round((float) $v->total_sales, 2),
                    'avg_price' => round((float) $v->avg_price, 2),
                    'share_percent' => $totalQty > 0 ? round(((float) $v->total_qty / $totalQty) * 100, 1) : 0,
                ])
                ->all();

            $topVar = $variations[0]['variation_name'] ?? null;

            $rows[] = [
                'rank' => $rank,
                'product_id' => $productId,
                'product' => $product,
                'total_qty' => $totalQty,
                'retail_qty' => (float) ($row->retail_qty ?? 0),
                'wholesale_qty' => (float) ($row->wholesale_qty ?? 0),
                'total_sales' => round((float) $row->total_sales, 2),
                'avg_unit_price' => round((float) $row->avg_price, 2),
                'order_count' => (int) $row->order_count,
                'prev_qty' => $prevQty,
                'growth_percent' => $growth,
                'variations' => $variations,
                'top_variation_name' => $topVar,
                'is_trending' => $growth >= (float) config('trending_products.rising_growth_percent', 10) || $rank <= 5,
            ];
        }

        return [
            'filters' => [
                'from' => $from,
                'to' => $to,
                'prev_from' => $prevFrom,
                'prev_to' => $prevTo,
                'channel' => $channel,
                'channel_label' => $this->channelLabel($channel),
            ],
            'rows' => $rows,
            'summary' => [
                'product_count' => count($rows),
                'total_qty' => round(collect($rows)->sum('total_qty'), 2),
                'retail_qty' => round(collect($rows)->sum('retail_qty'), 2),
                'wholesale_qty' => round(collect($rows)->sum('wholesale_qty'), 2),
                'total_sales' => round(collect($rows)->sum('total_sales'), 2),
                'trending_count' => collect($rows)->where('is_trending', true)->count(),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function buildProductDetail(Product $product, string $from, string $to, Request $request): array
    {
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $days = max(1, Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1);
        $prevTo = Carbon::parse($from)->subDay()->format('Y-m-d');
        $prevFrom = Carbon::parse($from)->subDays($days)->format('Y-m-d');
        $channel = $this->channel($request);

        $product->load(['category', 'brand', 'variations']);

        $currentAgg = $this->aggregateByProduct($from, $to, $channel)->firstWhere('product_id', $product->id);
        $prevAgg = $this->aggregateByProduct($prevFrom, $prevTo, $channel)->firstWhere('product_id', $product->id);

        $totalQty = (float) ($currentAgg->total_qty ?? 0);
        $prevQty = (float) ($prevAgg->total_qty ?? 0);
        $growth = $prevQty > 0
            ? round((($totalQty - $prevQty) / $prevQty) * 100, 1)
            : ($totalQty > 0 ? 100.0 : 0.0);

        $allCurrent = $this->aggregateByProduct($from, $to, $channel);
        $rank = 1;
        foreach ($allCurrent->sortByDesc('total_qty')->values() as $i => $row) {
            if ((int) $row->product_id === (int) $product->id) {
                $rank = $i + 1;
                break;
            }
        }

        $variationStats = $this->aggregateByProductVariation($from, $to, $channel)
            ->where('product_id', $product->id)
            ->sortByDesc('total_qty')
            ->values()
            ->map(fn ($v) => [
                'variation_id' => $v->product_variation_id,
                'variation_name' => $v->variation_name ?? '—',
                'sub_sku' => $v->sub_sku ?? '',
                'total_qty' => (float) $v->total_qty,
                'retail_qty' => (float) ($v->retail_qty ?? 0),
                'wholesale_qty' => (float) ($v->wholesale_qty ?? 0),
                'total_sales' => round((float) $v->total_sales, 2),
                'avg_price' => round((float) $v->avg_price, 2),
            ])
            ->all();

        $orderLines = $this->orderLinesForProduct($product->id, $from, $to, $channel);
        $buyers = $this->topBuyers($orderLines);

        $salesHistory = $this->salesHistory->productSalesHistory($product->id);

        $stats = [
            'total_qty' => $totalQty,
            'retail_qty' => (float) ($currentAgg->retail_qty ?? 0),
            'wholesale_qty' => (float) ($currentAgg->wholesale_qty ?? 0),
            'total_sales' => round((float) ($currentAgg->total_sales ?? 0), 2),
            'avg_unit_price' => round((float) ($currentAgg->avg_price ?? 0), 2),
            'prev_qty' => $prevQty,
            'growth_percent' => $growth,
            'rank' => $rank,
            'order_count' => (int) ($currentAgg->order_count ?? 0),
            'top_variation_name' => $variationStats[0]['variation_name'] ?? null,
        ];

        $recommendation = $this->advisor->buildRecommendation($product, $stats, $salesHistory);

        return [
            'filters' => [
                'from' => $from,
                'to' => $to,
                'prev_from' => $prevFrom,
                'prev_to' => $prevTo,
                'channel' => $channel,
                'channel_label' => $this->channelLabel($channel),
            ],
            'product' => $product,
            'stats' => $stats,
            'variations' => $variationStats,
            'order_lines' => $orderLines,
            'buyers' => $buyers,
            'sales_history' => $salesHistory,
            'recommendation' => $recommendation,
        ];
    }

    private function netQtySql(): string
    {
        return 'GREATEST(order_items.quantity - COALESCE(order_items.quantity_returned, 0), 0)';
    }

    private function channel(Request $request): string
    {
        $channel = (string) $request->get('channel', self::CHANNEL_ALL);

        return in_array($channel, [self::CHANNEL_RETAIL, self::CHANNEL_WHOLESALE], true)
            ? $channel
            : self::CHANNEL_ALL;
    }

    private function channelLabel(string $channel): string
    {
        return match ($channel) {
            self::CHANNEL_RETAIL => 'POS Retail',
            self::CHANNEL_WHOLESALE => 'Wholesale orders',
            default => 'All',
        };
    }

    /** Wholesale salesperson orders. POS register sales stay in retail. */
    private function wholesaleOrderSql(): string
    {
        return "orders.register_id IS NULL AND EXISTS (SELECT 1 FROM users WHERE users.id = orders.user_id AND users.role = 'sales_person')";
    }

    private function applyChannel($query, string $channel): void
    {
        $wholesale = $this->wholesaleOrderSql();
        if ($channel === self::CHANNEL_WHOLESALE) {
            $query->whereRaw($wholesale);
        } elseif ($channel === self::CHANNEL_RETAIL) {
            $query->whereRaw('NOT ('.$wholesale.')');
        }
    }

    private function ordersDateScope($query, string $from, string $to, string $channel = self::CHANNEL_ALL): void
    {
        $query->whereNotIn('orders.status', Order::excludedFromSalesStats())
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('orders.order_date', [$from . ' 00:00:00', $to . ' 23:59:59'])
                    ->orWhere(function ($f) use ($from, $to) {
                        $f->whereNull('orders.order_date')
                            ->whereBetween('orders.created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);
                    });
            });
        $this->applyChannel($query, $channel);
    }

    private function aggregateByProduct(string $from, string $to, string $channel = self::CHANNEL_ALL): Collection
    {
        $net = $this->netQtySql();
        $wholesale = $this->wholesaleOrderSql();

        return OrderItem::query()
            ->select([
                'order_items.product_id',
                DB::raw("SUM({$net}) as total_qty"),
                DB::raw("SUM(CASE WHEN {$wholesale} THEN {$net} ELSE 0 END) as wholesale_qty"),
                DB::raw("SUM(CASE WHEN NOT ({$wholesale}) THEN {$net} ELSE 0 END) as retail_qty"),
                DB::raw("SUM({$net} * order_items.unit_price) as total_sales"),
                DB::raw("AVG(order_items.unit_price) as avg_price"),
                DB::raw('COUNT(DISTINCT order_items.order_id) as order_count'),
            ])
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->tap(fn ($q) => $this->ordersDateScope($q, $from, $to, $channel))
            ->groupBy('order_items.product_id')
            ->havingRaw("SUM({$net}) > 0")
            ->get();
    }

    private function aggregateByProductVariation(string $from, string $to, string $channel = self::CHANNEL_ALL): Collection
    {
        $net = $this->netQtySql();
        $wholesale = $this->wholesaleOrderSql();

        return OrderItem::query()
            ->select([
                'order_items.product_id',
                'order_items.product_variation_id',
                'product_variations.variation_name',
                'product_variations.sub_sku',
                DB::raw("SUM({$net}) as total_qty"),
                DB::raw("SUM(CASE WHEN {$wholesale} THEN {$net} ELSE 0 END) as wholesale_qty"),
                DB::raw("SUM(CASE WHEN NOT ({$wholesale}) THEN {$net} ELSE 0 END) as retail_qty"),
                DB::raw("SUM({$net} * order_items.unit_price) as total_sales"),
                DB::raw("AVG(order_items.unit_price) as avg_price"),
            ])
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('product_variations', 'product_variations.id', '=', 'order_items.product_variation_id')
            ->tap(fn ($q) => $this->ordersDateScope($q, $from, $to, $channel))
            ->groupBy(
                'order_items.product_id',
                'order_items.product_variation_id',
                'product_variations.variation_name',
                'product_variations.sub_sku'
            )
            ->havingRaw("SUM({$net}) > 0")
            ->get();
    }

    /** @return array<int, array<string, mixed>> */
    private function orderLinesForProduct(int $productId, string $from, string $to, string $channel = self::CHANNEL_ALL): array
    {
        $net = $this->netQtySql();

        return OrderItem::query()
            ->with(['order.customer', 'order.salesPerson', 'variation'])
            ->select('order_items.*')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.product_id', $productId)
            ->tap(fn ($q) => $this->ordersDateScope($q, $from, $to, $channel))
            ->whereRaw("{$net} > 0")
            ->orderByDesc('orders.order_date')
            ->limit(200)
            ->get()
            ->map(function (OrderItem $item) {
                $order = $item->order;
                $netQty = max(0, (float) $item->quantity - (float) ($item->quantity_returned ?? 0));
                $isRetail = $order?->isRetailSale() ?? true;

                return [
                    'order_id' => $order?->id,
                    'order_number' => $order?->order_number,
                    'order_date' => $order?->order_date?->format('Y-m-d') ?? $order?->created_at?->format('Y-m-d'),
                    'status' => $order?->status,
                    'channel' => $isRetail ? 'POS Retail' : 'Wholesale',
                    'customer_name' => $order?->customer?->display_name ?? 'Walk-in',
                    'customer_phone' => $order?->customer?->phone,
                    'sales_person' => $order?->salesPerson?->name,
                    'variation_name' => $item->variation?->variation_name ?? '—',
                    'quantity' => $netQty,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => round($netQty * (float) $item->unit_price, 2),
                ];
            })
            ->all();
    }

    /** @param array<int, array<string, mixed>> $orderLines */
    private function topBuyers(array $orderLines): array
    {
        return collect($orderLines)
            ->groupBy('customer_name')
            ->map(fn ($lines, $name) => [
                'customer_name' => $name,
                'customer_phone' => $lines->first()['customer_phone'] ?? null,
                'order_count' => $lines->pluck('order_number')->unique()->count(),
                'total_qty' => round($lines->sum('quantity'), 2),
                'total_sales' => round($lines->sum('line_total'), 2),
            ])
            ->sortByDesc('total_qty')
            ->values()
            ->take(15)
            ->all();
    }
}
