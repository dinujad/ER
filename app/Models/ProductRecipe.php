<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductRecipe extends Model
{
    protected $fillable = [
        'product_id', 
        'name', 
        'instructions', 
        'total_output_quantity', 
        'output_unit', 
        'wastage_percent', 
        'production_cost',
    ];

    protected $casts = [
        'production_cost' => 'decimal:2',
    ];

    public function ingredientsMaterialCost(): float
    {
        return (float) $this->ingredients()->sum('cost');
    }

    public function otherCostsTotal(): float
    {
        if ($this->relationLoaded('otherCosts')) {
            return round((float) $this->otherCosts->sum('amount'), 2);
        }

        return round((float) $this->otherCosts()->sum('amount'), 2);
    }

    public function totalRecipeCost(): float
    {
        return round($this->ingredientsMaterialCost() + $this->otherCostsTotal(), 2);
    }

    public function syncProductionCost(): void
    {
        $this->update(['production_cost' => $this->totalRecipeCost()]);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function ingredients()
    {
        return $this->hasManyThrough(RecipeIngredient::class, RecipeStep::class);
    }

    public function steps()
    {
        return $this->hasMany(RecipeStep::class)->orderBy('order_index');
    }

    public function otherCosts()
    {
        return $this->hasMany(ProductRecipeOtherCost::class, 'product_recipe_id')->orderBy('order_index');
    }
}
