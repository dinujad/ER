<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaAssetEvent extends Model
{
    protected $table = 'fa_asset_events';

    protected $fillable = [
        'asset_id', 'event_type', 'event_date', 'description',
        'before_state', 'after_state', 'user_id',
    ];

    protected $casts = [
        'event_date' => 'date',
        'before_state' => 'array',
        'after_state' => 'array',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(FaAsset::class, 'asset_id');
    }
}