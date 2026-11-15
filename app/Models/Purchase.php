<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    const STATUS_DRAFT = 'draft';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CANCELLED = 'cancelled';

    const TYPE_CASH = 'cash';
    const TYPE_CREDIT = 'credit';

    public static function purchaseTypeOptions(): array
    {
        return [
            self::TYPE_CREDIT => 'Credit',
            self::TYPE_CASH => 'Full Payment',
        ];
    }

    public function getPurchaseTypeLabelAttribute(): string
    {
        return self::purchaseTypeOptions()[$this->purchase_type] ?? ucfirst((string) $this->purchase_type);
    }

    protected $fillable = [
        'purchase_no', 'date', 'supplier_id', 'purchase_type', 'store_id', 'reference_no', 'notes',
        'status', 'subtotal', 'discount_amount', 'discount_type', 'tax_amount', 'shipping_cost',
        'total', 'paid_amount', 'payment_method', 'payment_reference',
        'cheque_date', 'cheque_bank', 'cheque_number',
        'created_by', 'confirmed_at', 'cancelled_at', 'cancel_reason',
        'accounting_txn_number',
    ];

    protected $casts = [
        'date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'cheque_date' => 'date',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function payments()
    {
        return $this->hasMany(PurchasePayment::class);
    }

    public function returns()
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function scopeNotCancelled($query)
    {
        return $query->where('status', '!=', self::STATUS_CANCELLED);
    }

    /** Purchases that count in financial / stock reports */
    public function scopeReportable($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function getTotalReturnedAttribute(): float
    {
        if ($this->relationLoaded('returns')) {
            return (float) $this->returns->sum('total');
        }

        return (float) $this->returns()->sum('total');
    }

    public function getNetTotalAttribute(): float
    {
        return round((float) $this->total - $this->total_returned, 2);
    }

    /** Total paid = payment rows, or legacy header paid_amount when no rows exist. */
    public function getTotalPaidAttribute(): float
    {
        $fromRows = $this->paymentEntriesTotal();
        $header = (float) $this->paid_amount;

        if ($fromRows <= 0) {
            return $header;
        }

        if ($header <= 0 || abs($header - $fromRows) < 0.009) {
            return $fromRows;
        }

        return round($header + $fromRows, 2);
    }

    public function paymentEntriesTotal(): float
    {
        if ($this->relationLoaded('payments')) {
            return (float) $this->payments
                ->filter(fn (PurchasePayment $payment) => $payment->isPaymentEntry())
                ->sum('amount');
        }

        return (float) $this->payments()
            ->where(function ($q) {
                $q->where('entry_type', PurchasePayment::ENTRY_PAYMENT)
                    ->orWhereNull('entry_type');
            })
            ->sum('amount');
    }

    public function getTotalRefundedAttribute(): float
    {
        return (float) $this->payments()
            ->where('entry_type', PurchasePayment::ENTRY_REFUND)
            ->sum('amount');
    }

    /** Amount supplier still owes back after returns (overpayment credit) */
    public function getRefundableBalanceAttribute(): float
    {
        return max(0, round($this->total_paid - $this->net_total - $this->total_refunded, 2));
    }

    public function getBalanceAttribute(): float
    {
        return round($this->net_total - $this->total_paid + $this->total_refunded, 2);
    }

    /** paid | partial | unpaid for display in list */
    public function getPaymentStatusAttribute(): string
    {
        if ($this->status === self::STATUS_CANCELLED) {
            return 'cancelled';
        }
        $bal = $this->balance;
        if ($bal <= 0) {
            return 'paid';
        }
        return $this->total_paid > 0 ? 'partial' : 'unpaid';
    }

    public static function generatePurchaseNo(): string
    {
        $prefix = 'PUR-' . date('Y-m-d') . '-';

        $existing = static::query()
            ->where('purchase_no', 'like', $prefix . '%')
            ->pluck('purchase_no');

        $max = (int) $existing
            ->map(function ($no) use ($prefix) {
                $suffix = substr((string) $no, strlen($prefix));

                return ctype_digit($suffix) ? (int) $suffix : null;
            })
            ->filter(fn ($n) => $n !== null)
            ->max();

        $next = max($max + 1, 1);

        while (static::query()->where('purchase_no', $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT))->exists()) {
            $next++;
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /** Serialize purchase number generation inside an open DB transaction. */
    public static function lockPurchaseNumberGeneration(): void
    {
        static::query()->orderByDesc('id')->lockForUpdate()->value('id');
    }
}
