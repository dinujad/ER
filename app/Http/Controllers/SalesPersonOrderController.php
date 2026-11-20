<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Http\Controllers\Concerns\PreparesOrderChequePayment;
use App\Http\Controllers\Concerns\StoresOrderMultiPayment;
use App\Services\StockChannelService;
use App\Services\TextLkSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SalesPersonOrderController extends Controller
{
    use PreparesOrderChequePayment, StoresOrderMultiPayment;

    public function __construct(protected StockChannelService $stock) {}

    public function index(Request $request)
    {
        $showingDrafts = $request->get('status') === 'draft';
        $query = Order::with('customer')
            ->withCount(['items as checked_items_count' => fn ($q) => $q->whereNotNull('checked_at')])
            ->visibleToSalesPerson()
            ->latest();

        if ($showingDrafts) {
            $query->where('status', Order::STATUS_DRAFT)
                ->where('user_id', auth()->id());
        } else {
            $query->where('status', '!=', Order::STATUS_DRAFT);
        }

        $orders = $query->get();

        return view('erp.sales_persons.orders.index', compact('orders', 'showingDrafts'));
    }

    /**
     * Ensure the logged-in sales person can access this order.
     * Allowed when they own the order, or when they added the customer attached to it.
     *
     * Uses loose (==) comparison on purpose: MySQL bigint columns occasionally hydrate
     * as strings depending on PDO settings, so strict === can produce false negatives.
     */
    protected function authorizeOrderAccess(Order $order): void
    {
        $userId = (int) auth()->id();
        $orderUserId = $order->user_id !== null ? (int) $order->user_id : null;
        $customerAddedBy = $order->customer ? ($order->customer->added_by !== null ? (int) $order->customer->added_by : null) : null;

        $isOrderOwner = $orderUserId !== null && $orderUserId === $userId;
        $isCustomerOwner = $customerAddedBy !== null && $customerAddedBy === $userId;

        if (!$isOrderOwner && !$isCustomerOwner) {
            \Illuminate\Support\Facades\Log::warning('SalesPersonOrderController access denied', [
                'auth_user_id'          => $userId,
                'order_id'              => $order->id,
                'order_user_id'         => $orderUserId,
                'order_user_id_raw'     => $order->user_id,
                'customer_id'           => $order->customer?->id,
                'customer_added_by'     => $customerAddedBy,
                'customer_added_by_raw' => $order->customer?->added_by,
            ]);
            abort(403, 'You do not have access to this order.');
        }
    }

    public function create(Request $request)
    {
        $selectedCustomer = null;
        $draftPayload = null;

        if ($request->filled('draft')) {
            $draftOrder = Order::with(['customer', 'items.product', 'items.variation'])
                ->where('status', Order::STATUS_DRAFT)
                ->where('user_id', auth()->id())
                ->findOrFail($request->integer('draft'));
            $selectedCustomer = $draftOrder->customer;
            $draftPayload = $this->draftPayload($draftOrder);
        } elseif (old('customer_id')) {
            $ownerIds = auth()->user()->visibleCustomerOwnerIds();
            $selectedCustomer = Customer::whereIn('added_by', $ownerIds)
                ->find(old('customer_id'));
        }

        return view('erp.sales_persons.orders.create', compact('selectedCustomer', 'draftPayload'));
    }

    public function edit(Order $order)
    {
        $order->load(['customer', 'items.product', 'items.variation']);
        $this->authorizeSalesPersonEdit($order);

        $selectedCustomer = $order->customer;
        $draftPayload = $this->draftPayload($order);
        $editingOrder = $order->id;

        return view('erp.sales_persons.orders.create', compact('selectedCustomer', 'draftPayload', 'editingOrder'));
    }

    public function update(Request $request, Order $order)
    {
        $order->load('items');
        $this->authorizeSalesPersonEdit($order);
        $this->normalizeOrderItems($request);
        $ownerIds = auth()->user()->visibleCustomerOwnerIds();
        $request->validate([
            'customer_id' => [
                'required',
                Rule::exists('customers', 'id')->where(fn ($q) => $q->whereIn('added_by', $ownerIds)),
            ],
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variation_id' => 'nullable|exists:product_variations,id',
            'items.*.quantity' => 'required|integer|min:1',
            'discount_type' => 'nullable|in:fixed,percentage',
            'discount_value' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
            'is_urgent' => 'nullable|boolean',
        ]);

        try {
            [$orderItemsData, $subtotal] = $this->pricedOrderItems($request->items, $order);
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('sales-person.orders.edit', $order)
                ->withInput()
                ->with('error', $e->getMessage());
        }

        $discount = $this->calculateOrderDiscount(
            $subtotal,
            $request->input('discount_type', 'fixed'),
            (float) $request->input('discount_value', 0)
        );

        DB::transaction(function () use ($request, $order, $subtotal, $discount, $orderItemsData) {
            $order->update([
                'customer_id' => $request->customer_id,
                'subtotal' => round($subtotal, 2),
                'total_amount' => $discount['total_amount'],
                'discount_amount' => $discount['discount_amount'],
                'discount_type' => $discount['discount_type'],
                'discount_value' => $discount['discount_value'],
                'notes' => filled($request->notes) ? trim((string) $request->notes) : null,
                'is_urgent' => $request->boolean('is_urgent'),
            ]);
            $order->items()->delete();
            foreach ($orderItemsData as $data) {
                $order->items()->create($data);
            }
        });

        return redirect()->route('sales-person.orders.show', $order)
            ->with('success', 'Order updated.')
            ->with('clear_edit_draft_id', $order->id);
    }

    protected function authorizeSalesPersonEdit(Order $order): void
    {
        if ((int) $order->user_id !== (int) auth()->id()) {
            abort(403, 'This order can no longer be edited.');
        }
        if ($order->items->contains(fn ($item) => $item->isChecked())) {
            abort(403, 'Admin දැනටම check කරලා තියෙන නිසා මේ order එක edit කරන්න බැහැ.');
        }
        if (!$order->canSalesPersonEdit()) {
            abort(403, 'This order can no longer be edited.');
        }
    }

    public function saveDraft(Request $request)
    {
        $this->normalizeOrderItems($request);
        $ownerIds = auth()->user()->visibleCustomerOwnerIds();
        $validated = $request->validate([
            'draft_id' => 'nullable|integer',
            'customer_id' => [
                'required',
                Rule::exists('customers', 'id')->where(fn ($q) => $q->whereIn('added_by', $ownerIds)),
            ],
            'items' => 'nullable|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variation_id' => 'nullable|exists:product_variations,id',
            'items.*.quantity' => 'required|integer|min:1',
            'discount_type' => 'nullable|in:fixed,percentage',
            'discount_value' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
            'is_urgent' => 'nullable|boolean',
        ]);

        try {
            [$orderItemsData, $subtotal] = $this->pricedOrderItems($validated['items'] ?? []);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $discount = $this->calculateOrderDiscount(
            $subtotal,
            $request->input('discount_type', 'fixed'),
            (float) $request->input('discount_value', 0)
        );

        $order = DB::transaction(function () use ($request, $discount, $orderItemsData, $subtotal) {
            $order = null;
            if ($request->filled('draft_id')) {
                $order = Order::query()
                    ->where('id', $request->integer('draft_id'))
                    ->where('user_id', auth()->id())
                    ->where('status', Order::STATUS_DRAFT)
                    ->first();

                if (!$order) {
                    return null;
                }
            }

            $attributes = [
                'customer_id' => $request->customer_id,
                'user_id' => auth()->id(),
                'subtotal' => round($subtotal, 2),
                'total_amount' => $discount['total_amount'],
                'discount_amount' => $discount['discount_amount'],
                'discount_type' => $discount['discount_type'],
                'discount_value' => $discount['discount_value'],
                'status' => Order::STATUS_DRAFT,
                'order_date' => now(),
                'notes' => filled($request->notes) ? trim((string) $request->notes) : null,
                'is_urgent' => $request->boolean('is_urgent'),
            ];

            if ($order) {
                $order->update($attributes);
                $order->items()->delete();
            } else {
                $order = Order::create($attributes + [
                    'order_number' => Order::nextOrderNumber(),
                ]);
            }

            foreach ($orderItemsData as $data) {
                $order->items()->create($data);
            }

            return $order;
        });

        if (!$order) {
            return response()->json(['message' => 'This draft was already placed.'], 409);
        }

        return response()->json([
            'id' => $order->id,
            'order_number' => $order->order_number,
        ]);
    }

    public function getProductVariations($productId)
    {
        $product = Product::with('variations')
            ->where('is_active', true)
            ->find($productId);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        // Treat as variable whenever variations exist (product_type may be unset/wrong).
        if ($product->product_type === 'variable' || $product->variations->isNotEmpty()) {
            $variations = $product->variations->map(function ($v) use ($product) {
                $availableStock = $this->stock->availableForOrderLine(
                    $v->product_id,
                    StockChannelService::CHANNEL_WHOLESALE,
                    $v->id
                );

                $price = (float) ($v->wholesale_price ?? 0) > 0
                    ? (float) $v->wholesale_price
                    : (float) ($v->default_sell_price ?? 0);

                return [
                    'id' => $v->id,
                    'product_id' => $v->product_id,
                    'product_sku' => $product->sku,
                    'product_name' => $product->name,
                    'sub_sku' => $v->sub_sku,
                    'variation_name' => $v->variation_name,
                    'default_sell_price' => $price,
                    'available_stock' => (float) $availableStock,
                ];
            });
            return response()->json([
                'type' => 'variable',
                'product_sku' => $product->sku,
                'product_name' => $product->name,
                'variations' => $variations,
            ]);
        } else {
            $wholesalePrice = $product->wholesale_price ?? $product->selling_price;
            $availableStock = $this->stock->availableForOrderLine(
                $product->id,
                StockChannelService::CHANNEL_WHOLESALE,
                null
            );

            return response()->json([
                'type' => 'simple',
                'price' => $wholesalePrice,
                'available_stock' => (float) $availableStock,
                'product' => (object) array_merge($product->toArray(), ['selling_price' => $wholesalePrice]),
            ]);
        }
    }

    public function searchProducts(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        if ($q === '') {
            return response()->json([]);
        }

        $products = Product::query()
            ->where('is_active', true)
            ->with(['variations:id,product_id,variation_name,sub_sku,wholesale_price,default_sell_price'])
            ->where(function ($builder) use ($q) {
                $builder->where('sku', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%")
                    ->orWhereHas('variations', function ($v) use ($q) {
                        $v->where('sub_sku', 'like', "%{$q}%")
                            ->orWhere('variation_name', 'like', "%{$q}%");
                    });
            })
            ->orderByRaw('CASE WHEN sku LIKE ? THEN 0 WHEN name LIKE ? THEN 1 ELSE 2 END', ["{$q}%", "{$q}%"])
            ->limit(25)
            ->get(['id', 'sku', 'name', 'product_type', 'wholesale_price', 'selling_price']);

        return response()->json($products->map(function (Product $product) {
            $prices = collect();

            if ($product->product_type === 'variable') {
                foreach ($product->variations as $variation) {
                    $prices->push((float) ($variation->wholesale_price ?: $variation->default_sell_price));
                }
            } else {
                $prices->push((float) ($product->wholesale_price ?: $product->selling_price));
            }

            $prices = $prices->filter(fn ($p) => $p > 0);

            return [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'product_type' => $product->product_type,
                'price_from' => $prices->min() ?? 0,
                'price_to' => $prices->max() ?? 0,
                'variation_count' => $product->variations->count(),
                'stock_total' => $this->stock->availableForOrderLine(
                    $product->id,
                    StockChannelService::CHANNEL_WHOLESALE,
                    null
                ),
            ];
        })->values());
    }

    public function store(Request $request)
    {
        $this->normalizeOrderItems($request);
        $ownerIds = auth()->user()->visibleCustomerOwnerIds();
        $request->validate([
            'customer_id' => [
                'required',
                Rule::exists('customers', 'id')->where(fn ($q) => $q->whereIn('added_by', $ownerIds)),
            ],
            'items'                    => 'required|array|min:1',
            'items.*.product_id'       => 'required|exists:products,id',
            'items.*.variation_id'     => 'nullable|exists:product_variations,id',
            'items.*.quantity'         => 'required|integer|min:1',
            'discount_type'            => 'nullable|in:fixed,percentage',
            'discount_value'           => 'nullable|numeric|min:0',
            'notes'                    => 'nullable|string|max:2000',
            'is_urgent'                => 'nullable|boolean',
            'draft_id'                 => 'nullable|integer',
        ]);

        try {
            [$orderItemsData, $subtotal] = $this->pricedOrderItems($request->items);
        } catch (\InvalidArgumentException $e) {
            return $this->backToOrderForm($request, $e->getMessage());
        }

        $discount = $this->calculateOrderDiscount(
            $subtotal,
            $request->input('discount_type', 'fixed'),
            (float) $request->input('discount_value', 0)
        );

        $order = DB::transaction(function () use ($request, $subtotal, $discount, $orderItemsData) {
            $attributes = [
                'customer_id'     => $request->customer_id,
                'user_id'         => auth()->id(),
                'subtotal'        => round($subtotal, 2),
                'total_amount'    => $discount['total_amount'],
                'discount_amount' => $discount['discount_amount'],
                'discount_type'   => $discount['discount_type'],
                'discount_value'  => $discount['discount_value'],
                'status'          => 'pending',
                'order_date'      => now(),
                'notes'           => filled($request->notes) ? trim((string) $request->notes) : null,
                'is_urgent'       => $request->boolean('is_urgent'),
            ];

            $order = null;
            if ($request->filled('draft_id')) {
                $order = Order::query()
                    ->where('id', $request->integer('draft_id'))
                    ->where('user_id', auth()->id())
                    ->where('status', Order::STATUS_DRAFT)
                    ->first();
            }

            if ($order) {
                $order->update($attributes);
                $order->items()->delete();
            } else {
                $order = Order::create($attributes + [
                    'order_number' => Order::nextOrderNumber(),
                ]);
            }

            foreach ($orderItemsData as $data) {
                $order->items()->create($data);
            }

            return $order;
        });

        // Send order confirmation SMS to customer phone
        $customer = $order->customer;
        if ($customer && !empty(trim($customer->phone ?? ''))) {
            try {
                $message = config('app.name') . ': Your order ' . $order->order_number . ' is confirmed. Total: Rs ' . number_format($order->total_amount, 2) . '. Thank you!';
                app(TextLkSmsService::class)->sendSms($customer->phone, $message);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Order confirmation SMS failed: ' . $e->getMessage());
            }
        }

        return redirect()->route('sales-person.orders.index')
            ->with('success', 'Order created successfully.')
            ->with('download_order_form_id', $order->id)
            ->with('share_order_filename', $order->orderFormFilename())
            ->with('share_owner_name', $order->customer?->name)
            ->with('share_owner_phone', $order->customer?->phone);
    }

    public function show(Order $order)
    {
        $order->load('customer');
        $this->authorizeOrderAccess($order);
        $order->load(['items.product', 'items.variation', 'salesPerson', 'payments', 'saleReturns']);
        return view('erp.sales_persons.orders.show', compact('order'));
    }

    public function addPayment(Request $request, Order $order)
    {
        $order->load('customer');
        $this->authorizeOrderAccess($order);

        return $this->storeOrderMultiPayment(
            $request,
            $order,
            route('sales-person.orders.show', $order) . '#add-payment'
        );
    }

    protected function redirectToOrderPaymentForm(Order $order): \Illuminate\Http\RedirectResponse
    {
        return redirect()->to(route('sales-person.orders.show', $order) . '#add-payment');
    }

    public function downloadInvoice(Order $order)
    {
        $order->load('customer');
        $this->authorizeOrderAccess($order);

        // Sales person can download invoice only after admin marks order as completed.
        if ($order->status !== 'completed') {
            return back()->with('error', 'Invoice is not available yet. Please wait until admin issues it.');
        }

        $order->load(['items.product', 'items.variation', 'salesPerson', 'payments']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('erp.sales_persons.orders.invoice', compact('order'))
            ->setPaper('a4', 'portrait');

        \App\Services\Audit\AuditLogger::record($order, 'invoice_printed');

        return $pdf->download('tax-invoice-' . $order->taxInvoiceSerialNumber() . '.pdf');
    }

    public function downloadOrderForm(Order $order)
    {
        $order->load('customer');
        $this->authorizeOrderAccess($order);

        $order->load(['items.product', 'items.variation', 'salesPerson']);
        $subtotal = $order->items->sum('total_price');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('erp.sales_persons.orders.order_form', compact('order', 'subtotal'));
        $downloadName = $order->orderFormFilename();
        $pdf->addInfo(['Title' => pathinfo($downloadName, PATHINFO_FILENAME)]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$downloadName.'"',
        ]);
    }

    protected function normalizeOrderItems(Request $request): void
    {
        $items = $request->input('items');
        if (!is_array($items)) {
            return;
        }

        $request->merge([
            'items' => array_map(function ($item) {
                if (is_array($item) && (($item['variation_id'] ?? null) === '')) {
                    $item['variation_id'] = null;
                }

                return $item;
            }, $items),
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array{0: array<int, array<string, mixed>>, 1: float}
     */
    protected function pricedOrderItems(array $items, ?Order $existing = null): array
    {
        $existingPrices = [];
        if ($existing) {
            foreach ($existing->items as $line) {
                $existingPrices[(int) $line->product_id.':'.(int) ($line->product_variation_id ?? 0)] = (float) $line->unit_price;
            }
        }

        $subtotal = 0;
        $orderItemsData = [];

        foreach ($items as $item) {
            $requestedVariationId = !empty($item['variation_id']) ? (int) $item['variation_id'] : 0;
            $productId = (int) ($item['product_id'] ?? 0);
            $priceKey = $productId.':'.$requestedVariationId;
            $keptPrice = $existingPrices[$priceKey] ?? null;

            $product = $keptPrice !== null
                ? Product::withTrashed()->find($productId)
                : Product::find($productId);
            if (!$product || ($keptPrice === null && !$product->is_active)) {
                throw new \InvalidArgumentException('One or more selected products are no longer available.');
            }

            $quantity = (int) $item['quantity'];
            $variationId = null;

            if ($requestedVariationId) {
                $variation = ProductVariation::find($requestedVariationId);

                if (!$variation || !$variation->belongsToProduct($product->id)) {
                    throw new \InvalidArgumentException('Invalid product variation selected.');
                }

                $price = $keptPrice ?? (float) ($variation->wholesale_price ?: $variation->default_sell_price);
                $variationId = $variation->id;
            } else {
                $price = $keptPrice ?? (float) ($product->wholesale_price ?: $product->selling_price);
            }

            $lineTotal = $price * $quantity;
            $subtotal += $lineTotal;
            $orderItemsData[] = [
                'product_id' => $product->id,
                'product_variation_id' => $variationId,
                'quantity' => $quantity,
                'unit_price' => $price,
                'total_price' => $lineTotal,
            ];
        }

        return [$orderItemsData, $subtotal];
    }

    protected function draftPayload(Order $order): array
    {
        return [
            'id' => $order->id,
            'customer_id' => $order->customer_id,
            'customer_label' => $order->customer
                ? $order->customer->display_name.($order->customer->phone ? ' ('.$order->customer->phone.')' : '')
                : '',
            'notes' => $order->notes,
            'is_urgent' => (bool) $order->is_urgent,
            'discount_type' => $order->discount_type ?: 'fixed',
            'discount_value' => (float) ($order->discount_value ?? 0),
            'last_product_id' => $order->items->last()?->product_id,
            'items' => $order->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'variation_id' => $item->product_variation_id,
                    'design_no' => $item->product?->sku ?? '',
                    'product_name' => $item->product?->name ?? '',
                    'variation_name' => $item->variation?->variation_name ?? '',
                    'price' => (float) $item->unit_price,
                    'quantity' => (int) $item->quantity,
                    'available_stock' => (float) $this->stock->availableForOrderLine(
                        (int) $item->product_id,
                        StockChannelService::CHANNEL_WHOLESALE,
                        $item->product_variation_id ? (int) $item->product_variation_id : null
                    ),
                ];
            })->values(),
        ];
    }

    protected function backToOrderForm(Request $request, string $message)
    {
        if ($request->filled('draft_id')) {
            return redirect()
                ->route('sales-person.orders.create', ['draft' => $request->integer('draft_id')])
                ->with('error', $message);
        }

        return back()->withInput()->with('error', $message);
    }

    /**
     * @return array{discount_type: string, discount_value: float, discount_amount: float, total_amount: float}
     */
    protected function calculateOrderDiscount(float $subtotal, ?string $discountType, float $discountValue): array
    {
        return Order::discountBreakdown($subtotal, $discountType, $discountValue);
    }
}
