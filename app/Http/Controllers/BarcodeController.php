<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PackingBatchVariation;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\BarcodeSetting;
use App\Support\ProductLabel;

class BarcodeController extends Controller
{
    public function index()
    {
        BarcodeSetting::ensureDefaults();
        $settings = BarcodeSetting::orderByDesc('is_default')->orderBy('name')->get();

        return view('erp.barcode.index', compact('settings'));
    }

    public function storeSetting(Request $request)
    {
        BarcodeSetting::create($this->validatedSetting($request));

        return back()->with('success', 'Barcode template saved.');
    }

    public function updateSetting(Request $request, BarcodeSetting $barcodeSetting)
    {
        $barcodeSetting->update($this->validatedSetting($request));

        return back()->with('success', 'Barcode template updated.');
    }

    public function destroySetting(BarcodeSetting $barcodeSetting)
    {
        $barcodeSetting->delete();
        BarcodeSetting::ensureDefaults();

        return back()->with('success', 'Barcode template deleted.');
    }

    /** One C128 sticker so the scanner can be tested. */
    public function testPrint()
    {
        $setting = BarcodeSetting::fallback();
        $items = [[
            'name' => 'SCAN TEST C128',
            'price' => '',
            'sku' => 'DERMAS-TEST-C128',
            'barcode' => 'DERMAS-TEST-C128',
        ]];

        return view('erp.barcode.preview', compact('setting', 'items'));
    }

    protected function validatedSetting(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'sticker_width' => 'required|numeric|min:0.3|max:12',
            'sticker_height' => 'required|numeric|min:0.3|max:12',
            'paper_width' => 'required|numeric|min:0.3|max:12',
            'stickers_per_row' => 'required|integer|min:1|max:10',
            'top_margin' => 'nullable|numeric|min:0|max:2',
            'left_margin' => 'nullable|numeric|min:0|max:2',
            'row_distance' => 'nullable|numeric|min:0|max:2',
            'col_distance' => 'nullable|numeric|min:0|max:2',
        ]);

        $data['is_continuous'] = $request->boolean('is_continuous');
        $data['top_margin'] = (float) ($data['top_margin'] ?? 0);
        $data['left_margin'] = (float) ($data['left_margin'] ?? 0);
        $data['row_distance'] = (float) ($data['row_distance'] ?? 0);
        $data['col_distance'] = (float) ($data['col_distance'] ?? 0);

        return $data;
    }
    
    // The Main Print Interface
    public function printForm()
    {
        BarcodeSetting::ensureDefaults();
        $settings = BarcodeSetting::orderByDesc('is_default')->orderBy('name')->get();
        $products = Product::query()
            ->with('variations')
            ->orderBy('name')
            ->get();

        $packedQty = collect();
        try {
            $packedQty = PackingBatchVariation::query()
                ->selectRaw('product_variation_id, SUM(quantity) as qty')
                ->whereHas('packingBatch', fn ($q) => $q->where('type', 'good'))
                ->groupBy('product_variation_id')
                ->pluck('qty', 'product_variation_id');
        } catch (\Throwable $e) {
            report($e);
        }

        return view('erp.barcode.print', compact('settings', 'products', 'packedQty'));
    }

    public function generatePdf(Request $request)
    {
        $setting = $request->filled('setting_id')
            ? (BarcodeSetting::find($request->setting_id) ?: BarcodeSetting::fallback())
            : BarcodeSetting::fallback();

        $items = [];

        foreach ((array) $request->input('products', []) as $prodId => $qty) {
            $qty = (int) $qty;
            if ($qty <= 0) {
                continue;
            }
            $product = Product::find($prodId);
            if (!$product) {
                continue;
            }
            for ($i = 0; $i < $qty; $i++) {
                $items[] = [
                    'name' => $product->name,
                    'price' => number_format((float) $product->selling_price, 2),
                    'sku' => ProductLabel::productBarcode($product),
                    'barcode' => ProductLabel::productBarcode($product),
                ];
            }
        }

        foreach ((array) $request->input('variations', []) as $varId => $qty) {
            $qty = (int) $qty;
            if ($qty <= 0) {
                continue;
            }
            $var = ProductVariation::with('product')->find($varId);
            if (!$var) {
                continue;
            }
            $label = trim(($var->product?->name ?? 'Product') . ' (' . ProductLabel::attributesLabel($var) . ')');
            for ($i = 0; $i < min($qty, 2000); $i++) {
                $items[] = [
                    'name' => $label,
                    'price' => number_format(ProductLabel::sellPrice($var), 2),
                    'sku' => ProductLabel::variationBarcode($var),
                    'barcode' => ProductLabel::variationBarcode($var),
                ];
            }
        }

        if ($items === []) {
            return redirect()
                ->route('barcode.print')
                ->with('error', 'Select a product / variation and qty before preview.');
        }

        return view('erp.barcode.preview', compact('setting', 'items'));
    }
}
