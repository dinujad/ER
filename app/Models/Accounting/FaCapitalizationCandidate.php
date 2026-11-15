<?php

namespace App\Models\Accounting;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaCapitalizationCandidate extends Model
{
    protected $table = 'fa_capitalization_candidates';

    protected $fillable = [
        'business_id', 'purchase_id', 'purchase_item_id', 'fa_category_id', 'amount', 'status',
        'asset_id', 'gl_treatment', 'clearing_event_key', 'capitalize_event_key', 'capitalization_txn',
        'notes', 'created_by', 'approved_by', 'approved_at', 'capitalized_at',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'approved_at' => 'datetime',
        'capitalized_at' => 'datetime',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FaAssetCategory::class, 'fa_category_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(FaAsset::class, 'asset_id');
    }
}