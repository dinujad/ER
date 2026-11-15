<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MaterialStockMovement extends Model
{
    const TYPE_OPENING = 'opening';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_PURCHASE_IN = 'purchase_in';
    const TYPE_PURCHASE_CANCEL = 'purchase_cancel';
    const TYPE_PURCHASE_RETURN = 'purchase_return';
    const TYPE_ISSUE = 'issue';

    protected $fillable = [
        'material_id',
        'movement_type',
        'quantity',
        'balance_after',
        'reference_type',
        'reference_id',
        'reference_label',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isInbound(): bool
    {
        return (float) $this->quantity > 0;
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_OPENING => 'Opening stock',
            self::TYPE_ADJUSTMENT => 'Manual adjustment',
            self::TYPE_PURCHASE_IN => 'Purchase received',
            self::TYPE_PURCHASE_CANCEL => 'Purchase cancelled',
            self::TYPE_PURCHASE_RETURN => 'Return to supplier',
            self::TYPE_ISSUE => 'Issued to production',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }
}
