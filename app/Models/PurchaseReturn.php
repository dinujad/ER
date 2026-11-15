<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReturn extends Model
{
    const STATUS_CONFIRMED = 'confirmed';

    protected $fillable = [
        'return_no',
        'purchase_id',
        'supplier_id',
        'return_date',
        'status',
        'subtotal',
        'total',
        'reason',
        'accounting_txn_number',
        'created_by',
    ];

    protected $casts = [
        'return_date' => 'date',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function generateReturnNo(): string
    {
        $last = static::orderByDesc('id')->first();
        $num = $last ? ((int) preg_replace('/\D/', '', $last->return_no)) + 1 : 1;

        return 'PR-' . date('Y') . '-' . str_pad((string) $num, 5, '0', STR_PAD_LEFT);
    }
}
