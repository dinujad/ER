<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\StockChannelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockAdjustmentController extends Controller
{
    public function create()
    {
        $products = Product::query()
            ->with('unit:id,short_name')
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'product_type', 'unit_id']);

        return view('erp.inventory.stock_adjustment', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_variation_id' => 'nullable|exists:product_variations,id',
            'stock_channel' => 'required|in:wholesale,retail',
            'adjustment_type' => 'required|in:add,subtract',
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'required|string|max:500',
        ]);

        $product = Product::with('variations')->findOrFail($request->product_id);
        $variationId = $request->filled('product_variation_id') ? (int) $request->product_variation_id : null;

        if ($product->product_type === 'variable' && ! $variationId) {
            return back()->withInput()->with('error', 'Variable product — select a size/variation first.');
        }

        if ($variationId && ! $product->variations->contains('id', $variationId)) {
            return back()->withInput()->with('error', 'Selected variation does not belong to this product.');
        }

        if ($product->product_type !== 'variable') {
            $variationId = null;
        }

        $channel = $request->stock_channel;
        $stock = app(StockChannelService::class);
        $quantity = (float) $request->quantity;

        try {
            DB::transaction(function () use ($request, $product, $variationId, $channel, $stock, $quantity) {
                if ($request->adjustment_type === 'subtract') {
                    $available = $stock->balance($product->id, $channel, $variationId);
                    if ($quantity > $available) {
                        throw new \RuntimeException(
                            ucfirst($channel).' stock insufficient. Available: '.number_format($available, 2)
                        );
                    }
                    $quantity = -$quantity;
                }

                $stock->record(
                    $product->id,
                    $channel,
                    'adjustment',
                    $quantity,
                    $variationId,
                    [
                        'notes' => $request->notes,
                        'reference_type' => 'Manual Adjustment',
                    ]
                );
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'Could not save stock adjustment. Try again.');
        }

        $label = $request->adjustment_type === 'add' ? 'added' : 'removed';

        return redirect()
            ->route('stock-adjustment.create')
            ->with('success', 'Stock '.$label.' successfully for '.$product->name.'.');
    }
}
