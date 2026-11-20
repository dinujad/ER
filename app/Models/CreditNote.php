<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 6.4 — customer credit note against an invoice.
 */
class CreditNote extends Model
{
    use \App\Models\Concerns\Auditable;

    public const TYPE_RETURN = 'return';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const STATUS_ISSUED = 'issued';
    public const STATUS_VOID = 'void';

    protected $fillable = [
        'credit_note_number', 'order_id', 'customer_id', 'sale_return_id', 'type', 'credit_note_date', 'reason',
        'goods_amount', 'sscl_amount', 'vat_amount', 'total', 'status', 'created_by', 'voided_at', 'voided_by',
    ];

    protected $casts = [
        'credit_note_date' => 'date',
        'voided_at' => 'datetime',
        'goods_amount' => 'float',
        'sscl_amount' => 'float',
        'vat_amount' => 'float',
        'total' => 'float',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isVoid(): bool
    {
        return $this->status === self::STATUS_VOID;
    }

    public static function nextNumber(): string
    {
        $prefix = 'CN-' . now()->format('Y') . '-';
        $last = static::query()->where('credit_note_number', 'like', $prefix . '%')->orderByDesc('id')->value('credit_note_number');
        $n = $last && preg_match('/(\d+)$/', $last, $m) ? ((int) $m[1]) + 1 : 1;
        do {
            $number = $prefix . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
            $n++;
        } while (static::query()->where('credit_note_number', $number)->exists());

        return $number;
    }

    public function auditLabel(): string
    {
        return 'Credit note ' . ($this->credit_note_number ?: '#' . $this->getKey());
    }
}
