<?php

namespace App\Models;

use App\Observers\SaleReturnObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([SaleReturnObserver::class])]
class SaleReturn extends Model
{
    use \App\Models\Concerns\Auditable;

    public const STATUS_CONFIRMED = 'confirmed';

    public const CHANNEL_WHOLESALE = 'wholesale';

    public const CHANNEL_RETAIL = 'retail';

    /** Portion of return total that can be claimed on other invoices. */
    public const CLAIMABLE_RATE = 0.30;

    protected $fillable = [
        'return_no',
        'order_id',
        'customer_id',
        'return_date',
        'channel',
        'status',
        'subtotal',
        'total',
        'claimable_amount',
        'claimed_amount',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'return_date' => 'date',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'claimable_amount' => 'decimal:2',
        'claimed_amount' => 'decimal:2',
    ];

    public function remainingClaimable(): float
    {
        return max(0, round((float) $this->claimable_amount - (float) $this->claimed_amount, 2));
    }

    public function claims(): HasMany
    {
        return $this->hasMany(SaleReturnClaim::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function generateReturnNo(): string
    {
        $last = static::orderByDesc('id')->first();
        $num = $last ? ((int) preg_replace('/\D/', '', $last->return_no)) + 1 : 1;

        return 'SR-' . date('Y') . '-' . str_pad((string) $num, 5, '0', STR_PAD_LEFT);
    }

    public function auditLabel(): string
    {
        return 'Sale return ' . ($this->return_no ?: '#' . $this->getKey());
    }
}
