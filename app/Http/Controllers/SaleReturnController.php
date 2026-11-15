<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\SaleReturn;
use App\Services\SaleReturnService;
use Illuminate\Http\Request;

class SaleReturnController extends Controller
{
    public function __construct(
        protected SaleReturnService $returnService
    ) {}

    public function index(Request $request)
    {
        $query = SaleReturn::query()
            ->with(['order', 'customer', 'creator', 'items'])
            ->orderByDesc('return_date')
            ->orderByDesc('id');

        if ($this->isSalesPerson()) {
            $query->whereHas('order', fn ($q) => $q->visibleToSalesPerson(auth()->id()));
        }

        if ($request->filled('return_no')) {
            $query->where('return_no', 'like', '%' . trim((string) $request->return_no) . '%');
        }
        if ($request->filled('order_no')) {
            $needle = trim((string) $request->order_no);
            $query->whereHas('order', function ($q) use ($needle) {
                $q->where('order_number', 'like', '%' . $needle . '%')
                    ->orWhere('invoice_number', 'like', '%' . $needle . '%');
            });
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('return_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('return_date', '<=', $request->to_date);
        }

        $totalReturned = (float) (clone $query)->sum('total');
        $totalClaimable = (float) (clone $query)->sum('claimable_amount');
        $totalClaimed = (float) (clone $query)->sum('claimed_amount');
        $returns = $query->paginate(25)->withQueryString();

        $customersQuery = Customer::orderByRaw("COALESCE(NULLIF(TRIM(business_name), ''), name)");
        if ($this->isSalesPerson()) {
            $customersQuery->where('added_by', auth()->id());
        }
        $customers = $customersQuery->get(['id', 'name', 'business_name']);

        $view = $this->isSalesPerson() ? 'sales_person.sale-returns.index' : 'erp.sale-returns.index';

        return view($view, compact('returns', 'customers', 'totalReturned', 'totalClaimable', 'totalClaimed'));
    }

    public function create(Request $request)
    {
        $order = null;
        if ($request->filled('order_id')) {
            $order = Order::with(['items.product', 'items.variation', 'customer', 'salesPerson', 'payments', 'saleReturns'])
                ->find($request->order_id);
        } elseif ($request->filled('q')) {
            $q = trim((string) $request->q);
            $orderQuery = Order::with(['items.product', 'items.variation', 'customer', 'salesPerson', 'payments', 'saleReturns'])
                ->where(function ($query) use ($q) {
                    $query->where('order_number', $q)
                        ->orWhere('invoice_number', $q)
                        ->orWhere('order_number', 'like', '%' . $q . '%')
                        ->orWhere('invoice_number', 'like', '%' . $q . '%');
                });
            if ($this->isSalesPerson()) {
                $orderQuery->visibleToSalesPerson(auth()->id());
            }
            $order = $orderQuery->orderByDesc('id')->first();
        }

        if ($order && $this->isSalesPerson()) {
            $this->assertSalesPersonCanAccessOrder($order);
        }

        $customersQuery = Customer::orderByRaw("COALESCE(NULLIF(TRIM(business_name), ''), name)");
        if ($this->isSalesPerson()) {
            $customersQuery->where('added_by', auth()->id());
        }
        $customers = $customersQuery->get(['id', 'name', 'business_name']);

        $lookupUrl = $this->isSalesPerson()
            ? route('sales-person.sale-returns.lookup-item')
            : route('sale-returns.lookup-item');
        $createUrl = $this->isSalesPerson()
            ? route('sales-person.sale-returns.create')
            : route('sale-returns.create');

        $view = $this->isSalesPerson() ? 'sales_person.sale-returns.create' : 'erp.sale-returns.create';

        return view($view, compact('order', 'customers', 'lookupUrl', 'createUrl'));
    }

    /**
     * Find the customer's latest returnable order line for a SKU / barcode / product name.
     */
    public function lookupItem(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'q' => 'required|string|min:2|max:100',
        ]);

        $customerId = (int) $data['customer_id'];
        $q = trim($data['q']);

        if ($this->isSalesPerson()) {
            $customerOk = Customer::query()
                ->where('id', $customerId)
                ->where('added_by', auth()->id())
                ->exists();
            if (!$customerOk) {
                abort(403, 'You can only look up returns for your customers.');
            }
        }

        $orderQuery = Order::query()
            ->with(['items.product', 'items.variation', 'payments', 'customer'])
            ->where('customer_id', $customerId)
            ->whereIn('status', [
                Order::STATUS_AFTERCHECKING,
                Order::STATUS_COMPLETED,
                Order::STATUS_PAID,
                Order::STATUS_CREDIT,
            ])
            ->whereHas('items', function ($iq) use ($q) {
                $iq->where(function ($w) use ($q) {
                    $w->whereHas('product', function ($pq) use ($q) {
                        $pq->where('sku', 'like', '%' . $q . '%')
                            ->orWhere('barcode', 'like', '%' . $q . '%')
                            ->orWhere('name', 'like', '%' . $q . '%');
                    })->orWhereHas('variation', function ($vq) use ($q) {
                        $vq->where('variation_name', 'like', '%' . $q . '%');
                    });
                });
            })
            ->orderByDesc('order_date')
            ->orderByDesc('id');

        if ($this->isSalesPerson()) {
            $orderQuery->visibleToSalesPerson(auth()->id());
        }

        $orders = $orderQuery->limit(15)->get();
        $matches = [];

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $product = $item->product;
                $variation = $item->variation;
                $hay = strtolower(implode(' ', array_filter([
                    $product?->sku,
                    $product?->barcode,
                    $product?->name,
                    $variation?->variation_name,
                ])));
                if ($hay === '' || !str_contains($hay, strtolower($q))) {
                    continue;
                }
                $max = $item->returnableQuantity();
                if ($max <= 0.0001) {
                    continue;
                }
                $matches[] = [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'invoice_number' => $order->invoice_number,
                    'order_date' => optional($order->order_date)->format('Y-m-d'),
                    'status' => $order->status,
                    'business' => $order->customer?->display_name,
                    'order_item_id' => $item->id,
                    'product' => $product?->name ?? 'Product',
                    'variation' => $variation?->variation_name,
                    'sku' => $product?->sku,
                    'barcode' => $product?->barcode,
                    'returnable_qty' => $max,
                    'list_unit_price' => round((float) $item->unit_price, 2),
                    'effective_unit_price' => $item->effectiveReturnUnitPrice(),
                ];
            }
        }

        usort($matches, function ($a, $b) {
            $da = $a['order_date'] ?? '';
            $db = $b['order_date'] ?? '';
            if ($da === $db) {
                return $b['order_id'] <=> $a['order_id'];
            }

            return $db <=> $da;
        });

        $latest = $matches[0] ?? null;

        return response()->json([
            'latest' => $latest,
            'matches' => array_slice($matches, 0, 8),
        ]);
    }

    public function store(Request $request, ?Order $order = null)
    {
        if (!$order) {
            $request->validate([
                'order_id' => 'required|exists:orders,id',
            ]);
            $order = Order::findOrFail($request->order_id);
        }

        if ($this->isSalesPerson()) {
            $this->assertSalesPersonCanAccessOrder($order);
        }

        $request->validate([
            'return_date' => 'required|date',
            'reason' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.order_item_id' => 'required|exists:order_items,id',
            'items.*.quantity' => 'nullable|numeric|min:0',
            'refund_amount' => 'nullable|numeric|min:0',
            'refund_method' => 'nullable|in:cash,card,bank_transfer,cheque',
            'refund_reference' => 'nullable|string|max:255',
            'refund_notes' => 'nullable|string|max:1000',
        ]);

        $lines = [];
        foreach ($request->items as $row) {
            $qty = (float) ($row['quantity'] ?? 0);
            if ($qty > 0) {
                $lines[] = [
                    'order_item_id' => (int) $row['order_item_id'],
                    'quantity' => $qty,
                ];
            }
        }

        try {
            $return = $this->returnService->createReturn(
                $order,
                $lines,
                $request->return_date,
                $request->reason,
                auth()->id(),
                [
                    'refund_amount' => (float) ($request->refund_amount ?? 0),
                    'refund_method' => $request->refund_method ?? 'cash',
                    'refund_reference' => $request->refund_reference,
                    'refund_notes' => $request->refund_notes,
                ]
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'Could not record sale return. ' . $e->getMessage());
        }

        $msg = 'Sale return ' . $return->return_no . ' recorded. Stock restocked.';
        $msg .= ' Claimable credit (30%): Rs ' . number_format((float) $return->claimable_amount, 2) . '.';
        if ((float) ($request->refund_amount ?? 0) > 0) {
            $msg .= ' Refund Rs ' . number_format((float) $request->refund_amount, 2) . ' posted.';
        }

        if ($request->boolean('from_pos')) {
            return redirect()->route('pos.index')->with('success', $msg);
        }

        if ($this->isSalesPerson() || $request->boolean('redirect_sales_person')) {
            return redirect()->route('sales-person.orders.show', $order)->with('success', $msg);
        }

        if ($request->boolean('redirect_order_show')) {
            return redirect()->route('orders.show', $order)->with('success', $msg);
        }

        return redirect()->route('sale-returns.index')->with('success', $msg);
    }

    public function show(SaleReturn $saleReturn)
    {
        if ($this->isSalesPerson()) {
            $saleReturn->loadMissing('order');
            if (!$saleReturn->order || !Order::visibleToSalesPerson(auth()->id())->where('id', $saleReturn->order_id)->exists()) {
                abort(403);
            }
        }

        $saleReturn->load(['order.customer', 'customer', 'items.product', 'items.variation', 'creator', 'refunds', 'claims.order']);

        $view = $this->isSalesPerson() ? 'sales_person.sale-returns.show' : 'erp.sale-returns.show';

        return view($view, compact('saleReturn'));
    }

    protected function isSalesPerson(): bool
    {
        return auth()->check() && auth()->user()->role === 'sales_person';
    }

    protected function assertSalesPersonCanAccessOrder(Order $order): void
    {
        $ok = Order::visibleToSalesPerson(auth()->id())->where('id', $order->id)->exists();
        if (!$ok) {
            abort(403, 'You do not have access to this order.');
        }
    }
}
