<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductImportController extends Controller
{
    public function create()
    {
        return view('erp.products.import');
    }

    public function store(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('csv_file');
        $csvData = array_map('str_getcsv', file($file->getRealPath()));
        
        // Remove header row
        $header = array_shift($csvData);
        
        // Expected header mapping (Name, SKU, Category, Brand, Unit, Cost, Price, Stock)
        $importedCount = 0;
        $errors = [];

        try {
            DB::beginTransaction();

            $defaultUnit = Unit::firstOrCreate(['name' => 'Pieces'], ['short_name' => 'Pcs']);

            foreach ($csvData as $index => $row) {
                // Skip empty rows
                if (count($row) < 3) continue;

                // Simple mapping based on index (Assume Template matches)
                // 0: Name, 1: SKU, 2: Category, 3: Brand, 4: Cost, 5: Price, 6: Stock
                $name = $row[0] ?? null;
                $sku = $row[1] ?? null;
                $categoryName = $row[2] ?? 'General';
                $brandName = $row[3] ?? 'Generic';
                $cost = isset($row[4]) && is_numeric($row[4]) ? $row[4] : 0;
                $price = isset($row[5]) && is_numeric($row[5]) ? $row[5] : 0;
                $stock = isset($row[6]) && is_numeric($row[6]) ? $row[6] : 0;

                if (!$name) {
                    $errors[] = "Row " . ($index + 2) . ": Name is required.";
                    continue;
                }

                if ($sku && Product::where('sku', $sku)->exists()) {
                    $errors[] = "Row " . ($index + 2) . ": SKU '$sku' already exists. Skipped.";
                    continue;
                }

                // Handle Relations
                $category = Category::firstOrCreate(['name' => $categoryName], ['slug' => Str::slug($categoryName)]);
                $brand = Brand::firstOrCreate(['name' => $brandName]);

                // Create Product
                $product = Product::create([
                    'name' => $name,
                    'sku' => $sku,
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'unit_id' => $defaultUnit->id, // Defaulting for now to avoid complexity
                    'cost_price' => $cost,
                    'selling_price' => $price,
                    'product_type' => 'simple',
                    'is_active' => true
                ]);

                // Opening Stock
                if ($stock > 0) {
                    \App\Models\StockLedger::create([
                        'product_id' => $product->id,
                        'channel' => 'wholesale',
                        'transaction_type' => 'opening',
                        'quantity' => $stock,
                        'created_by' => auth()->id(),
                        'notes' => 'Imported Opening Stock (wholesale)',
                    ]);
                }

                $importedCount++;
            }

            if (count($errors) > 0 && $importedCount == 0) {
                DB::rollback();
                return back()->with('error', 'Import failed. No products imported. Check errors: ' . implode(', ', $errors));
            }

            DB::commit();
            
            $msg = "Imported $importedCount products successfully.";
            if (count($errors) > 0) {
                $msg .= " Some rows skipped: " . implode(', ', $errors);
            }

            return redirect()->route('products.index')->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Critical Error: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="product_import_template.csv"',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Name', 'SKU', 'Category', 'Brand', 'Cost Price', 'Selling Price', 'Opening Stock']);
            fputcsv($file, ['Example Shirt', 'SHIRT001', 'Mens Wear', 'Nike', '1500', '2500', '10']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
