<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type', // fabric, accessory
        'quantity',
        'unit_price',
        'unit', // yards, pieces
    ];

    public function issues()
    {
        return $this->hasMany(MaterialIssue::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function purchaseReturnItems()
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function recipeIngredients()
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(MaterialStockMovement::class)->orderByDesc('created_at');
    }
}
