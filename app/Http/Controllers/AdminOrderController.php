<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PreparesOrderChequePayment;
use App\Http\Controllers\Concerns\StoresOrderMultiPayment;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;
use App\Models\StockLedger;
use App\Services\StockChannelService;
use App\Models\User;
use App\Services\SaleReturnClaimService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AdminOrderController extends Controller
{
    use PreparesOrderChequePayment, StoresOrderMultiPayment;

    public function index(Request $request)
    {
        $type = $request->get('type', 'all'); // all | retail | wholesale
        $workflow = $request->get('workflow', 'all'); // all | ac | issued
        $urgentOnly = $request->boolean('urgent');
        $query = Order::with(['customer', 'salesPerson', 'items.product', 'items.variation'])->latest();

        // POS / retail quotations have their own Retail Sales → Quotations list.
        $query->whereNotIn('status', [Order::STATUS_QUOTATION, Order::STATUS_DRAFT]);

        if ($urgentOnly) {
            $query->where('is_urgent', true);
        }

        if ($request->filled('order_no')) {
            $query->where('order_number', 'like', '%' . trim((string) $request->order_no) . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($workflow === 'ac') {
            $query->where('status', Order::STATUS_AFTERCHECKING);
        } elseif ($workflow === 'issued') {
            $query->where('status', Order::STATUS_COMPLETED);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('sales_person_id')) {
            $query->where('user_id', $request->sales_person_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('order_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('order_date', '<=', $request->to_date);
        }

        if ($type === 'wholesale') {
            $salesPersonIds = User::where('role', 'sales_person')->pluck('id');
            $query->whereIn('user_id', $salesPersonIds);
        } elseif ($type === 'retail') {
            $salesPersonIds = User::where('role', 'sales_person')->pluck('id');
            $query->where(function ($q) use ($salesPersonIds) {
                $q->whereNull('user_id')->orWhereNotIn('user_id', $salesPersonIds);
            });
        }

        if ($request->filled('payment_state')) {
            $paidSub = Order::effectivePaidAmountSubquery();
            if ($request->payment_state === 'paid') {
                $query->whereRaw("total_amount <= {$paidSub}");
            } elseif ($request->payment_state === 'due') {
                $query->whereRaw("total_amount > {$paidSub}");
            }
        }

        $orders = $query->get();

        $stockService = app(StockChannelService::class);
        $manufacturingNeededOrderIds = [];
        $manufacturingStateMap = [];
        if (!in_array($workflow, ['ac', 'issued'], true)) {
            foreach ($orders as $order) {
                $requiresManufacturing = false;
                $channel = $order->stockChannel();

                foreach ($order->items as $item) {
                    $insight = $stockService->orderLineInsight(
                        (int) $item->product_id,
                        $channel,
                        $item->product_variation_id ? (int) $item->product_variation_id : null,
                        (float) $item->quantity
                    );

                    if ($insight['needs_manufacturing']) {
                        $requiresManufacturing = true;
                        break;
                    }
                }

                if ($requiresManufacturing) {
                    $manufacturingNeededOrderIds[] = $order->id;
                }
                $manufacturingStateMap[$order->id] = $requiresManufacturing;
            }

            if ($request->filled('manufacturing_state')) {
                if ($request->manufacturing_state === 'needed') {
                    $orders = $orders->filter(function ($order) use ($manufacturingStateMap) {
                        return !empty($manufacturingStateMap[$order->id]);
                    })->values();
                } elseif ($request->manufacturing_state === 'not_needed') {
                    $orders = $orders->filter(function ($order) use ($manufacturingStateMap) {
                        return empty($manufacturingStateMap[$order->id]);
                    })->values();
                }
            }
        }

        $salesPersons = User::where('role', 'sales_person')->orderBy('name')->get(['id', 'name']);
        $customers = Customer::orderByRaw("COALESCE(NULLIF(TRIM(business_name), ''), name)")
            ->get(['id', 'name', 'business_name', 'phone']);

        $boardStatuses = Order::adminBoardStatuses();
        if ($workflow === 'ac') {
            $boardStatuses = array_intersect_key(
                $boardStatuses,
                array_flip([Order::STATUS_AFTERCHECKING])
            );
        } elseif ($workflow === 'issued') {
            $boardStatuses = array_intersect_key(
                $boardStatuses,
                array_flip([Order::STATUS_COMPLETED])
            );
        }

        $ordersByStatus = $orders->groupBy('status');
        $statusCounts = collect($boardStatuses)->mapWithKeys(function ($meta, $status) use ($ordersByStatus) {
            return [$status => ($ordersByStatus->get($status)?->count() ?? 0)];
        });

        return view('erp.orders.index', compact(
            'orders',
            'ordersByStatus',
            'boardStatuses',
            'statusCounts',
            'type',
            'workflow',
            'manufacturingNeededOrderIds',
            'salesPersons',
            'customers',
            'urgentOnly'
        ));
    }

    public function history(Request $request)
    {
        $date = $this->historyDate($request);
        $orders = $this->historyOrdersQuery($date)
            ->paginate(1)
            ->withQueryString()
            ->onEachSide(2);
        $order = $orders->first();

        return view('erp.orders.history', compact('orders', 'order', 'date'));
    }

    public function downloadHistoryPdf(Request $request)
    {
        $date = $this->historyDate($request);
        $orders = $this->historyOrdersQuery($date)->get();

        if ($orders->isEmpty()) {
            return redirect()
                ->route('orders.history', ['date' => $date])
                ->with('error', 'No orders on '.$date.' to download.');
        }

        $pdf = Pdf::loadView('erp.orders.history-pdf', [
            'orders' => $orders,
            'date' => $date,
        ]);
        $filename = 'Orders '.$date.'.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    protected function historyDate(Request $request): string
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
        ]);

        return isset($validated['date'])
            ? \Carbon\Carbon::parse($validated['date'])->toDateString()
            : now()->toDateString();
    }

    protected function historyOrdersQuery(string $date)
    {
        return Order::query()
            ->with(['customer', 'salesPerson', 'items.product', 'items.variation'])
            ->countedInSales()
            ->whereDate('order_date', $date)
            ->orderBy('id');
    }

    public function create()
    {
        $customers = Customer::orderByRaw("COALESCE(NULLIF(TRIM(business_name), ''), name)")->get(['id', 'name', 'business_name', 'phone']);
        $products = Product::where('is_active', true)->get();
        $salesPersons = User::where('role', 'sales_person')->orderBy('name')->get();
        return view('erp.orders.create', compact('customers', 'products', 'salesPersons'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sales_person_id' => 'nullable|exists:users,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'order_date' => 'required|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        try {
            [$orderItemsData, $totalAmount] = $this->buildOrderItemsFromRequest($request->items);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }

        $salesPersonId = $this->resolvedSalesPersonId($request->sales_person_id);
        $order = Order::create([
            'order_number' => Order::nextOrderNumber(),
            'customer_id' => $request->customer_id,
            'user_id' => $salesPersonId,
            'total_amount' => $totalAmount,
            'status' => 'pending',
            'order_date' => $request->order_date,
            'notes' => filled($request->notes) ? trim((string) $request->notes) : null,
        ]);

        foreach ($orderItemsData as $data) {
            $order->items()->create($data);
        }

        return redirect()->route('orders.index')->with('success', 'Order created successfully.');
    }

    public function edit(Order $order)
    {
        if (!$order->canAdminEditItems()) {
            return redirect()
                ->route('orders.show', $order)
                ->with('error', 'Only Pending or Processing orders can be edited. After Order Check, edit items via Order Check / Invoice flow.');
        }

        $order->load(['items.product', 'items.variation', 'customer', 'salesPerson']);
        $customers = Customer::orderByRaw("COALESCE(NULLIF(TRIM(business_name), ''), name)")->get(['id', 'name', 'business_name', 'phone']);
        $products = Product::where('is_active', true)->get();
        $salesPersons = User::where('role', 'sales_person')->orderBy('name')->get();

        return view('erp.orders.edit', compact('order', 'customers', 'products', 'salesPersons'));
    }

    public function update(Request $request, Order $order)
    {
        if (!$order->canAdminEditItems()) {
            return redirect()
                ->route('orders.show', $order)
                ->with('error', 'Only Pending or Processing orders can be edited.');
        }

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sales_person_id' => 'nullable|exists:users,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'order_date' => 'required|date',
        ]);

        try {
            $order->load('items');
            [$orderItemsData, $subtotal] = $this->buildOrderItemsFromRequest($request->items, $order);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }

        $totals = Order::discountBreakdown(
            $subtotal,
            $order->discount_type,
            (float) ($order->discount_value ?? 0)
        );

        DB::transaction(function () use ($request, $order, $orderItemsData, $subtotal, $totals) {
            $order->update([
                'customer_id' => $request->customer_id,
                'user_id' => $this->resolvedSalesPersonId($request->sales_person_id),
                'subtotal' => round($subtotal, 2),
                'discount_amount' => $totals['discount_amount'],
                'total_amount' => $totals['total_amount'],
                'order_date' => $request->order_date,
            ]);

            $order->items()->delete();
            foreach ($orderItemsData as $data) {
                $order->items()->create($data);
            }
        });

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Order updated successfully.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array{0: array<int, array<string, mixed>>, 1: float}
     */
    protected function buildOrderItemsFromRequest(array $items, ?Order $existingOrder = null): array
    {
        $totalAmount = 0;
        $orderItemsData = [];
        $existingPrices = [];

        if ($existingOrder) {
            foreach ($existingOrder->items as $line) {
                $key = $this->orderLineKey($line->product_id, $line->product_variation_id);
                $existingPrices[$key] ??= (float) $line->unit_price;
                if ($line->product_variation_id) {
                    $existingPrices['variation:'.(int) $line->product_variation_id] ??= (float) $line->unit_price;
                }
            }
        }

        foreach ($items as $item) {
            $product = Product::withTrashed()->find($item['product_id'] ?? null);
            $quantity = (int) ($item['quantity'] ?? 0);
            $variationId = null;
            $variation = null;

            if (!empty($item['variation_id'])) {
                $variation = \App\Models\ProductVariation::find($item['variation_id']);
                if (!$variation) {
                    throw new \InvalidArgumentException('Invalid variation selected for '.($product->name ?? 'this product').'.');
                }

                $alreadyOnOrder = $existingOrder && $existingOrder->items->contains(
                    fn ($line) => (int) $line->product_variation_id === (int) $variation->id
                );

                if (!$product || !$variation->belongsToProduct($product->id)) {
                    if (!$alreadyOnOrder) {
                        throw new \InvalidArgumentException('Invalid variation selected for '.($product->name ?? 'this product').'.');
                    }
                    $product = Product::withTrashed()->find($variation->product_id) ?: $product;
                }

                $variationId = $variation->id;
            }

            $keptOnOrder = $product && (
                array_key_exists($this->orderLineKey($product->id, $variationId), $existingPrices)
                || ($variationId && isset($existingPrices['variation:'.(int) $variationId]))
            );

            if (!$product || ($product->trashed() && !$keptOnOrder)) {
                throw new \InvalidArgumentException('Invalid product selected.');
            }

            $lineKey = $this->orderLineKey($product->id, $variationId);
            if (array_key_exists($lineKey, $existingPrices)) {
                $price = $existingPrices[$lineKey];
            } elseif ($variationId && isset($existingPrices['variation:'.(int) $variationId])) {
                $price = $existingPrices['variation:'.(int) $variationId];
            } elseif ($variation) {
                $price = (float) ($variation->wholesale_price ?? 0) > 0
                    ? (float) $variation->wholesale_price
                    : (float) $variation->default_sell_price;
            } else {
                $price = (float) ($product->wholesale_price ?? 0) > 0
                    ? (float) $product->wholesale_price
                    : (float) ($product->selling_price ?? 0);
            }

            $lineTotal = $price * $quantity;
            $totalAmount += $lineTotal;

            $orderItemsData[] = [
                'product_id' => $product->id,
                'product_variation_id' => $variationId,
                'quantity' => $quantity,
                'unit_price' => $price,
                'total_price' => $lineTotal,
            ];
        }

        if ($orderItemsData === []) {
            throw new \InvalidArgumentException('Add at least one order item.');
        }

        return [$orderItemsData, $totalAmount];
    }

    protected function orderLineKey(mixed $productId, mixed $variationId): string
    {
        return ((int) $productId).':'.((int) ($variationId ?: 0));
    }

    protected function resolvedSalesPersonId(mixed $salesPersonId): ?int
    {
        if (!$salesPersonId) {
            return null;
        }

        $salesPerson = User::find($salesPersonId);
        if (!$salesPerson || $salesPerson->role !== 'sales_person') {
            return null;
        }

        return (int) $salesPerson->id;
    }

    public function show(Order $order)
    {
        if ($order->isRetailQuotation()) {
            return redirect()->route('retail-quotations.show', $order);
        }

        $order->load(['customer', 'items.product', 'items.variation', 'salesPerson', 'payments', 'saleReturns.items']);

        $stockService = app(StockChannelService::class);
        $channel = $order->stockChannel();
        $stockInsights = [];
        foreach ($order->items as $item) {
            $stockInsights[$item->id] = $stockService->orderLineInsight(
                (int) $item->product_id,
                $channel,
                $item->product_variation_id ? (int) $item->product_variation_id : null,
                (float) $item->quantity
            );
        }

        return view('erp.orders.show', compact('order', 'stockInsights', 'channel'));
    }

    public function destroy(Order $order)
    {
        $order->load(['payments', 'customer']);

        $orderNumber = $order->order_number;

        DB::transaction(function () use ($order) {
            StockLedger::query()
                ->where('reference_type', Order::class)
                ->where('reference_id', $order->id)
                ->delete();

            foreach ($order->payments as $payment) {
                if ($payment->cheque_image_path) {
                    Storage::disk('public')->delete($payment->cheque_image_path);
                }
            }

            if ($order->status === Order::STATUS_CREDIT && $order->customer) {
                $paidEffective = (float) $order->payments()->effectiveForOrderPaid()->sum('amount');
                $dueAmount = max(0, (float) $order->total_amount - $paidEffective);
                if ($dueAmount > 0) {
                    $newBalance = max(0, (float) $order->customer->due_balance - $dueAmount);
                    $order->customer->update(['due_balance' => $newBalance]);
                }
            }

            $order->delete();
        });

        return redirect()
            ->route('orders.index')
            ->with('success', "Order {$orderNumber} deleted successfully.");
    }

    public function addPayment(Request $request, Order $order)
    {
        return $this->storeOrderMultiPayment(
            $request,
            $order,
            route('orders.show', $order) . '#add-payment'
        );
    }
    
    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:' . implode(',', Order::adminUpdatableStatuses()),
        ]);

        $newStatus = $request->status;

        if ($newStatus === Order::STATUS_COMPLETED && $order->status !== Order::STATUS_COMPLETED) {
            if ($order->status !== Order::STATUS_AFTERCHECKING) {
                return back()->withErrors([
                    'status' => 'Completed කිරීමට පෙර order එක After Checking වෙත යවන්න හෝ Issue Invoice කරන්න.',
                ]);
            }

            return back()->withErrors([
                'status' => 'Invoice issue කරලා completed කරන්න. Order view එකෙන් Issue Invoice button එක භාවිතා කරන්න.',
            ]);
        }

        $order->update(['status' => $newStatus]);

        $redirectQuery = collect($request->except(['_token', 'status']))
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();

        return redirect()
            ->route('orders.index', $redirectQuery)
            ->withFragment('status-' . $newStatus)
            ->with('success', 'Order ' . $order->order_number . ' moved to ' . $order->fresh()->statusLabel() . '.');
    }

    public function completeOrderCheck(Request $request, Order $order)
    {
        if (!in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_PROCESSING], true)) {
            return back()->withErrors(['invoice_issue' => 'Order Check කරන්න පුළුවන් pending හෝ processing orders වලට විතරයි.']);
        }

        $order->load(['items.product', 'items.variation']);

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer',
            'items.*.checked' => 'required|boolean',
            'items.*.quantity' => 'nullable|numeric|min:0',
            'issued_bags_count' => 'nullable|integer|min:0',
            'issued_bundle_count' => 'nullable|integer|min:0',
        ]);

        $submittedIds = collect($validated['items'])->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
        $orderLineIds = $order->items->pluck('id')->sort()->values();
        if ($submittedIds->toArray() !== $orderLineIds->toArray()) {
            return back()->withErrors(['invoice_issue' => 'Submit කරන payload එකේ සියලු order line items තිබිය යුතුය. Page refresh කරලා නැවත උත්සාහ කරන්න.'])->withInput();
        }

        $submittedItems = collect($validated['items'])->keyBy(fn ($item) => (int) $item['id']);
        $newChecks = $order->items->filter(function ($orderItem) use ($submittedItems) {
            if ($orderItem->isChecked()) {
                return false;
            }
            $payload = $submittedItems->get($orderItem->id);

            return $payload && (bool) $payload['checked'];
        });
        if ($newChecks->isEmpty()) {
            return back()->withErrors(['invoice_issue' => 'Check කරන item එකක්වත් tick කරන්න.'])->withInput();
        }

        DB::transaction(function () use ($order, $validated, $submittedItems, $newChecks) {
            $stockService = app(StockChannelService::class);
            $channel = $order->stockChannel();

            foreach ($newChecks as $orderItem) {
                $payload = $submittedItems->get($orderItem->id);
                $orderedQty = $orderItem->orderedQuantity();
                $qty = round((float) ($payload['quantity'] ?? 0), 2);
                if ($qty < 0) {
                    throw ValidationException::withMessages([
                        'invoice_issue' => 'Check කරන item එකේ qty එක ඍණ වෙන්න බැහැ. Product: '.(optional($orderItem->product)->name ?? 'Product'),
                    ]);
                }

                $availableStock = $stockService->availableForOrderLine(
                    (int) $orderItem->product_id,
                    $channel,
                    $orderItem->product_variation_id ? (int) $orderItem->product_variation_id : null
                );
                if ($qty > 0 && $qty > $availableStock) {
                    throw ValidationException::withMessages([
                        'invoice_issue' => 'Available stock ඉක්මවා qty යවන්න බැහැ. Product: '.(optional($orderItem->product)->name ?? 'Product'),
                    ]);
                }

                $orderItem->update([
                    'ordered_quantity' => $orderedQty,
                    'quantity' => $qty,
                    'total_price' => round((float) $orderItem->unit_price * $qty, 2),
                    'checked_at' => now(),
                    'checked_by' => auth()->id(),
                ]);
            }

            // Final totals follow Order Check (issued) qty, not the original order qty.
            $order->unsetRelation('items');
            $items = $order->items()->get();
            $subtotal = 0.0;
            foreach ($items as $item) {
                $lineTotal = round((float) $item->unit_price * (float) $item->quantity, 2);
                if (abs((float) $item->total_price - $lineTotal) > 0.001) {
                    $item->update(['total_price' => $lineTotal]);
                }
                $subtotal += $lineTotal;
            }
            $subtotal = round($subtotal, 2);
            $allChecked = $items->every(fn ($item) => $item->isChecked());
            $counts = [
                'issued_bags_count' => (int) ($validated['issued_bags_count'] ?? 0),
                'issued_bundle_count' => (int) ($validated['issued_bundle_count'] ?? 0),
            ];

            if ($allChecked) {
                $order->update(array_merge($counts, [
                    'subtotal' => $subtotal,
                    'discount_type' => 'fixed',
                    'discount_value' => 0,
                    'discount_amount' => 0,
                    'shipping' => 0,
                    'invoice_other_expenses' => 0,
                    'total_amount' => $subtotal,
                    'status' => Order::STATUS_AFTERCHECKING,
                ]));
            } else {
                $totals = Order::discountBreakdown($subtotal, $order->discount_type, (float) $order->discount_value);
                $order->update(array_merge($counts, [
                    'subtotal' => $subtotal,
                    'discount_amount' => $totals['discount_amount'],
                    'total_amount' => $totals['total_amount'],
                ]));
            }
        });

        $order->refresh()->load('items');
        $progress = $order->orderCheckProgress();
        if ($progress['left'] === 0) {
            return redirect()->route('orders.index', ['workflow' => 'ac'])
                ->with('success', 'හැම item එකම check වුණා. Order AC (After Checking) වෙත යවා ඇත.');
        }

        return back()->with('success', $newChecks->count().' items checked. තව '.$progress['left'].' check කරන්න තියෙනවා.');
    }

    public function issueInvoice(Request $request, Order $order)
    {
        if ($order->status === 'completed') {
            return back()->withErrors(['invoice_issue' => 'මෙම order එකට invoice දැනටම issue කරලා තියෙනවා.']);
        }

        if ($order->status !== Order::STATUS_AFTERCHECKING) {
            return back()->withErrors(['invoice_issue' => 'Invoice issue කිරීමට පෙර order එක Order AC (After Checking) වෙත යවන්න.']);
        }

        $order->load(['items.product', 'items.variation', 'customer']);
        $validated = $request->validate($this->invoiceFinancialRules());
        DB::transaction(function () use ($order, $validated) {
            $stockService = app(StockChannelService::class);
            $channel = $order->stockChannel();

            foreach ($order->items as $orderItem) {
                $qty = (float) $orderItem->quantity;
                if ($qty <= 0) {
                    continue;
                }

                $availableStock = $stockService->availableForOrderLine(
                    (int) $orderItem->product_id,
                    $channel,
                    $orderItem->product_variation_id ? (int) $orderItem->product_variation_id : null
                );

                if ($qty > $availableStock) {
                    $name = optional($orderItem->product)->name ?: 'Product';
                    $variation = optional($orderItem->variation)->variation_name;
                    $label = $variation ? ($name . ' (' . $variation . ')') : $name;
                    throw ValidationException::withMessages([
                        'invoice_issue' => "{$label} සඳහා available stock {$availableStock}යි. ඒකට වඩා වැඩි qty issue කරන්න බෑ.",
                    ]);
                }

                StockLedger::create([
                    'product_id' => $orderItem->product_id,
                    'product_variation_id' => $orderItem->product_variation_id,
                    'channel' => $channel,
                    'transaction_type' => 'sale',
                    'quantity' => -$qty,
                    'reference_type' => Order::class,
                    'reference_id' => $order->id,
                    'notes' => ucfirst($channel) . ' order invoice issued',
                    'created_by' => auth()->id(),
                ]);
            }

            $subtotal = (float) $order->items->sum('total_price');
            $this->applyInvoiceFinancials($order, $validated, $subtotal, markCompleted: true);

            $claimAmount = round((float) ($validated['claim_credit_amount'] ?? 0), 2);
            if ($claimAmount > 0) {
                try {
                    app(SaleReturnClaimService::class)->applyToOrder($order->fresh(), $claimAmount, auth()->id());
                } catch (\InvalidArgumentException $e) {
                    throw ValidationException::withMessages(['claim_credit_amount' => $e->getMessage()]);
                }
            }
        });

        return redirect()->route('orders.index', ['workflow' => 'issued'])->with('success', 'Invoice issued successfully.');
    }

    public function updateInvoice(Request $request, Order $order)
    {
        if ($order->status !== Order::STATUS_COMPLETED) {
            return back()->withErrors(['invoice_edit' => 'Invoice edit කිරීමට order එක completed විය යුතුයි.']);
        }

        $order->load(['items.product', 'items.variation']);
        $validated = $request->validate($this->invoiceFinancialRules());
        $subtotal = (float) $order->items->sum('total_price');
        $this->applyInvoiceFinancials($order, $validated, $subtotal, markCompleted: false);

        return back()->with('success', 'Invoice updated successfully. Net total recalculated.');
    }

    public function downloadInvoice(Order $order)
    {
        if ($order->status !== 'completed') {
            return back()->with('error', 'Invoice is not issued yet. Please complete Order Check and Issue Invoice first.');
        }

        $order->load(['customer', 'items.product', 'items.variation', 'salesPerson', 'payments']);
        $pdf = Pdf::loadView('erp.sales_persons.orders.invoice', compact('order'))
            ->setPaper('a4', 'portrait');
        $serial = $order->taxInvoiceSerialNumber();

        return $pdf->download('tax-invoice-' . $serial . '.pdf');
    }

    public function downloadOrderForm(Order $order)
    {
        $order->load(['customer', 'items.product', 'items.variation', 'salesPerson']);
        $subtotal = $order->items->sum('total_price');

        $pdf = Pdf::loadView('erp.sales_persons.orders.order_form', compact('order', 'subtotal'));
        $downloadName = $order->orderFormFilename();
        $pdf->addInfo(['Title' => pathinfo($downloadName, PATHINFO_FILENAME)]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$downloadName.'"',
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function invoiceFinancialRules(): array
    {
        return [
            'discount_type' => 'nullable|in:fixed,percentage',
            'discount_value' => 'nullable|numeric|min:0',
            'shipping' => 'nullable|numeric|min:0',
            'invoice_other_expenses' => 'nullable|numeric|min:0',
            'claim_credit_amount' => 'nullable|numeric|min:0',
        ];
    }

    protected function applyInvoiceFinancials(Order $order, array $validated, float $subtotal, bool $markCompleted): void
    {
        $totals = Order::computeInvoiceTotals(
            $subtotal,
            $validated['discount_type'] ?? $order->discount_type ?? 'fixed',
            (float) ($validated['discount_value'] ?? $order->discount_value ?? 0),
            (float) ($validated['shipping'] ?? $order->shipping ?? 0),
            (float) ($validated['invoice_other_expenses'] ?? $order->invoice_other_expenses ?? 0),
        );

        $payload = array_merge($totals, [
            'subtotal' => round($subtotal, 2),
        ]);

        if ($markCompleted) {
            $payload['invoice_number'] = $order->invoice_number ?: Order::nextInvoiceNumber();
            $payload['status'] = Order::STATUS_COMPLETED;
        }

        $order->update($payload);
    }
}
