<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WashingStockMovement extends Model
{
    public const TYPE_IN = 'in';

    public const TYPE_OUT = 'out';

    protected $fillable = [
        'manufacturing_job_id',
        'washing_stock_id',
        'type',
        'quantity',
        'user_id',
        'note',
        'washing_transfer_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(ManufacturingJob::class, 'manufacturing_job_id');
    }

    public function washingStock(): BelongsTo
    {
        return $this->belongsTo(WashingStock::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function washingTransfer(): BelongsTo
    {
        return $this->belongsTo(WashingTransfer::class);
    }

    public function isIn(): bool
    {
        return $this->type === self::TYPE_IN;
    }
}
