<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRecipeOtherCost extends Model
{
    protected $fillable = [
        'product_recipe_id',
        'description',
        'amount',
        'order_index',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(ProductRecipe::class, 'product_recipe_id');
    }
}
