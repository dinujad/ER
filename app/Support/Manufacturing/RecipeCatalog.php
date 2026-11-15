<?php

namespace App\Support\Manufacturing;

use App\Models\Product;
use App\Models\ProductRecipe;
use Illuminate\Support\Collection;

final class RecipeCatalog
{
    /** Products that have at least one recipe (for job send-to-cutting). */
    public static function productsWithRecipes(): Collection
    {
        $ids = ProductRecipe::query()->distinct()->pluck('product_id');

        return Product::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get();
    }
}
