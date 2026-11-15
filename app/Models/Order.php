<?php

namespace App\Models;

use App\Observers\OrderObserver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Support\Collection;

#[ObservedBy([OrderObserver::class])]
class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'invoice_number',
        'store_id',
        'customer_id',
        'user_id',
        'register_id',
        'total_amount',
        'subtotal',
        'discount_amount',
        'discount_type',
        'discount_value',
        'tax_type',
        'tax_value',
        'shipping',
        'invoice_other_expenses',
        'status',
        'is_urgent',
        'order_date',
        'notes',
        'commission_rate',
        'issued_bags_count',
        'issued_bundle_count',
        'claim_credit_amount',
    ];

    public const STATUS_PAID = 'paid';
    public const STATUS_CREDIT = 'credit';
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_QUOTATION = 'quotation';
    public const STATUS_PENDING = 'pending';
    public const STATUS_AFTERCHECKING = 'afterchecking';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_CANCELLED = 'cancelled';

    /** POS draft / quotation / suspend — not real sales until payment is received. */
    public static function excludedFromSalesStats(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_QUOTATION,
            self::STATUS_SUSPENDED,
            self::STATUS_CANCELLED,
        ];
    }

    public function scopeCountedInSales($query)
    {
        return $query->whereNotIn('status', static::excludedFromSalesStats());
    }

    /** Admin order board: pipeline order and display metadata. */
    public static function adminBoardStatuses(): array
    {
        return [
            self::STATUS_PENDING => ['label' => 'Pending', 'badge' => 'warning'],
            self::STATUS_PROCESSING => ['label' => 'Processing', 'badge' => 'info'],
            self::STATUS_AFTERCHECKING => ['label' => 'After Checking', 'badge' => 'primary'],
            self::STATUS_COMPLETED => ['label' => 'Completed', 'badge' => 'success'],
            self::STATUS_CANCELLED => ['label' => 'Cancelled', 'badge' => 'danger'],
        ];
    }

    public static function adminUpdatableStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PROCESSING,
            self::STATUS_AFTERCHECKING,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
        ];
    }

    public function statusLabel(): string
    {
        return self::adminBoardStatuses()[$this->status]['label'] ?? ucfirst((string) $this->status);
    }

    /** Admin may add/remove items before Order Check (afterchecking). */
    public function canAdminEditItems(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_PROCESSING,
            self::STATUS_DRAFT,
        ], true);
    }

    /** Salesperson may change a placed order until admin Order Check. */
    public function canSalesPersonEdit(): bool
    {
        if (!in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_PROCESSING,
        ], true)) {
            return false;
        }

        if ($this->relationLoaded('items')) {
            return !$this->items->contains(fn ($item) => $item->isChecked());
        }

        if (array_key_exists('checked_items_count', $this->attributes)) {
            return (int) $this->checked_items_count === 0;
        }

        return !$this->items()->whereNotNull('checked_at')->exists();
    }

    /** Payments allowed only after admin Order Check (After Checking or later). */
    public function canAcceptPayments(): bool
    {
        return in_array($this->status, [
            self::STATUS_AFTERCHECKING,
            self::STATUS_COMPLETED,
        ], true);
    }

    protected $casts = [
        'order_date' => 'datetime',
        'is_urgent' => 'boolean',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function register()
    {
        return $this->belongsTo(Register::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return array{discount_type: string, discount_value: float, discount_amount: float, total_amount: float}
     */
    public static function discountBreakdown(float $subtotal, ?string $discountType, float $discountValue): array
    {
        $type = in_array($discountType, ['fixed', 'percentage'], true) ? $discountType : 'fixed';
        $value = max(0, $discountValue);

        if ($value <= 0) {
            return [
                'discount_type' => $type,
                'discount_value' => 0,
                'discount_amount' => 0.0,
                'total_amount' => round(max(0, $subtotal), 2),
            ];
        }

        if ($type === 'percentage') {
            $value = min(100, $value);
            $discountAmount = $subtotal * ($value / 100);
        } else {
            $discountAmount = min($value, $subtotal);
        }

        $discountAmount = round($discountAmount, 2);

        return [
            'discount_type' => $type,
            'discount_value' => $value,
            'discount_amount' => $discountAmount,
            'total_amount' => round(max(0, $subtotal - $discountAmount), 2),
        ];
    }

    public function orderFormFilename(): string
    {
        $business = strtoupper(trim((string) ($this->customer?->business_name ?? '')));
        $business = trim((string) preg_replace('/[^A-Z0-9]+/', '_', $business), '_');

        $number = strtoupper(trim((string) preg_replace('/[^A-Z0-9]+/', '', (string) $this->order_number)));
        if ($business !== '' && $number !== '') {
            $base = $business.'_'.$number;
        } elseif ($number !== '') {
            $base = $number;
        } elseif ($business !== '') {
            $base = $business;
        } else {
            $base = 'ORDER_FORM';
        }

        return $base.'.pdf';
    }

    public function orderFormContentDisposition(): string
    {
        return 'attachment; filename="'.$this->orderFormFilename().'"';
    }

    public function salesPerson()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return array{checked: int, left: int, total: int}
     */
    public function orderCheckProgress(): array
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get(['id', 'checked_at']);
        $total = $items->count();
        $checked = $items->filter(fn ($item) => $item->checked_at !== null)->count();

        return [
            'checked' => $checked,
            'left' => max(0, $total - $checked),
            'total' => $total,
        ];
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function saleReturns()
    {
        return $this->hasMany(SaleReturn::class);
    }

    public function returnClaims()
    {
        return $this->hasMany(SaleReturnClaim::class);
    }

    public function canAcceptSaleReturn(): bool
    {
        return in_array($this->status, [
            self::STATUS_AFTERCHECKING,
            self::STATUS_COMPLETED,
            self::STATUS_PAID,
            self::STATUS_CREDIT,
        ], true);
    }

    /**
     * Merchandise discount factor for return pricing (shipping / other expenses stay off lines).
     */
    public function invoiceMerchandiseFactor(): float
    {
        $subtotal = (float) $this->subtotal;
        if ($subtotal <= 0.0001) {
            return 1.0;
        }

        $merchNet = max(0, $subtotal - (float) ($this->discount_amount ?? 0));

        return max(0, min(1, $merchNet / $subtotal));
    }

    /**
     * Cash / payment discount factor: money received vs settlement that cleared due.
     * Unpaid open bills return 1 so same-day returns use invoice-adjusted list price.
     */
    public function returnSettlementCashFactor(): float
    {
        $this->loadMissing('payments');

        $paidSettlement = 0.0;
        $received = 0.0;
        $refunded = 0.0;
        foreach ($this->payments as $payment) {
            if ($payment->isRefund()) {
                $refunded += (float) $payment->amount;
                continue;
            }
            if (!$payment->countsTowardPaid()) {
                continue;
            }
            $paidSettlement += $payment->effectiveSettlementAmount();
            $received += (float) $payment->amount;
        }

        $claim = (float) ($this->claim_credit_amount ?? 0);
        $covered = round($paidSettlement - $refunded + $claim, 2);
        $net = max(0, round((float) $this->total_amount - (float) $this->total_returned, 2));
        $dueOpen = max(0, round($net - $paidSettlement + $refunded - $claim, 2));

        if ($covered <= 0.009 && $dueOpen > 0.009) {
            return 1.0;
        }

        $received = max(0, round($received - $refunded, 2));
        $settledBasis = max(0, round($paidSettlement - $refunded, 2));

        if ($settledBasis <= 0.009) {
            return 1.0;
        }

        $economicIn = $received + $claim;
        $economicOut = $settledBasis + $claim;

        return max(0, min(1, $economicOut > 0 ? $economicIn / $economicOut : 1.0));
    }

    /** POS / cashier sale (not a salesperson wholesale invoice). */
    public function isRetailSale(): bool
    {
        if ($this->register_id) {
            return true;
        }

        return $this->stockChannel() === SaleReturn::CHANNEL_RETAIL;
    }

    /** POS and other non-salesperson sales. */
    public function scopeRetailSales($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('orders.register_id')
                ->orWhereDoesntHave('salesPerson', function ($u) {
                    $u->where('role', 'sales_person');
                });
        });
    }

    public function isRetailQuotation(): bool
    {
        return $this->status === self::STATUS_QUOTATION;
    }

    public function scopeRetailQuotations($query)
    {
        return $query->where('orders.status', self::STATUS_QUOTATION);
    }

    /** Wholesale when created by / assigned to a sales person. */
    public function stockChannel(): string
    {
        if ($this->register_id) {
            return SaleReturn::CHANNEL_RETAIL;
        }

        if ($this->relationLoaded('salesPerson') && $this->salesPerson) {
            return $this->salesPerson->role === 'sales_person'
                ? SaleReturn::CHANNEL_WHOLESALE
                : SaleReturn::CHANNEL_RETAIL;
        }

        $user = $this->salesPerson()->first();
        if ($user && $user->role === 'sales_person') {
            return SaleReturn::CHANNEL_WHOLESALE;
        }

        return SaleReturn::CHANNEL_RETAIL;
    }

    public function getTotalReturnedAttribute(): float
    {
        if ($this->relationLoaded('saleReturns')) {
            return round((float) $this->saleReturns->sum('total'), 2);
        }

        return round((float) $this->saleReturns()->sum('total'), 2);
    }

    public function getNetTotalAttribute(): float
    {
        return max(0, round((float) $this->total_amount - $this->total_returned, 2));
    }

    public function getTotalRefundedAttribute(): float
    {
        return round((float) $this->payments()->customerRefunds()->sum('amount'), 2);
    }

    /** Cash still refundable to customer after returns. */
    public function getRefundableToCustomerAttribute(): float
    {
        return max(0, round($this->paid_amount - $this->net_total - $this->total_refunded, 2));
    }

    /**
     * Orders a sales person may see: they created the order or they added the customer.
     */
    public function scopeVisibleToSalesPerson($query, ?int $userId = null)
    {
        $userId = $userId ?? (int) auth()->id();

        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
                ->orWhereHas('customer', function ($cq) use ($userId) {
                    $cq->where('added_by', $userId);
                });
        });
    }

    /**
     * @return array{discount_type: string, discount_value: float, discount_amount: float, shipping: float, invoice_other_expenses: float, total_amount: float}
     */
    public static function computeInvoiceTotals(
        float $subtotal,
        ?string $discountType,
        float $discountValue,
        float $shipping = 0,
        float $otherExpenses = 0
    ): array {
        $discountType = in_array($discountType, ['fixed', 'percentage'], true) ? $discountType : 'fixed';
        $discountValue = max(0, $discountValue);
        $shipping = max(0, $shipping);
        $otherExpenses = max(0, $otherExpenses);

        if ($discountType === 'percentage') {
            $discountValue = min(100, $discountValue);
            $discountAmount = $subtotal * ($discountValue / 100);
        } else {
            $discountAmount = min($discountValue, $subtotal);
        }

        $totalAmount = max(0, round($subtotal - $discountAmount + $shipping + $otherExpenses, 2));

        return [
            'discount_type' => $discountType,
            'discount_value' => round($discountValue, 2),
            'discount_amount' => round($discountAmount, 2),
            'shipping' => round($shipping, 2),
            'invoice_other_expenses' => round($otherExpenses, 2),
            'total_amount' => $totalAmount,
        ];
    }

    public function getPaidAmountAttribute()
    {
        return (float) $this->payments()
            ->effectiveForOrderPaid()
            ->get()
            ->sum(fn (Payment $p) => $p->effectiveSettlementAmount());
    }

    public function getDueAmountAttribute()
    {
        return max(0, round(
            $this->net_total
            - $this->paid_amount
            + $this->total_refunded
            - (float) ($this->claim_credit_amount ?? 0),
            2
        ));
    }

    /** SQL subquery: sum of payments that count toward order paid (excludes bounced cheques + refunds). */
    public static function effectivePaidAmountSubquery(): string
    {
        return '(SELECT COALESCE(SUM(COALESCE(payments.settlement_amount, payments.amount + COALESCE(payments.discount_amount, 0))), 0) FROM payments WHERE payments.order_id = orders.id'
            . " AND (payments.entry_type IS NULL OR payments.entry_type = 'payment')"
            . " AND ("
            . "LOWER(TRIM(payments.payment_method)) != 'cheque' OR ("
            . "LOWER(TRIM(payments.payment_method)) = 'cheque' AND (payments.cheque_status IS NULL OR payments.cheque_status != 'bounced')"
            . ')))';
    }

    public static function nextOrderNumber(): string
    {
        return static::nextRunningNumber('ORD', 'order_number');
    }

    /**
     * System-wide tax invoice number: {YY}{MMM}-DERM-{N}
     * Example: 26JUL-DERM-1001
     * Year/month from issue date; trailing N increments for every new invoice.
     */
    public static function nextInvoiceNumber(?\DateTimeInterface $issuedAt = null): string
    {
        $issuedAt = Carbon::parse($issuedAt ?? now());
        $yy = $issuedAt->format('y');
        $mmm = strtoupper($issuedAt->format('M'));
        $code = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) config('dermas.invoice_branch_code', 'DERM')) ?: 'DERM');
        $code = substr($code, 0, 15);
        $prefix = $yy . $mmm . '-' . $code . '-';

        $next = static::nextInvoiceSequence();
        $serial = $prefix . $next;

        while (static::query()->where('invoice_number', $serial)->exists()) {
            $next++;
            $serial = $prefix . $next;
        }

        return substr($serial, 0, 40);
    }

    /** Next numeric suffix across old INV* and new YYMMM-DERM-* invoice numbers. */
    public static function nextInvoiceSequence(): int
    {
        $existing = static::query()
            ->whereNotNull('invoice_number')
            ->pluck('invoice_number');

        $max = 0;
        foreach ($existing as $value) {
            if (!is_string($value) || $value === '') {
                continue;
            }
            if (preg_match('/(\d+)\s*$/', $value, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        return max($max + 1, 1001);
    }

    /**
     * Display tax invoice number — stored invoice_number is the system format.
     */
    public function taxInvoiceSerialNumber(): string
    {
        if (filled($this->invoice_number)) {
            return (string) $this->invoice_number;
        }

        return static::nextInvoiceNumber($this->updated_at ?? $this->created_at ?? now());
    }

    /**
     * Tax invoice money breakdown.
     * goods → SSCL on goods → Total Value of Supply (goods+SSCL) → VAT on supply → grand total.
     *
     * @return array{
     *   goods: float,
     *   sscl_rate: float,
     *   sscl: float,
     *   supply: float,
     *   rate: float,
     *   exclusive: float,
     *   vat: float,
     *   inclusive: float
     * }
     */
    public function taxInvoiceVatBreakdown(): array
    {
        $ssclRate = max(0, (float) config('dermas.sscl_rate', 2.5));
        $vatRate = max(0, (float) config('dermas.vat_rate', 18));
        $goods = round((float) $this->total_amount, 2);
        $sscl = $ssclRate > 0 ? round($goods * ($ssclRate / 100), 2) : 0.0;
        $supply = round($goods + $sscl, 2);
        $vat = $vatRate > 0 ? round($supply * ($vatRate / 100), 2) : 0.0;

        return [
            'goods' => $goods,
            'sscl_rate' => $ssclRate,
            'sscl' => $sscl,
            'supply' => $supply,
            'rate' => $vatRate,
            'exclusive' => $supply,
            'vat' => $vat,
            'inclusive' => round($supply + $vat, 2),
        ];
    }

    public function taxInvoicePaymentModeLabel(): string
    {
        $this->loadMissing('payments');
        $methods = $this->payments
            ->filter(fn (Payment $p) => !$p->isRefund() && $p->countsTowardPaid())
            ->map(function (Payment $p) {
                return match (strtolower(trim((string) $p->payment_method))) {
                    'cash' => 'Cash',
                    'card', 'credit_card', 'debit_card' => 'Credit/Debit card',
                    'bank_transfer', 'bank' => 'Bank Transfer',
                    'cheque' => 'Cheque',
                    'transport' => 'Transport',
                    'mobile', 'mobile_payment' => 'Mobile Payment',
                    'online', 'online_payment' => 'Online Payment',
                    default => ucfirst((string) $p->payment_method),
                };
            })
            ->unique()
            ->values();

        if ($methods->isEmpty()) {
            return 'Credit';
        }

        return $methods->implode(', ');
    }

    public function amountInWords(float $amount): string
    {
        $amount = round($amount, 2);
        $rupees = (int) floor($amount);
        $cents = (int) round(($amount - $rupees) * 100);
        $words = $this->integerToWords($rupees) . ' Rupees';
        if ($cents > 0) {
            $words .= ' and ' . $this->integerToWords($cents) . ' Cents';
        }

        return $words . ' Only';
    }

    protected function integerToWords(int $number): string
    {
        if ($number === 0) {
            return 'Zero';
        }

        $ones = [
            '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
            'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
            'Seventeen', 'Eighteen', 'Nineteen',
        ];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $chunk = function (int $n) use (&$chunk, $ones, $tens): string {
            if ($n < 20) {
                return $ones[$n];
            }
            if ($n < 100) {
                return trim($tens[(int) floor($n / 10)] . ' ' . $ones[$n % 10]);
            }
            if ($n < 1000) {
                return trim($ones[(int) floor($n / 100)] . ' Hundred ' . $chunk($n % 100));
            }
            if ($n < 100000) {
                return trim($chunk((int) floor($n / 1000)) . ' Thousand ' . $chunk($n % 1000));
            }
            if ($n < 10000000) {
                return trim($chunk((int) floor($n / 100000)) . ' Lakh ' . $chunk($n % 100000));
            }

            return trim($chunk((int) floor($n / 10000000)) . ' Crore ' . $chunk($n % 10000000));
        };

        return trim($chunk($number));
    }

    public static function nextQuotationNumber(): string
    {
        return static::nextRunningNumber('QT', 'order_number');
    }

    protected static function nextRunningNumber(string $prefix, string $column): string
    {
        $existing = static::query()
            ->whereNotNull($column)
            ->where($column, 'like', $prefix . '%')
            ->pluck($column);

        $max = static::maxSuffix($existing, $prefix);
        $next = max($max + 1, 100);

        while (static::query()->where($column, $prefix . $next)->exists()) {
            $next++;
        }

        return $prefix . $next;
    }

    protected static function maxSuffix(Collection $values, string $prefix): int
    {
        return (int) $values
            ->map(function ($value) use ($prefix) {
                if (!is_string($value)) {
                    return null;
                }
                $normalized = strtoupper(trim($value));
                if (!str_starts_with($normalized, strtoupper($prefix))) {
                    return null;
                }
                $suffix = substr($normalized, strlen($prefix));
                return ctype_digit($suffix) ? (int) $suffix : null;
            })
            ->filter(fn ($n) => $n !== null)
            ->max() ?? 0;
    }
}
