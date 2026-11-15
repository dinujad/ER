<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Store;
use App\Services\StockChannelService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function __construct(protected StockChannelService $stockChannel)
    {
    }

    protected function retailUnitPrice(Product $product, ?ProductVariation $variation = null): float
    {
        if ($variation) {
            return (float) ($variation->retail_price ?: $variation->default_sell_price ?: 0);
        }

        return (float) ($product->retail_price ?: $product->selling_price ?: 0);
    }

    protected function retailUnitStock(Product $product, ?ProductVariation $variation = null): float
    {
        return $this->stockChannel->balance(
            $product->id,
            StockChannelService::CHANNEL_RETAIL,
            $variation?->id
        );
    }

    protected function productHasRetailStock(Product $product): bool
    {
        $product->loadMissing('variations');

        if ($product->product_type === 'variable') {
            foreach ($product->variations as $variation) {
                if ($this->retailUnitStock($product, $variation) > 0) {
                    return true;
                }
            }

            return false;
        }

        return $this->retailUnitStock($product) > 0;
    }

    /** @return array<string, mixed>|null */
    protected function mapProductForPos(Product $product): ?array
    {
        $product->loadMissing('variations');

        if (!$this->productHasRetailStock($product)) {
            return null;
        }

        if ($product->product_type === 'variable') {
            $variations = $product->variations
                ->filter(fn (ProductVariation $v) => $this->retailUnitStock($product, $v) > 0)
                ->map(fn (ProductVariation $v) => [
                    'id' => $v->id,
                    'product_id' => $v->product_id,
                    'variation_name' => $v->variation_name,
                    'retail_price' => $this->retailUnitPrice($product, $v),
                    'default_sell_price' => $this->retailUnitPrice($product, $v),
                    'retail_stock' => $this->retailUnitStock($product, $v),
                ])
                ->values();

            if ($variations->isEmpty()) {
                return null;
            }

            $prices = $variations->pluck('retail_price')->filter(fn ($p) => $p > 0);
            $minPrice = (float) ($prices->min() ?? 0);
            $maxPrice = (float) ($prices->max() ?? 0);

            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'image' => $product->image,
                'brand_id' => $product->brand_id,
                'category_id' => $product->category_id,
                'product_type' => 'variable',
                'retail_price' => $minPrice,
                'price_label' => ($prices->count() > 1 && $minPrice !== $maxPrice)
                    ? number_format($minPrice, 2) . ' – ' . number_format($maxPrice, 2)
                    : number_format($minPrice, 2),
                'retail_stock' => (float) $variations->sum('retail_stock'),
                'variations' => $variations->all(),
            ];
        }

        $price = $this->retailUnitPrice($product);
        $stock = $this->retailUnitStock($product);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'image' => $product->image,
            'brand_id' => $product->brand_id,
            'category_id' => $product->category_id,
            'product_type' => 'simple',
            'retail_price' => $price,
            'price_label' => number_format($price, 2),
            'retail_stock' => $stock,
            'variations' => [],
        ];
    }

    protected function posCatalog()
    {
        return Product::query()
            ->with(['variations', 'brand', 'category'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product) => $this->mapProductForPos($product))
            ->filter()
            ->values();
    }

    public function index()
    {
        $products = $this->posCatalog();
        $brands = Brand::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $stores = Store::orderBy('name')->get();
        $walkIn = Customer::firstOrCreate(
            ['phone' => 'WALKIN'],
            ['name' => 'Walk-in Customer', 'email' => null, 'address' => null, 'added_by' => null, 'customer_type' => 'retail']
        );
        $customers = Customer::retail()->orderBy('name')->get();
        if (! $customers->contains('id', $walkIn->id)) {
            $customers = $customers->prepend($walkIn);
        } else {
            $customers = $customers->sortByDesc(function ($c) use ($walkIn) {
                return $c->id === $walkIn->id ? 1 : 0;
            })->values();
        }
        return view('erp.pos.index', compact('customers', 'products', 'brands', 'categories', 'stores'));
    }

    /** Search products for POS autocomplete / barcode. */
    public function productSearch(Request $request)
    {
        $term = $request->get('term', '');
        $query = Product::with('variations')->where('is_active', true);
        if (strlen($term) >= 1) {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%' . $term . '%')
                    ->orWhere('sku', 'like', '%' . $term . '%')
                    ->orWhere('barcode', 'like', '%' . $term . '%');
            });
        }
        $products = $query->limit(30)->get();
        $results = [];
        foreach ($products as $p) {
            if (!$this->productHasRetailStock($p)) {
                continue;
            }
            if ($p->product_type === 'variable') {
                foreach ($p->variations as $v) {
                    $stock = $this->retailUnitStock($p, $v);
                    if ($stock <= 0) {
                        continue;
                    }
                    $results[] = [
                        'id' => $p->id,
                        'variation_id' => $v->id,
                        'name' => $p->name,
                        'variation_name' => $v->variation_name,
                        'sku' => $v->sub_sku ?? $p->sku ?? $p->id,
                        'barcode' => $v->barcode ?? $p->barcode ?? null,
                        'price' => $this->retailUnitPrice($p, $v),
                        'stock' => $stock,
                    ];
                }
            } else {
                $stock = $this->retailUnitStock($p);
                $results[] = [
                    'id' => $p->id,
                    'variation_id' => null,
                    'name' => $p->name,
                    'variation_name' => null,
                    'sku' => $p->sku ?? (string) $p->id,
                    'barcode' => $p->barcode ?? null,
                    'price' => $this->retailUnitPrice($p),
                    'stock' => $stock,
                ];
            }
        }
        return response()->json(array_slice($results, 0, 15));
    }

    /** Lookup product by barcode (exact match). */
    public function barcodeLookup(string $code)
    {
        $code = trim($code);
        $product = Product::with('variations')->where('is_active', true)
            ->where('barcode', $code)->first();
        if (!$product && strlen($code) > 0) {
            $variation = \App\Models\ProductVariation::where('barcode', $code)->first();
            if ($variation) {
                $product = $variation->product;
                if ($product && $product->is_active) {
                    return response()->json([
                        'product_id' => $product->id,
                        'variation_id' => $variation->id,
                        'name' => $product->name . ' (' . $variation->variation_name . ')',
                        'price' => (float) ($variation->retail_price ?? $variation->default_sell_price ?? 0),
                        'stock' => $this->retailUnitStock($product, $variation),
                    ]);
                }
            }
        }
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }
        if ($product->product_type === 'variable' && $product->variations->count() > 0) {
            $v = $product->variations->first(fn (ProductVariation $var) => $this->retailUnitStock($product, $var) > 0)
                ?? $product->variations->first();
            if (!$v || $this->retailUnitStock($product, $v) <= 0) {
                return response()->json(['error' => 'No retail stock for this product'], 404);
            }
            return response()->json([
                'product_id' => $product->id,
                'variation_id' => $v->id,
                'name' => $product->name . ' (' . $v->variation_name . ')',
                'price' => $this->retailUnitPrice($product, $v),
                'stock' => $this->retailUnitStock($product, $v),
            ]);
        }
        if ($this->retailUnitStock($product) <= 0) {
            return response()->json(['error' => 'No retail stock for this product'], 404);
        }
        return response()->json([
            'product_id' => $product->id,
            'variation_id' => null,
            'name' => $product->name,
            'price' => $this->retailUnitPrice($product),
            'stock' => $this->retailUnitStock($product),
        ]);
    }

    /**
     * API: Products with retail prices & retail stock for POS.
     */
    public function getProducts(Request $request)
    {
        return response()->json($this->posCatalog());
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'discount_type' => 'nullable|in:fixed,percentage',
            'discount_value' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|in:cash,card,mobile,other',
            'payments' => 'nullable|array',
            'payments.*.method' => 'required_with:payments|string|in:cash,card,mobile,other',
            'payments.*.amount' => 'required_with:payments|numeric|min:0',
            'amount_tendered' => 'nullable|numeric|min:0',
        ]);

        $subtotal = 0;
        $orderItemsData = [];

        foreach ($request->items as $item) {
            $product = Product::find($item['product_id']);
            $quantity = (int) $item['quantity'];
            $price = 0;
            $variationId = null;

            if (isset($item['variation_id']) && $item['variation_id']) {
                $variation = \App\Models\ProductVariation::find($item['variation_id']);
                $price = $variation->retail_price ?? $variation->default_sell_price;
                $variationId = $variation->id;
            } else {
                $price = $product->retail_price ?? $product->selling_price;
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

        $discountType = $request->discount_type ?? 'fixed';
        $discountValue = (float) ($request->discount_value ?? 0);
        if ($discountType === 'percentage') {
            $discountValue = min(100, max(0, $discountValue));
            $discount = $subtotal * ($discountValue / 100);
        } else {
            $discount = min($discountValue, $subtotal);
        }
        $totalAmount = max(0, $subtotal - $discount);

        $order = Order::create([
            'order_number' => Order::nextOrderNumber(),
            'customer_id' => $request->customer_id,
            'user_id' => auth()->id(),
            'total_amount' => round($totalAmount, 2),
            'discount_amount' => round($discount, 2),
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'status' => 'pending',
            'order_date' => now(),
        ]);

        foreach ($orderItemsData as $data) {
            $order->items()->create($data);
        }

        $paymentsToAdd = [];
        if ($request->filled('payments') && is_array($request->payments)) {
            foreach ($request->payments as $p) {
                $amt = (float) ($p['amount'] ?? 0);
                if ($amt > 0) {
                    $paymentsToAdd[] = ['method' => $p['method'] ?? 'cash', 'amount' => $amt];
                }
            }
        } elseif ($request->filled('payment_method')) {
            $paymentsToAdd[] = ['method' => $request->payment_method, 'amount' => round($totalAmount, 2)];
        }

        $totalPaid = 0;
        foreach ($paymentsToAdd as $p) {
            $order->payments()->create([
                'amount' => $p['amount'],
                'payment_date' => now(),
                'payment_method' => $p['method'],
            ]);
            $totalPaid += $p['amount'];
        }
        if ($totalPaid >= round($totalAmount, 2)) {
            $order->update([
                'status' => 'completed',
                'invoice_number' => $order->invoice_number ?: Order::nextInvoiceNumber(),
            ]);
        }

        $amountTendered = $request->filled('amount_tendered') ? (float) $request->amount_tendered : null;
        $change = null;
        if ($amountTendered !== null && $amountTendered >= round($totalAmount, 2)) {
            $change = round($amountTendered - $totalAmount, 2);
        }

        return redirect()->route('pos.receipt', $order)
            ->with('success', 'Sale completed. Order ' . $order->order_number)
            ->with('pos_amount_tendered', $amountTendered)
            ->with('pos_change', $change);
    }

    /**
     * POS receipt view (print-friendly). After sale, redirect here and offer print.
     */
    public function receipt(Order $order)
    {
        $order->load(['customer', 'items.product', 'items.variation', 'payments']);
        $amountTendered = session('pos_amount_tendered');
        $change = session('pos_change');
        return view('erp.pos.receipt', compact('order', 'amountTendered', 'change'));
    }

    /**
     * Download receipt as PDF.
     */
    public function receiptPdf(Order $order)
    {
        $order->load(['customer', 'items.product', 'items.variation', 'payments']);
        $amountTendered = session('pos_amount_tendered');
        $change = session('pos_change');
        $pdf = Pdf::loadView('erp.pos.receipt', compact('order', 'amountTendered', 'change'));
        return $pdf->download('receipt-' . $order->order_number . '.pdf');
    }

    /**
     * Add retail customer from POS (popup).
     */
    public function storeCustomer(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
        ]);

        $customer = Customer::create([
            'name' => $request->name,
            'phone' => $request->phone ?: null,
            'email' => $request->email ?: null,
            'address' => $request->address ?: null,
            'added_by' => auth()->id(),
            'customer_type' => 'retail',
        ]);

        return response()->json([
            'success' => true,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
            ],
        ]);
    }
}
