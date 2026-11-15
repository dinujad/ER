<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product; // Import Product model
use App\Models\OrderItem;
use App\Models\StockLedger;
use App\Services\StockChannelService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    protected function generateNumericSku(int $length = 10): string
    {
        do {
            $sku = str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }

    protected function generateNumericVariationSku(string $baseSku, int $length = 4): string
    {
        do {
            $suffix = str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
            $subSku = $baseSku . '-' . $suffix;
        } while (\App\Models\ProductVariation::where('sub_sku', $subSku)->exists());

        return $subSku;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'all');

        $query = Product::with(['category', 'brand', 'unit', 'variations']);

        if ($tab === 'gproducts') {
            $query->where('status', 'draft')->where('classification', 'standard');
        } elseif ($tab === 'dproducts') {
            $query->where('status', 'draft')->where('classification', 'damage');
        } elseif (in_array($tab, ['wholesale', 'retail'], true)) {
            $query->where(function ($q) {
                $q->where('status', '!=', 'draft')->orWhereNull('status');
            });
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->trim();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($tab === 'wholesale') {
            $query->withSum(
                ['stockLedgers as wholesale_stock_sum' => fn ($q) => $q->where('channel', 'wholesale')],
                'quantity'
            )->orderByDesc('wholesale_stock_sum');
        } elseif ($tab === 'retail') {
            $query->withSum(
                ['stockLedgers as retail_stock_sum' => fn ($q) => $q->where('channel', 'retail')],
                'quantity'
            )->orderByDesc('retail_stock_sum');
        } else {
            $query->latest();
        }

        $products = $query->paginate(20)->withQueryString();

        $stockChannel = in_array($tab, ['wholesale', 'retail'], true) ? $tab : null;
        $gproductsCount = Product::query()
            ->where('status', 'draft')
            ->where('classification', 'standard')
            ->count();

        return view('erp.products.index', compact('products', 'tab', 'stockChannel', 'gproductsCount'));
    }

    public function create()
    {
        $categories = \App\Models\Category::all();
        $brands = \App\Models\Brand::all();
        $units = \App\Models\Unit::all();
        $warranties = \App\Models\Warranty::all();
        
        return view('erp.products.create', compact('categories', 'brands', 'units', 'warranties'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|unique:products,sku',
            'category_id' => 'required|exists:categories,id',
            'unit_id' => 'required|exists:units,id',
            'cost_price' => 'nullable|numeric|min:0', // Nullable for variable parent
            'selling_price' => 'nullable|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'retail_price' => 'nullable|numeric|min:0',
            'product_type' => 'required|in:simple,variable',
            'alert_quantity' => 'nullable|integer|min:0',
            'image' => 'nullable|image|max:2048',
        ]);

        try {
            DB::beginTransaction();

            // 1. Handle Image Upload
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('products', 'public');
            }

            // 2. Create Parent Product
            $baseSku = $request->sku ? preg_replace('/\D+/', '', (string) $request->sku) : $this->generateNumericSku();
            if ($baseSku === '') {
                $baseSku = $this->generateNumericSku();
            }
            $product = Product::create([
                'name' => $request->name,
                'sku' => $baseSku,
                'product_type' => $request->product_type,
                'barcode_type' => $request->barcode_type ?? 'C128',
                'category_id' => $request->category_id,
                'brand_id' => $request->brand_id,
                'unit_id' => $request->unit_id,
                'description' => $request->description,
                'cost_price' => $request->cost_price ?? 0,
                'selling_price' => $request->selling_price ?? 0,
                'wholesale_price' => $request->wholesale_price ?? $request->selling_price ?? 0,
                'retail_price' => $request->retail_price ?? $request->selling_price ?? 0,
                'tax_rate' => $request->tax_rate ?? 0,
                'alert_quantity' => $request->alert_quantity ?? 0,
                'image' => $imagePath,
                'enable_stock' => $request->has('enable_stock'),
                'is_active' => true,
            ]);

            // Attributes are UI-only (define combo labels) — no separate Attribute model needed.

            // 3. Handle Simple Product Stock
            if ($request->product_type == 'simple' && $request->has('enable_stock') && $request->filled('opening_stock')) {
                if ($request->opening_stock > 0) {
                    $channel = $request->input('opening_stock_channel', StockChannelService::CHANNEL_WHOLESALE);
                    app(StockChannelService::class)->record(
                        $product->id,
                        $channel === StockChannelService::CHANNEL_RETAIL
                            ? StockChannelService::CHANNEL_RETAIL
                            : StockChannelService::CHANNEL_WHOLESALE,
                        'opening',
                        (float) $request->opening_stock,
                        null,
                        ['notes' => 'Opening Stock (' . $channel . ')']
                    );
                }
            }

            // 4. Handle Variations — prices optional per row at create time
            if ($request->product_type == 'variable' && $request->has('variations')) {
                foreach ($request->variations as $v) {
                    if (empty($v['name'])) {
                        continue;
                    }

                    $skuSuffix = trim((string) ($v['sku'] ?? ''));
                    $subSku = $skuSuffix !== ''
                        ? $product->sku . '-' . preg_replace('/\s+/', '', strtoupper($skuSuffix))
                        : $this->generateNumericVariationSku($product->sku);

                    $sellPrice = (float) ($v['selling_price'] ?? $v['default_sell_price'] ?? 0);
                    $costPrice = (float) ($v['cost_price'] ?? $v['default_purchase_price'] ?? 0);

                    \App\Models\ProductVariation::create([
                        'product_id' => $product->id,
                        'variation_name' => $v['name'],
                        'sub_sku' => $subSku,
                        'default_purchase_price' => $costPrice,
                        'default_sell_price' => $sellPrice,
                        'wholesale_price' => (float) ($v['wholesale_price'] ?? $sellPrice),
                        'retail_price' => (float) ($v['retail_price'] ?? $sellPrice),
                        'barcode_type' => $request->barcode_type ?? 'C128',
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('products.index')->with('success', 'Product saved successfully.');

        } catch (\Exception $e) {
            DB::rollback();
             // Delete image if uploaded?
            return back()->with('error', 'Error saving product: ' . $e->getMessage())->withInput();
        }
    }

    public function storeAjax(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);

        try {
            DB::beginTransaction();

            // 1. Get or Create Default Category
            $category = \App\Models\Category::first();
            if (!$category) {
                $category = \App\Models\Category::create([
                    'name' => 'General', 
                    'slug' => 'general'
                ]);
            }

            // 2. Get or Create Default Unit
            $unit = \App\Models\Unit::first();
            if (!$unit) {
                $unit = \App\Models\Unit::create([
                    'name' => 'Pieces', 
                    'short_name' => 'Pc'
                ]);
            }

            // 3. Create Product (Matching 'store' method fields)
            $product = \App\Models\Product::create([
                'name' => $request->name,
                'sku' => $this->generateNumericSku(),
                'product_type' => 'simple',
                'category_id' => $category->id,
                'unit_id' => $unit->id,
                'cost_price' => 0,
                'selling_price' => 0,
                'barcode_type' => 'C128', // Standard default
                'is_active' => true,
            ]);

            DB::commit();

            return response()->json([
                'id' => $product->id, 
                'name' => $product->name,
                'success' => true
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function printBarcode(Product $product)
    {
        $product->load('variations');
        $focusVariationId = (int) request('variation', 0);

        return view('erp.products.print_barcode', compact('product', 'focusVariationId'));
    }

    public function stockHistory(Product $product)
    {
        $product->load('variations');
        $ledgers = $product->stockLedgers()
            ->with('creator')
            ->latest()
            ->paginate(50);
            
        return view('erp.products.stock_history', compact('product', 'ledgers'));
    }

    public function stockAdjustmentData(Product $product)
    {
        $product->load('variations');
        $stock = app(StockChannelService::class);

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'product_type' => $product->product_type,
            'unit' => $product->unit?->short_name,
            'current_stock' => (float) $product->current_stock,
            'wholesale_stock' => $stock->balance($product->id, StockChannelService::CHANNEL_WHOLESALE),
            'retail_stock' => $stock->balance($product->id, StockChannelService::CHANNEL_RETAIL),
            'variations' => $product->variations->map(function ($variation) use ($product, $stock) {
                return [
                    'id' => $variation->id,
                    'name' => $variation->variation_name,
                    'current_stock' => (float) $variation->current_stock,
                    'wholesale_stock' => $stock->balance($product->id, StockChannelService::CHANNEL_WHOLESALE, $variation->id),
                    'retail_stock' => $stock->balance($product->id, StockChannelService::CHANNEL_RETAIL, $variation->id),
                ];
            })->values(),
        ]);
    }

    public function transferChannelStock(Request $request, Product $product)
    {
        $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'product_variation_id' => 'nullable|exists:product_variations,id',
            'notes' => 'nullable|string|max:500',
            'direction' => 'nullable|in:wholesale_to_retail,retail_to_wholesale',
        ]);

        $variationId = $request->filled('product_variation_id')
            ? (int) $request->product_variation_id
            : null;

        if ($variationId && !$product->variations()->where('id', $variationId)->exists()) {
            return back()->with('error', 'Invalid variation for this product.');
        }

        if ($product->product_type === 'variable' && !$variationId) {
            return back()->with('error', 'Variable product එකක් — transfer කරන්න variation එක select කරන්න.');
        }

        $direction = $request->input('direction', 'wholesale_to_retail');
        $toRetail = $direction === 'wholesale_to_retail';

        try {
            $stock = app(StockChannelService::class);
            if ($toRetail) {
                $stock->transferWholesaleToRetail(
                    $product,
                    (float) $request->quantity,
                    $variationId,
                    $request->notes
                );
            } else {
                $stock->transferRetailToWholesale(
                    $product,
                    (float) $request->quantity,
                    $variationId,
                    $request->notes
                );
            }
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $message = $toRetail
            ? 'Stock moved from Wholesale to Retail successfully.'
            : 'Stock moved from Retail to Wholesale successfully.';

        return back()->with('success', $message);
    }

    public function edit(Product $product)
    {
        $product->load(['variations', 'category', 'brand', 'unit']);

        $categories = \App\Models\Category::all();
        $brands = \App\Models\Brand::all();
        $units = \App\Models\Unit::all();
        $warranties = \App\Models\Warranty::all();
        $manufacturingCost = $product->latestManufacturingCostMeta();
        
        return view('erp.products.edit', compact('product', 'categories', 'brands', 'units', 'warranties', 'manufacturingCost'));
    }

    public function publish(Product $product)
    {
        if ($product->classification === 'damage') {
            return back()->with('error', 'Damage products stay in Dproducts until handled separately.');
        }

        $product->update([
            'status' => 'active',
            'is_active' => true,
        ]);

        return redirect()
            ->route('products.index', ['tab' => 'wholesale'])
            ->with('success', $product->name . ' published — now visible under Wholesale / Retail.');
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|unique:products,sku,' . $product->id,
            'category_id' => 'required|exists:categories,id',
            'unit_id' => 'required|exists:units,id',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'retail_price' => 'nullable|numeric|min:0',
        ]);

        $product->update($request->except(['opening_stock', 'product_type'])); // Prevent type change and re-opening stock

        return redirect()->route('products.index')->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);

        $hasSales = OrderItem::where('product_id', $product->id)->exists();
        if ($hasSales) {
            return redirect()->route('products.index')
                ->with('product_delete_error', 'මෙම product එකට sales transactions තියෙන නිසා system එකෙන් remove කරන්න බැහැ.');
        }

        DB::transaction(function () use ($product) {
            $product->variations()->delete();
            $product->delete();
        });

        return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
    }
}
