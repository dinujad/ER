<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\TrendingProductsReportService;
use Illuminate\Http\Request;

class TrendingProductsReportController extends Controller
{
    public function show(Request $request, Product $product)
    {
        [$from, $to] = $this->resolveDates($request);
        $data = app(TrendingProductsReportService::class)->buildProductDetail($product, $from, $to, $request);

        return view('erp.reports.trending-products.show', compact('product', 'data', 'from', 'to'));
    }

    /** @return array{0: string, 1: string} */
    private function resolveDates(Request $request): array
    {
        $from = $request->get('from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }
}
