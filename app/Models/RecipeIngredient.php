<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeIngredient extends Model
{
    protected $fillable = [
        'recipe_step_id',
        'material_id',
        'quantity',
        'wastage_percent',
        'cost'
    ];

    public function step()
    {
        return $this->belongsTo(RecipeStep::class, 'recipe_step_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}
