<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleReturnItem extends Model
{
    use \App\Models\Concerns\Auditable;

    protected $fillable = [
        'sale_return_id',
        'order_item_id',
        'product_id',
        'product_variation_id',
        'quantity',
        'unit_price',
        'list_unit_price',
        'effective_unit_price',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'list_unit_price' => 'decimal:2',
        'effective_unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function listUnitPrice(): float
    {
        if ($this->list_unit_price !== null) {
            return (float) $this->list_unit_price;
        }

        return (float) $this->unit_price;
    }

    public function effectiveUnitPrice(): float
    {
        if ($this->effective_unit_price !== null) {
            return (float) $this->effective_unit_price;
        }

        return (float) $this->unit_price;
    }

    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }

    public function auditLabel(): string
    {
        return 'Sale return line #' . $this->getKey();
    }
}
