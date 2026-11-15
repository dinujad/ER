<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackingBatchVariation extends Model
{
    protected $fillable = [
        'packing_batch_id',
        'product_variation_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function packingBatch(): BelongsTo
    {
        return $this->belongsTo(PackingBatch::class);
    }

    public function productVariation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class);
    }
}
