<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaDisposal extends Model
{
    protected $table = 'fa_disposals';

    protected $fillable = [
        'asset_id', 'disposal_date', 'disposal_type', 'proceeds',
        'cost_at_disposal', 'accumulated_at_disposal', 'gain_loss',
        'status', 'transaction_number', 'notes', 'created_by',
    ];

    protected $casts = [
        'disposal_date' => 'date',
        'proceeds' => 'decimal:4',
        'cost_at_disposal' => 'decimal:4',
        'accumulated_at_disposal' => 'decimal:4',
        'gain_loss' => 'decimal:4',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(FaAsset::class, 'asset_id');
    }
}