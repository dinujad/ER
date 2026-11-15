<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\ProductionStage;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductRecipeController extends Controller
{
    public function index()
    {
        $recipes = ProductRecipe::with('product', 'ingredients')->latest()->get();

        return view('erp.recipes.index', compact('recipes'));
    }

    public function create()
    {
        $materials = Material::orderBy('name')->get();
        $stages = ProductionStage::orderBy('order_index')->get();
        $initialOtherCosts = old('other_costs', []);

        return view('erp.recipes.create', compact('materials', 'stages', 'initialOtherCosts'));
    }

    public function store(Request $request)
    {
        $request->validate($this->validationRules());

        try {
            DB::beginTransaction();

            $productId = $this->resolveProductId($request);

            $recipe = ProductRecipe::create([
                'product_id' => $productId,
                'name' => $request->name,
                'instructions' => $request->instructions,
                'total_output_quantity' => $request->total_output_quantity ?? 1,
                'output_unit' => $request->output_unit ?? 'pcs',
                'wastage_percent' => $request->wastage_percent ?? 0,
                'production_cost' => 0,
            ]);

            $this->syncSteps($recipe, $request->steps ?? []);
            $this->syncOtherCosts($recipe, $request->other_costs ?? []);
            $recipe->syncProductionCost();

            DB::commit();

            return redirect()->route('product-recipes.index')->with('success', 'Recipe created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Error creating recipe: ' . $e->getMessage())->withInput();
        }
    }

    public function show(ProductRecipe $productRecipe)
    {
        $productRecipe->load(['product', 'steps.ingredients.material', 'otherCosts']);

        return view('erp.recipes.show', compact('productRecipe'));
    }

    public function edit(ProductRecipe $productRecipe)
    {
        $productRecipe->load(['product', 'steps.ingredients.material', 'otherCosts']);
        $materials = Material::orderBy('name')->get();
        $stages = ProductionStage::orderBy('order_index')->get();

        $initialOtherCosts = old('other_costs', $productRecipe->otherCosts->map(fn ($row) => [
            'description' => $row->description,
            'amount' => $row->amount,
        ])->values()->all());

        return view('erp.recipes.edit', compact('productRecipe', 'materials', 'stages', 'initialOtherCosts'));
    }

    public function update(Request $request, ProductRecipe $productRecipe)
    {
        $request->validate($this->validationRules());

        try {
            DB::beginTransaction();

            $productRecipe->update([
                'name' => $request->name,
                'instructions' => $request->instructions,
                'total_output_quantity' => $request->total_output_quantity ?? 1,
                'output_unit' => $request->output_unit ?? 'pcs',
                'wastage_percent' => $request->wastage_percent ?? 0,
            ]);

            $productRecipe->steps()->each(fn ($step) => $step->ingredients()->delete());
            $productRecipe->steps()->delete();

            $this->syncSteps($productRecipe, $request->steps ?? []);
            $this->syncOtherCosts($productRecipe, $request->other_costs ?? []);
            $productRecipe->syncProductionCost();

            DB::commit();

            return redirect()->route('product-recipes.show', $productRecipe->id)->with('success', 'Recipe updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Error updating recipe: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(ProductRecipe $productRecipe)
    {
        $productRecipe->delete();

        return redirect()->route('product-recipes.index')->with('success', 'Recipe deleted successfully.');
    }

    public function storeProduct(Request $request)
    {
        $request->validate(['name' => 'required|unique:products,name']);
        $product = Product::create(['name' => $request->name]);

        return response()->json($product);
    }

    public function getRecipeByProduct($productId)
    {
        $recipe = ProductRecipe::where('product_id', $productId)->with(['steps' => function ($q) {
            $q->orderBy('order_index');
        }, 'steps.ingredients.material', 'otherCosts'])->latest()->first();

        if (!$recipe) {
            return response()->json(['error' => 'No recipe found for this product'], 404);
        }

        return response()->json($recipe);
    }

    private function validationRules(): array
    {
        return [
            'product_id' => 'nullable|exists:products,id',
            'name' => 'required|string|max:255',
            'total_output_quantity' => 'nullable|numeric|min:0',
            'output_unit' => 'nullable|string',
            'other_costs' => 'nullable|array',
            'other_costs.*.description' => 'nullable|string|max:500',
            'other_costs.*.amount' => 'nullable|numeric|min:0',
            'steps' => 'required|array|min:1',
            'steps.*.name' => 'required|string',
            'steps.*.production_stage_id' => 'nullable|exists:production_stages,id',
            'steps.*.ingredients' => 'nullable|array',
            'steps.*.ingredients.*.material_id' => 'required|exists:materials,id',
            'steps.*.ingredients.*.quantity' => 'required|numeric|min:0.0001',
        ];
    }

    private function resolveProductId(Request $request): int
    {
        if ($request->product_id) {
            return (int) $request->product_id;
        }

        $category = Category::first() ?? Category::create(['name' => 'General', 'slug' => 'general']);
        $unit = Unit::first() ?? Unit::create(['name' => 'Pieces', 'short_name' => 'Pc']);

        $product = Product::firstOrCreate(
            ['name' => $request->name],
            [
                'sku' => str_pad((string) random_int(0, 9999999999), 10, '0', STR_PAD_LEFT),
                'product_type' => 'simple',
                'category_id' => $category->id,
                'unit_id' => $unit->id,
                'cost_price' => 0,
                'selling_price' => 0,
                'barcode_type' => 'C128',
                'is_active' => true,
            ]
        );

        return $product->id;
    }

    private function syncSteps(ProductRecipe $recipe, array $steps): void
    {
        foreach ($steps as $stepIndex => $stepData) {
            $step = $recipe->steps()->create([
                'name' => $stepData['name'],
                'production_stage_id' => $stepData['production_stage_id'] ?? null,
                'description' => $stepData['description'] ?? null,
                'order_index' => $stepIndex,
            ]);

            foreach ($stepData['ingredients'] ?? [] as $ing) {
                $material = Material::find($ing['material_id']);
                $cost = $material ? ((float) $material->unit_price * (float) $ing['quantity']) : 0;

                $step->ingredients()->create([
                    'material_id' => $ing['material_id'],
                    'quantity' => $ing['quantity'],
                    'wastage_percent' => $ing['wastage_percent'] ?? 0,
                    'cost' => $cost,
                ]);
            }
        }
    }

    private function syncOtherCosts(ProductRecipe $recipe, array $otherCosts): void
    {
        $recipe->otherCosts()->delete();

        foreach ($otherCosts as $index => $row) {
            $description = trim((string) ($row['description'] ?? ''));
            $amount = (float) ($row['amount'] ?? 0);

            if ($description === '' && $amount <= 0) {
                continue;
            }

            $recipe->otherCosts()->create([
                'description' => $description !== '' ? $description : 'Other',
                'amount' => $amount,
                'order_index' => $index,
            ]);
        }
    }
}
