<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'sku', 'barcode', 'category_id', 'brand_id', 'unit_id', 
        'warranty_id', 'product_type', 'parent_product_id', 'description', 
        'image', 'cost_price', 'selling_price', 'wholesale_price', 'retail_price', 'tax_rate', 'alert_quantity', 'is_active',
        'status', 'classification'
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'retail_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function warranty()
    {
        return $this->belongsTo(Warranty::class);
    }

    public function parentProduct()
    {
        return $this->belongsTo(Product::class, 'parent_product_id');
    }

    public function variations()
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function stockLedgers()
    {
        return $this->hasMany(StockLedger::class);
    }

    public function productPrices()
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function priceHistory()
    {
        return $this->hasMany(PriceHistory::class);
    }

    public function recipes()
    {
        return $this->hasMany(ProductRecipe::class);
    }

    public function latestRecipe()
    {
        return $this->hasOne(ProductRecipe::class)->latestOfMany();
    }

    public function manufacturingJobs()
    {
        return $this->hasMany(ManufacturingJob::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSimple($query)
    {
        return $query->where('product_type', 'simple');
    }

    public function scopeVariable($query)
    {
        return $query->where('product_type', 'variable');
    }

    // Accessors
    public function getCurrentStockAttribute()
    {
        return $this->stockLedgers()->sum('quantity');
    }

    public function getWholesaleStockAttribute(): float
    {
        return (float) $this->stockLedgers()->where('channel', 'wholesale')->sum('quantity');
    }

    public function getRetailStockAttribute(): float
    {
        return (float) $this->stockLedgers()->where('channel', 'retail')->sum('quantity');
    }

    public function stockOnChannel(string $channel, ?int $variationId = null): float
    {
        $query = $this->stockLedgers()->where('channel', $channel);
        if ($variationId) {
            $query->where('product_variation_id', $variationId);
        } else {
            $query->whereNull('product_variation_id');
        }

        return (float) $query->sum('quantity');
    }

    public function getFormattedPriceAttribute()
    {
        return 'LKR ' . number_format($this->selling_price, 2);
    }

    /** Value encoded on a simple-product label. */
    public function barcodeValue(): string
    {
        foreach ([$this->barcode, $this->sku] as $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                return $value;
            }
        }

        return (string) $this->id;
    }

    /**
     * Cost shown in product lists — for variable products uses variation purchase price when product cost is empty.
     */
    public function displayCostPrice(): float
    {
        if ((float) $this->cost_price > 0) {
            return (float) $this->cost_price;
        }

        if ($this->product_type === 'variable') {
            $this->loadMissing('variations');
            $cost = $this->variations
                ->pluck('default_purchase_price')
                ->map(fn ($v) => (float) $v)
                ->filter(fn ($v) => $v > 0)
                ->first();

            if ($cost !== null) {
                return (float) $cost;
            }
        }

        return (float) $this->cost_price;
    }

    /**
     * Latest per-piece cost from a manufacturing job (includes overhead when available).
     *
     * @return array{job_id: int, job_number: string, cost_per_unit: float, finalized: bool}|null
     */
    public function latestManufacturingCostMeta(): ?array
    {
        $job = $this->manufacturingJobs()
            ->whereHas('packingBatches', fn ($q) => $q->where('type', 'good'))
            ->latest('updated_at')
            ->first();

        if (!$job) {
            return null;
        }

        $job->loadMissing(['materialIssues.material', 'packingBatches.materialIssues.material', 'additionalCosts']);
        $breakdown = $job->costBreakdown();
        $costPerUnit = (float) ($breakdown['cost_per_unit_with_overhead'] ?? $job->costPerUnit());

        if ($costPerUnit <= 0) {
            return null;
        }

        $finalized = $job->packingBatches()
            ->where('type', 'good')
            ->where('status', 'completed')
            ->exists();

        return [
            'job_id' => $job->id,
            'job_number' => $job->job_number,
            'cost_per_unit' => $costPerUnit,
            'finalized' => $finalized,
        ];
    }
}
