<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class PosCartController extends Controller
{
    private const CART_KEY = 'pos_cart';
    private const META_KEY = 'pos_cart_meta';

    public static function getCart(): array
    {
        return Session::get(self::CART_KEY, []);
    }

    public static function getMeta(): array
    {
        return array_merge([
            'customer_id' => null,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'tax_type' => 'percentage',
            'tax_value' => 0,
            'shipping' => 0,
        ], Session::get(self::META_KEY, []));
    }

    public static function setCart(array $cart): void
    {
        Session::put(self::CART_KEY, $cart);
    }

    public static function setMeta(array $meta): void
    {
        Session::put(self::META_KEY, array_merge(self::getMeta(), $meta));
    }

    public static function clearCart(): void
    {
        Session::forget([self::CART_KEY, self::META_KEY]);
    }

    /** Recalculate totals from cart + meta. */
    public static function recalcTotals(): array
    {
        $cart = self::getCart();
        $meta = self::getMeta();
        $subtotal = 0;
        foreach ($cart as $row) {
            $subtotal += (float) $row['quantity'] * (float) $row['unit_price'];
        }
        $discountVal = (float) ($meta['discount_value'] ?? 0);
        $discountType = $meta['discount_type'] ?? 'fixed';
        if ($discountType === 'percentage') {
            $discountVal = min(100, max(0, $discountVal));
            $discount = $subtotal * ($discountVal / 100);
        } else {
            $discount = min($discountVal, $subtotal);
        }
        $afterDiscount = max(0, $subtotal - $discount);
        $taxVal = (float) ($meta['tax_value'] ?? 0);
        $taxType = $meta['tax_type'] ?? 'percentage';
        if ($taxType === 'percentage') {
            $taxVal = min(100, max(0, $taxVal));
            $tax = $afterDiscount * ($taxVal / 100);
        } else {
            $tax = $taxVal;
        }
        $shipping = (float) ($meta['shipping'] ?? 0);
        $total = $afterDiscount + $tax + $shipping;
        return [
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'tax' => round($tax, 2),
            'shipping' => round($shipping, 2),
            'total' => round($total, 2),
            'items_count' => count($cart),
            'items_sum' => array_sum(array_column($cart, 'quantity')),
        ];
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'variation_id' => 'nullable|exists:product_variations,id',
            'quantity' => 'required|integer|min:1',
        ]);
        $product = Product::find($request->product_id);
        $variationId = $request->filled('variation_id') ? (int) $request->variation_id : null;
        $qty = (int) $request->quantity;
        $unitPrice = $this->resolveUnitPrice($product, $variationId);
        $stock = $this->resolveStock($product, $variationId);
        $allowOversell = (bool) config('pos.allow_oversell', false);
        if (!$allowOversell && $qty > $stock) {
            return response()->json([
                'success' => false,
                'message' => 'Quantity exceeds available stock (' . $stock . ' pcs).',
            ], 422);
        }
        $cart = self::getCart();
        $key = $this->findCartKey($cart, $request->product_id, $variationId);
        $name = $product->name;
        $sku = $product->sku ?? (string) $product->id;
        $variationName = null;
        if ($variationId) {
            $v = ProductVariation::find($variationId);
            if ($v) {
                $variationName = $v->variation_name;
            }
        }
        if ($key !== null) {
            $newQty = $cart[$key]['quantity'] + $qty;
            if (!$allowOversell && $newQty > $stock) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quantity would exceed stock (' . $stock . ' pcs).',
                ], 422);
            }
            $cart[$key]['quantity'] = $newQty;
        } else {
            $cart[] = [
                'product_id' => $product->id,
                'variation_id' => $variationId,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'product_name' => $name,
                'sku' => $sku,
                'variation_name' => $variationName,
                'image' => $product->image,
            ];
        }
        self::setCart($cart);
        return response()->json(['success' => true, 'cart' => $cart, 'totals' => self::recalcTotals()]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'index' => 'required|integer|min:0',
            'quantity' => 'required|integer|min:0',
        ]);
        $cart = self::getCart();
        $index = (int) $request->index;
        if (!isset($cart[$index])) {
            return response()->json(['success' => false, 'message' => 'Item not found.'], 404);
        }
        if ((int) $request->quantity === 0) {
            array_splice($cart, $index, 1);
            self::setCart(array_values($cart));
            return response()->json(['success' => true, 'cart' => self::getCart(), 'totals' => self::recalcTotals()]);
        }
        $product = Product::find($cart[$index]['product_id']);
        $variationId = $cart[$index]['variation_id'] ?? null;
        $stock = $this->resolveStock($product, $variationId);
        $allowOversell = (bool) config('pos.allow_oversell', false);
        if (!$allowOversell && (int) $request->quantity > $stock) {
            return response()->json([
                'success' => false,
                'message' => 'Quantity cannot exceed stock (' . $stock . ' pcs).',
            ], 422);
        }
        $cart[$index]['quantity'] = (int) $request->quantity;
        self::setCart($cart);
        return response()->json(['success' => true, 'cart' => $cart, 'totals' => self::recalcTotals()]);
    }

    public function remove(Request $request)
    {
        $request->validate(['index' => 'required|integer|min:0']);
        $cart = self::getCart();
        $index = (int) $request->index;
        if (!isset($cart[$index])) {
            return response()->json(['success' => false, 'message' => 'Item not found.'], 404);
        }
        array_splice($cart, $index, 1);
        self::setCart(array_values($cart));
        return response()->json(['success' => true, 'cart' => self::getCart(), 'totals' => self::recalcTotals()]);
    }

    public function clear()
    {
        self::clearCart();
        return response()->json(['success' => true, 'cart' => [], 'totals' => self::recalcTotals()]);
    }

    public function updateMeta(Request $request)
    {
        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'discount_type' => 'nullable|in:fixed,percentage',
            'discount_value' => 'nullable|numeric|min:0',
            'tax_type' => 'nullable|in:fixed,percentage',
            'tax_value' => 'nullable|numeric|min:0',
            'shipping' => 'nullable|numeric|min:0',
        ]);
        $meta = [];
        if ($request->has('customer_id')) $meta['customer_id'] = $request->customer_id ? (int) $request->customer_id : null;
        if ($request->has('discount_type')) $meta['discount_type'] = $request->discount_type;
        if ($request->has('discount_value')) $meta['discount_value'] = (float) $request->discount_value;
        if ($request->has('tax_type')) $meta['tax_type'] = $request->tax_type;
        if ($request->has('tax_value')) $meta['tax_value'] = (float) $request->tax_value;
        if ($request->has('shipping')) $meta['shipping'] = (float) $request->shipping;
        self::setMeta($meta);
        return response()->json(['success' => true, 'totals' => self::recalcTotals(), 'meta' => self::getMeta()]);
    }

    public function totals()
    {
        return response()->json([
            'cart' => self::getCart(),
            'meta' => self::getMeta(),
            'totals' => self::recalcTotals(),
        ]);
    }

    private function findCartKey(array $cart, int $productId, ?int $variationId): ?int
    {
        foreach ($cart as $i => $row) {
            if ((int) $row['product_id'] === $productId && (int) ($row['variation_id'] ?? 0) === (int) $variationId) {
                return $i;
            }
        }
        return null;
    }

    private function resolveUnitPrice(Product $product, ?int $variationId): float
    {
        if ($variationId) {
            $v = ProductVariation::find($variationId);
            if ($v) {
                return (float) ($v->retail_price ?? $v->default_sell_price ?? 0);
            }
        }
        return (float) ($product->retail_price ?? $product->selling_price ?? 0);
    }

    private function resolveStock(Product $product, ?int $variationId): int
    {
        $stock = app(\App\Services\StockChannelService::class);

        if ($variationId) {
            return (int) $stock->balance(
                $product->id,
                \App\Services\StockChannelService::CHANNEL_RETAIL,
                $variationId
            );
        }

        return (int) $stock->balance($product->id, \App\Services\StockChannelService::CHANNEL_RETAIL);
    }
}
