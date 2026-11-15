<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    public const CHEQUE_RECEIVED  = 'received';
    public const CHEQUE_DEPOSITED = 'deposited';
    public const CHEQUE_CLEARED   = 'cleared';
    public const CHEQUE_BOUNCED   = 'bounced';

    public const ENTRY_PAYMENT = 'payment';
    public const ENTRY_REFUND = 'refund';

    protected $fillable = [
        'order_id',
        'cheque_batch_id',
        'entry_type',
        'sale_return_id',
        'amount',
        'discount_type',
        'discount_value',
        'discount_amount',
        'settlement_amount',
        'payment_date',
        'payment_method',
        'reference_number',
        'notes',
        'cheque_date',
        'cheque_image_path',
        'cheque_exception_requested',
        'cheque_exception_reason',
        'cheque_status',
        'cheque_bank',
        'cheque_number',
        'deposited_at',
        'cleared_at',
        'bounced_at',
        'bounce_reason',
        'receipt_journal_txn',
        'clearance_journal_txn',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'cheque_date' => 'date',
        'amount' => 'decimal:2',
        'discount_value' => 'decimal:4',
        'discount_amount' => 'decimal:2',
        'settlement_amount' => 'decimal:2',
        'cheque_exception_requested' => 'boolean',
        'cheque_followup_sent_at' => 'datetime',
        'customer_reminder_sent_at' => 'datetime',
        'deposited_at' => 'datetime',
        'cleared_at' => 'datetime',
        'bounced_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function chequeBatch()
    {
        return $this->belongsTo(ChequeBatch::class);
    }

    public function saleReturn()
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function isRefund(): bool
    {
        return $this->entry_type === self::ENTRY_REFUND;
    }

    /** Amount credited toward order due (cash + payment-line discount). */
    public function effectiveSettlementAmount(): float
    {
        if ($this->settlement_amount !== null && (float) $this->settlement_amount > 0) {
            return (float) $this->settlement_amount;
        }

        return round((float) $this->amount + (float) ($this->discount_amount ?? 0), 2);
    }

    /**
     * @return array{discount_type: string, discount_value: float, discount_amount: float, settlement_amount: float, amount: float}
     */
    /**
     * Cash: the entered amount is the cash handed over. The discount is taken off the next payment,
     * so the order due drops by cash + discount.
     * Other methods: the entered amount is applied to the due, and the discount reduces cash received.
     *
     * @return array{discount_type: string, discount_value: float, discount_amount: float, settlement_amount: float, amount: float}
     */
    public static function computeLineTotals(float $enteredAmount, ?string $discountType, float $discountValue, bool $cashReducesNextPayment = false): array
    {
        $enteredAmount = max(0, round($enteredAmount, 2));
        $discountType = in_array($discountType, ['fixed', 'percentage'], true) ? $discountType : 'fixed';
        $discountValue = max(0, $discountValue);

        if ($discountValue <= 0 || $enteredAmount <= 0) {
            return [
                'discount_type' => $discountType,
                'discount_value' => 0.0,
                'discount_amount' => 0.0,
                'settlement_amount' => $enteredAmount,
                'amount' => $enteredAmount,
            ];
        }

        if ($discountType === 'percentage') {
            $discountValue = min(100, $discountValue);
            $discountAmount = round($enteredAmount * ($discountValue / 100), 2);
        } else {
            $discountAmount = round(min($discountValue, $enteredAmount), 2);
        }

        if ($cashReducesNextPayment) {
            return [
                'discount_type' => $discountType,
                'discount_value' => round($discountValue, 2),
                'discount_amount' => $discountAmount,
                'settlement_amount' => round($enteredAmount + $discountAmount, 2),
                'amount' => $enteredAmount,
            ];
        }

        $netAmount = round(max(0, $enteredAmount - $discountAmount), 2);

        return [
            'discount_type' => $discountType,
            'discount_value' => round($discountValue, 2),
            'discount_amount' => $discountAmount,
            'settlement_amount' => $enteredAmount,
            'amount' => $netAmount,
        ];
    }

    /** Cash discount that was taken off the balance still left to pay. */
    public function cashNextPaymentDiscountLine(): ?string
    {
        if (strtolower(trim((string) $this->payment_method)) !== 'cash') {
            return null;
        }

        $discount = round((float) ($this->discount_amount ?? 0), 2);
        if ($discount <= 0) {
            return null;
        }

        $rate = '';
        if ($this->discount_type === 'percentage') {
            $rate = ' ('.rtrim(rtrim(number_format((float) $this->discount_value, 2), '0'), '.').'%)';
        }

        return 'Cash Rs '.number_format((float) $this->amount, 2)
            .'. Discount'.$rate.' Rs '.number_format($discount, 2)
            .' reduced the next payment. Due reduced by Rs '.number_format($this->effectiveSettlementAmount(), 2).'.';
    }

    /** @param Builder<Payment> $query */
    public function scopeCustomerPayments(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('entry_type', self::ENTRY_PAYMENT)
                ->orWhereNull('entry_type');
        });
    }

    /** @param Builder<Payment> $query */
    public function scopeCustomerRefunds(Builder $query): Builder
    {
        return $query->where('entry_type', self::ENTRY_REFUND);
    }

    public function isCheque(): bool
    {
        return strtolower(trim((string) $this->payment_method)) === 'cheque';
    }

    /** Cheques that count toward order paid amount (excludes bounced). */
    public function countsTowardPaid(): bool
    {
        if (!$this->isCheque()) {
            return true;
        }

        return $this->cheque_status !== self::CHEQUE_BOUNCED;
    }

    public function chequeStatusLabel(): string
    {
        return match ($this->cheque_status) {
            self::CHEQUE_RECEIVED  => 'Received (PDC)',
            self::CHEQUE_DEPOSITED => 'Deposited',
            self::CHEQUE_CLEARED   => 'Cleared',
            self::CHEQUE_BOUNCED   => 'Returned / Bounced',
            default => '—',
        };
    }

    public function chequeStatusBadgeClass(): string
    {
        return match ($this->cheque_status) {
            self::CHEQUE_RECEIVED  => 'bg-info text-dark',
            self::CHEQUE_DEPOSITED => 'bg-primary',
            self::CHEQUE_CLEARED   => 'bg-success',
            self::CHEQUE_BOUNCED   => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    public function canMarkDeposited(): bool
    {
        return $this->isCheque() && $this->cheque_status === self::CHEQUE_RECEIVED;
    }

    public function canMarkCleared(): bool
    {
        return $this->isCheque() && in_array($this->cheque_status, [self::CHEQUE_RECEIVED, self::CHEQUE_DEPOSITED], true);
    }

    public function canMarkBounced(): bool
    {
        return $this->isCheque() && $this->cheque_status !== self::CHEQUE_BOUNCED;
    }

    /**
     * Cheque received but not yet deposited to our bank (still pending deposit).
     *
     * @param Builder<Payment> $query
     */
    public function scopePendingBankDeposit(Builder $query): Builder
    {
        return $query->whereRaw('LOWER(TRIM(payment_method)) = ?', ['cheque'])
            ->where(function (Builder $q) {
                $q->whereNull('cheque_status')
                    ->orWhere('cheque_status', self::CHEQUE_RECEIVED);
            });
    }

    /** @param Builder<Payment> $query */
    public function scopeEffectiveForOrderPaid(Builder $query): Builder
    {
        return $query->customerPayments()->where(function (Builder $q) {
            $q->whereRaw('LOWER(TRIM(payment_method)) != ?', ['cheque'])
                ->orWhere(function (Builder $q2) {
                    $q2->whereRaw('LOWER(TRIM(payment_method)) = ?', ['cheque'])
                        ->where(function (Builder $q3) {
                            $q3->whereNull('cheque_status')
                                ->orWhere('cheque_status', '!=', self::CHEQUE_BOUNCED);
                        });
                });
        });
    }
}
