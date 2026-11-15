<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaAssetAttachment extends Model
{
    protected $table = 'fa_asset_attachments';

    protected $fillable = [
        'asset_id', 'disk', 'path', 'original_name', 'mime', 'size',
        'description', 'document_type', 'uploaded_by',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(FaAsset::class, 'asset_id');
    }
}