<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FaAsset extends Model
{
    protected $table = 'fa_assets';

    protected $fillable = [
        'business_id', 'category_id', 'asset_code', 'name', 'description',
        'purchase_date', 'acquisition_date', 'in_service_date',
        'supplier_id', 'purchase_reference',
        'asset_cost', 'capitalized_costs', 'salvage_value',
        'useful_life_months', 'depreciation_method',
        'accumulated_depreciation', 'net_book_value', 'status',
        'location_id', 'cost_center_id', 'serial_number', 'warranty_info',
        'capitalization_txn', 'disposal_date',
        'created_by', 'approved_by', 'capitalized_at',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'acquisition_date' => 'date',
        'in_service_date' => 'date',
        'disposal_date' => 'date',
        'asset_cost' => 'decimal:4',
        'capitalized_costs' => 'decimal:4',
        'salvage_value' => 'decimal:4',
        'accumulated_depreciation' => 'decimal:4',
        'net_book_value' => 'decimal:4',
        'capitalized_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(FaAssetCategory::class, 'category_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(FaAssetEvent::class, 'asset_id');
    }

    public function depreciationSchedules(): HasMany
    {
        return $this->hasMany(FaDepreciationSchedule::class, 'asset_id');
    }

    public function disposal(): HasOne
    {
        return $this->hasOne(FaDisposal::class, 'asset_id');
    }

    public function totalCapitalizedCost(): float
    {
        return round((float) $this->asset_cost + (float) $this->capitalized_costs, 2);
    }
}