<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrdersManufacturingReportService
{
    public function __construct(
        private readonly StockChannelService $stock
    ) {}

    /** @return array<string, mixed> */
    public function build(string $from, string $to, Request $request): array
    {
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $manufacturingFilter = $request->get('manufacturing', 'all'); // all | needed | not_needed
        $status = $request->get('status');
        $salesPersonId = $request->get('sales_person_id');
        $customerId = $request->get('customer_id');
        $search = trim((string) $request->get('q', ''));

        $query = Order::query()
            ->with(['customer', 'salesPerson', 'items.product', 'items.variation'])
            ->whereIn('status', [
                Order::STATUS_PENDING,
                Order::STATUS_PROCESSING,
                Order::STATUS_AFTERCHECKING,
                Order::STATUS_DRAFT,
            ])
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween(DB::raw('DATE(COALESCE(order_date, created_at))'), [$from, $to]);
            })
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($salesPersonId, fn ($q) => $q->where('user_id', $salesPersonId))
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('order_number', 'like', "%{$search}%")
                        ->orWhere('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('business_name', 'like', "%{$search}%"));
                });
            })
            ->orderByRaw('COALESCE(order_date, created_at) DESC');

        $orders = $query->get();

        $orderRows = [];
        $lineRows = [];

        foreach ($orders as $order) {
            $channel = $order->stockChannel();
            $orderNeedsMfg = false;
            $orderShortage = 0.0;
            $lines = [];

            foreach ($order->items as $item) {
                $insight = $this->stock->orderLineInsight(
                    (int) $item->product_id,
                    $channel,
                    $item->product_variation_id ? (int) $item->product_variation_id : null,
                    (float) $item->quantity
                );

                if ($insight['needs_manufacturing']) {
                    $orderNeedsMfg = true;
                }
                $orderShortage += $insight['required'];

                $line = [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'order_date' => $order->order_date?->format('Y-m-d') ?? $order->created_at?->format('Y-m-d'),
                    'customer' => $order->customer?->display_name ?? 'Walk-in',
                    'customer_phone' => $order->customer?->phone,
                    'sales_person' => $order->salesPerson?->name ?? '—',
                    'status' => $order->status,
                    'status_label' => $order->statusLabel(),
                    'channel' => $channel,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->name ?? '—',
                    'product_sku' => $item->product?->sku,
                    'variation_name' => $item->variation?->variation_name ?? '—',
                    'ordered_qty' => $insight['ordered'],
                    'available_stock' => $insight['available'],
                    'shortage_qty' => $insight['required'],
                    'needs_manufacturing' => $insight['needs_manufacturing'],
                    'manufacturing_label' => $insight['needs_manufacturing'] ? 'අවශ්‍යයි' : 'අවශ්‍ය නැහැ',
                ];

                $lines[] = $line;
                $lineRows[] = $line;
            }

            if ($manufacturingFilter === 'needed' && !$orderNeedsMfg) {
                continue;
            }
            if ($manufacturingFilter === 'not_needed' && $orderNeedsMfg) {
                continue;
            }

            $orderRows[] = [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'order_date' => $order->order_date?->format('Y-m-d') ?? $order->created_at?->format('Y-m-d'),
                'customer' => $order->customer?->display_name ?? 'Walk-in',
                'customer_phone' => $order->customer?->phone,
                'sales_person' => $order->salesPerson?->name ?? '—',
                'status' => $order->status,
                'status_label' => $order->statusLabel(),
                'total_amount' => (float) $order->total_amount,
                'line_count' => count($lines),
                'needs_manufacturing' => $orderNeedsMfg,
                'manufacturing_label' => $orderNeedsMfg ? 'අවශ්‍යයි' : 'Stock OK',
                'total_shortage_qty' => round($orderShortage, 2),
                'lines' => $lines,
            ];
        }

        return [
            'filters' => [
                'from' => $from,
                'to' => $to,
                'manufacturing' => $manufacturingFilter,
                'status' => $status,
                'sales_person_id' => $salesPersonId,
                'customer_id' => $customerId,
                'search' => $search,
            ],
            'orders' => $orderRows,
            'lines' => $lineRows,
            'summary' => [
                'order_count' => count($orderRows),
                'needs_manufacturing_count' => collect($orderRows)->where('needs_manufacturing', true)->count(),
                'stock_ok_count' => collect($orderRows)->where('needs_manufacturing', false)->count(),
                'total_shortage_pcs' => round(collect($orderRows)->sum('total_shortage_qty'), 2),
                'line_count' => count($lineRows),
            ],
            'sales_persons' => User::where('role', 'sales_person')->orderBy('name')->get(['id', 'name']),
        ];
    }
}
