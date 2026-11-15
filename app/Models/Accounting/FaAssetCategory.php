<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FaAssetCategory extends Model
{
    protected $table = 'fa_asset_categories';

    protected $fillable = [
        'business_id', 'code', 'name', 'description',
        'asset_account_id', 'accumulated_depreciation_account_id',
        'depreciation_expense_account_id', 'disposal_gain_account_id', 'disposal_loss_account_id',
        'default_depreciation_method', 'default_useful_life_months', 'default_salvage_value', 'active',
    ];

    protected $casts = [
        'default_salvage_value' => 'decimal:4',
        'active' => 'boolean',
    ];

    public function assets(): HasMany
    {
        return $this->hasMany(FaAsset::class, 'category_id');
    }
}