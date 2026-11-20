<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use \App\Models\Concerns\Auditable;

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
        'accounting_txn_number', 'input_vat_claimable',
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
        'input_vat_claimable' => 'boolean',
        'due_date' => 'date',
        'approved_at' => 'datetime',
    ];

    /** Bill status labels (Phase 6.5, Xero-style). */
    public const BILL_STATUS_LABELS = [
        'draft' => 'Draft',
        'awaiting_approval' => 'Awaiting approval',
        'awaiting_payment' => 'Awaiting payment',
        'partially_paid' => 'Partially paid',
        'overdue' => 'Overdue',
        'paid' => 'Paid',
        'cancelled' => 'Cancelled',
    ];

    public const BILL_STATUS_BADGES = [
        'draft' => 'bg-secondary',
        'awaiting_approval' => 'bg-warning text-dark',
        'awaiting_payment' => 'bg-primary',
        'partially_paid' => 'bg-info text-dark',
        'overdue' => 'bg-danger',
        'paid' => 'bg-success',
        'cancelled' => 'bg-light text-muted border',
    ];

    private static ?bool $hasPayablesColumns = null;

    public static function hasPayablesColumns(): bool
    {
        if (self::$hasPayablesColumns === null) {
            try {
                self::$hasPayablesColumns = \Illuminate\Support\Facades\Schema::hasColumn('purchases', 'due_date');
            } catch (\Throwable) {
                self::$hasPayablesColumns = false;
            }
        }

        return self::$hasPayablesColumns;
    }

    public static function flushSchemaCache(): void
    {
        self::$hasPayablesColumns = null;
    }

    protected static function booted(): void
    {
        // Phase 6.5 — due date from supplier terms when the bill is confirmed (or its date changes).
        static::saving(function (Purchase $purchase) {
            if (!self::hasPayablesColumns() || $purchase->status !== self::STATUS_CONFIRMED) {
                return;
            }
            if ($purchase->due_date === null || $purchase->isDirty('date')) {
                $purchase->due_date = $purchase->computeDueDate()?->toDateString();
            }
        });
    }

    public function supplierTermsDays(): int
    {
        $terms = $this->supplier?->payment_terms_days;

        return $terms !== null ? (int) $terms : (int) config('dermas.default_supplier_terms_days', 30);
    }

    public function computeDueDate(): ?\Illuminate\Support\Carbon
    {
        return $this->date ? \Illuminate\Support\Carbon::parse($this->date)->startOfDay()->addDays($this->supplierTermsDays()) : null;
    }

    public function dueDate(): ?\Illuminate\Support\Carbon
    {
        return $this->due_date ? \Illuminate\Support\Carbon::parse($this->due_date)->startOfDay() : $this->computeDueDate();
    }

    public function billStatus(): string
    {
        if ($this->status === self::STATUS_CANCELLED) {
            return 'cancelled';
        }
        if ($this->status !== self::STATUS_CONFIRMED) {
            return 'draft';
        }
        $balance = (float) $this->balance;
        if ($balance <= 0.009) {
            return 'paid';
        }
        if (config('dermas.require_bill_approval') && self::hasPayablesColumns() && $this->approved_at === null) {
            return 'awaiting_approval';
        }
        $due = $this->dueDate();
        if ($due && $due->lt(now()->startOfDay())) {
            return 'overdue';
        }

        return $this->total_paid > 0.009 ? 'partially_paid' : 'awaiting_payment';
    }

    public function billStatusLabel(): string
    {
        return self::BILL_STATUS_LABELS[$this->billStatus()] ?? ucfirst($this->billStatus());
    }

    public function billStatusBadge(): string
    {
        return self::BILL_STATUS_BADGES[$this->billStatus()] ?? 'bg-secondary';
    }

    public function daysOverdue(): int
    {
        $due = $this->dueDate();
        if (!$due || (float) $this->balance <= 0.009) {
            return 0;
        }

        return max(0, (int) $due->diffInDays(now()->startOfDay(), false));
    }

    /**
     * Phase 6.3 — VAT on this bill that can be claimed back as input tax (header tax + line tax),
     * or 0 when the supplier is not VAT registered / the claim box was not ticked.
     */
    public function inputVatAmount(): float
    {
        if (!$this->input_vat_claimable) {
            return 0.0;
        }
        $lineTax = $this->relationLoaded('items')
            ? (float) $this->items->sum('tax_amount')
            : (float) $this->items()->sum('tax_amount');

        return round(max(0, (float) $this->tax_amount + $lineTax), 2);
    }

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

    /** Input VAT share of returned goods (claimable bills only) — the supplier credits it back too. */
    public function returnedInputVat(float $returnGoods): float
    {
        $inputVat = $this->inputVatAmount();
        $net = round((float) $this->total - $inputVat, 2);
        if ($inputVat <= 0 || $returnGoods <= 0 || $net <= 0) {
            return 0.0;
        }

        return round(min($inputVat, $returnGoods * $inputVat / $net), 2);
    }

    /** Returns valued as the supplier credits them (goods + input VAT share on claimable bills). */
    public function getTotalReturnedBilledAttribute(): float
    {
        $goods = $this->total_returned;

        return round($goods + $this->returnedInputVat($goods), 2);
    }

    public function getNetTotalAttribute(): float
    {
        return round((float) $this->total - $this->total_returned_billed, 2);
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

    public function auditLabel(): string
    {
        return 'Bill ' . ($this->purchase_no ?: '#' . $this->getKey());
    }
}
