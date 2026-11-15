<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'variation_template_value', // e.g. "Size: M" (Old?) - We might deprecate or use for display
        'variation_name', // e.g. "Red-Small"
        'sub_sku',
        'default_purchase_price',
        'default_sell_price',
        'wholesale_price',
        'retail_price',
        'combo_variations', // JSON: {Size: M, Color: Red}
        'barcode_type',
        'barcode'
    ];

    protected $casts = [
        'combo_variations' => 'array',
        'default_purchase_price' => 'decimal:2',
        'default_sell_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'retail_price' => 'decimal:2',
    ];

    public function belongsToProduct(mixed $productId): bool
    {
        return (int) $this->product_id === (int) $productId;
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
    public function stockLedgers()
    {
        return $this->hasMany(StockLedger::class);
    }

    public function getCurrentStockAttribute()
    {
        return $this->stockLedgers()->sum('quantity');
    }

    /** Value encoded on the label — variation barcode, then SKU. */
    public function barcodeValue(): string
    {
        foreach ([$this->barcode, $this->sub_sku] as $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                return $value;
            }
        }

        return (string) $this->id;
    }

    public function labelSellPrice(): float
    {
        foreach (['retail_price', 'default_sell_price'] as $field) {
            $price = (float) ($this->{$field} ?? 0);
            if ($price > 0) {
                return $price;
            }
        }

        $this->loadMissing('product');

        return (float) ($this->product?->retail_price ?: $this->product?->selling_price ?: 0);
    }

    /** Attribute map such as Color => Red, Size => M. */
    public function attributePairs(): array
    {
        $combo = $this->combo_variations;
        if (!is_array($combo) || $combo === []) {
            return [];
        }

        $pairs = [];
        foreach ($combo as $key => $value) {
            if (is_array($value)) {
                $value = implode(' / ', array_filter(array_map('strval', $value)));
            }
            $value = trim((string) $value);
            $key = trim((string) $key);
            if ($key === '' || $value === '') {
                continue;
            }
            $pairs[$key] = $value;
        }

        return $pairs;
    }

    public function attributeValue(string ...$keys): ?string
    {
        $pairs = [];
        foreach ($this->attributePairs() as $key => $value) {
            $pairs[strtolower($key)] = $value;
        }
        foreach ($keys as $key) {
            $found = $pairs[strtolower($key)] ?? null;
            if ($found !== null && $found !== '') {
                return $found;
            }
        }

        return null;
    }

    public function attributesLabel(): string
    {
        $pairs = $this->attributePairs();
        if ($pairs !== []) {
            return collect($pairs)->map(fn ($value, $key) => $key . ': ' . $value)->implode(' · ');
        }

        return (string) $this->variation_name;
    }

    public function searchHaystack(): string
    {
        $bits = [
            (string) $this->variation_name,
            (string) $this->sub_sku,
            (string) $this->barcode,
            $this->attributesLabel(),
        ];

        return strtolower(implode(' ', array_filter($bits)));
    }
}
