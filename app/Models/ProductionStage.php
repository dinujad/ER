<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionStage extends Model
{
    protected $fillable = ['name', 'code', 'order_index'];

    public function recipeSteps()
    {
        return $this->hasMany(RecipeStep::class);
    }

    public function productionSections()
    {
        return $this->hasMany(ProductionSection::class);
    }
}
