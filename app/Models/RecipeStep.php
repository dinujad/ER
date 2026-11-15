<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeStep extends Model
{
    protected $fillable = ['product_recipe_id', 'production_stage_id', 'name', 'description', 'order_index'];

    public function recipe()
    {
        return $this->belongsTo(ProductRecipe::class, 'product_recipe_id');
    }

    public function ingredients()
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    public function productionStage()
    {
        return $this->belongsTo(ProductionStage::class);
    }
}
