<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Support\Facades\DB;

class ProductVariationController extends Controller
{
    protected function generateNumericSubSku(string $baseSku, int $length = 4): string
    {
        do {
            $suffix = str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
            $subSku = $baseSku . '-' . $suffix;
        } while (ProductVariation::where('sub_sku', $subSku)->exists());

        return $subSku;
    }

    public function storeMany(Request $request, Product $product)
    {
        $request->validate([
            'variations' => 'required|array',
            'variations.*.variation_name' => 'required_without:variations.*.name|string',
            'variations.*.name' => 'nullable|string',
            'variations.*.cost_price' => 'nullable|numeric|min:0',
            'variations.*.selling_price' => 'nullable|numeric|min:0',
            'variations.*.wholesale_price' => 'nullable|numeric|min:0',
            'variations.*.retail_price' => 'nullable|numeric|min:0',
            'manufacturing_default_cost' => 'nullable|numeric|min:0',
            'manufacturing_mode' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            $manufacturingMode = $request->boolean('manufacturing_mode')
                || $request->filled('manufacturing_default_cost');
            $mfgDefaultCost = (float) ($request->manufacturing_default_cost ?? 0);
            $created = 0;

            foreach ($request->variations as $v) {
                $variationName = $v['variation_name'] ?? $v['name'] ?? null;
                if (!$variationName) {
                    continue;
                }

                $skuSuffix = trim((string) ($v['sku_suffix'] ?? $v['sku'] ?? ''));
                $skuSuffix = $skuSuffix !== '' ? preg_replace('/\s+/', '', strtoupper($skuSuffix)) : '';
                $baseSku = trim((string) ($product->sku ?? '')) !== ''
                    ? $product->sku
                    : ('SKU-' . $product->id);
                $fullSku = $skuSuffix !== '' ? ($baseSku . '-' . $skuSuffix) : $this->generateNumericSubSku($baseSku);

                // Manufacturing QC: sizes only — no prices. Cost applied on Convert to Product.
                if ($manufacturingMode) {
                    $costPrice = 0;
                    $sellPrice = 0;
                    $wholesale = 0;
                    $retail = 0;
                } else {
                    $sellPrice = (float) ($v['selling_price'] ?? $v['default_sell_price'] ?? 0);
                    $costPrice = (float) ($v['cost_price'] ?? $v['default_purchase_price'] ?? 0);
                    if ($costPrice <= 0 && $mfgDefaultCost > 0) {
                        $costPrice = $mfgDefaultCost;
                    }
                    $wholesale = (float) ($v['wholesale_price'] ?? $sellPrice);
                    $retail = (float) ($v['retail_price'] ?? $sellPrice);
                }

                ProductVariation::create([
                    'product_id' => $product->id,
                    'variation_name' => $variationName,
                    'sub_sku' => $fullSku,
                    'default_purchase_price' => $costPrice,
                    'default_sell_price' => $sellPrice,
                    'wholesale_price' => $wholesale,
                    'retail_price' => $retail,
                    'barcode_type' => $product->barcode_type ?? 'C128',
                ]);
                $created++;
            }

            // Ensure parent is marked as variable
            if ($product->product_type !== 'variable') {
                $product->update(['product_type' => 'variable']);
            }

            if (!$manufacturingMode && $mfgDefaultCost > 0 && (float) $product->cost_price <= 0) {
                $product->update(['cost_price' => $mfgDefaultCost]);
            }

            DB::commit();
            $msg = $manufacturingMode
                ? ($created === 1
                    ? '1 variation saved (sizes only). Convert පස්සේ cost auto; prices admin set කරයි.'
                    : "{$created} variations saved (sizes only). Convert පස්සේ cost auto; prices admin set කරයි.")
                : ($created === 1
                    ? '1 variation saved on product.'
                    : "{$created} variations saved on product.");
            return back()->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Error generating variations: ' . $e->getMessage());
        }
    }

    public function store(Request $request, Product $product)
    {
        $request->validate([
            'variation_name' => 'required|string|max:255',
            'sku' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // 1. Create Variation Record directly
            ProductVariation::create([
                'product_id' => $product->id,
                'variation_name' => $request->variation_name,
                'sub_sku' => $request->filled('sku')
                    ? $product->sku . '-' . preg_replace('/\s+/', '', strtoupper((string) $request->sku))
                    : $this->generateNumericSubSku($product->sku),
                'default_purchase_price' => 0,
                'default_sell_price' => 0,
                'wholesale_price' => 0,
                'retail_price' => 0,
                'barcode_type' => $product->barcode_type ?? 'C128',
            ]);

            // Ensure parent is marked as variable
            if ($product->product_type !== 'variable') {
                $product->update(['product_type' => 'variable']);
            }

            DB::commit();
            return back()->with('success', 'Variation added successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Error adding variation: ' . $e->getMessage());
        }
    }

    public function updatePrices(Request $request, Product $product, $variationId)
    {
        $request->validate([
            'default_sell_price' => 'required|numeric|min:0',
            'default_purchase_price' => 'nullable|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'retail_price' => 'nullable|numeric|min:0',
        ]);

        $variation = ProductVariation::where('product_id', $product->id)->findOrFail($variationId);

        $variation->update([
            'default_purchase_price' => $request->default_purchase_price ?? $variation->default_purchase_price,
            'default_sell_price' => $request->default_sell_price,
            'wholesale_price' => $request->wholesale_price ?? $request->default_sell_price,
            'retail_price' => $request->retail_price ?? $request->default_sell_price,
        ]);

        return back()->with('success', 'Prices updated for ' . $variation->variation_name . '.');
    }

    public function destroy(Product $product, $variationId)
    {
        try {
            $variation = ProductVariation::where('product_id', $product->id)
                ->where('id', $variationId)
                ->firstOrFail();

            $variation->delete();

            return back()->with('success', 'Variation removed successfully.');

        } catch (\Exception $e) {
            return back()->with('error', 'Error removing variation: ' . $e->getMessage());
        }
    }
}
