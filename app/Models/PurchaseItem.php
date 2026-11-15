<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id', 'material_id', 'batch_no', 'expiry_date', 'unit',
        'quantity', 'quantity_returned', 'buy_price', 'sell_price', 'discount', 'discount_type', 'tax_amount', 'line_total',
        'is_fixed_asset', 'fa_category_id', 'fa_useful_life_months', 'fa_depreciation_method',
        'fa_salvage_value', 'fa_in_service_date', 'fa_cost_center_id',
    ];

    protected $casts = [
        'is_fixed_asset' => 'boolean',
        'expiry_date' => 'date',
        'quantity' => 'decimal:2',
        'quantity_returned' => 'decimal:2',
        'buy_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}
