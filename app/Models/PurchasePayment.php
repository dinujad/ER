<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchasePayment extends Model
{
    use HasFactory;

    const ENTRY_PAYMENT = 'payment';
    const ENTRY_REFUND = 'refund';

    protected $fillable = [
        'supplier_id', 'purchase_id', 'entry_type', 'purchase_return_id',
        'amount', 'payment_method', 'payment_date',
        'reference', 'cheque_date', 'cheque_bank', 'cheque_number',
        'notes', 'receipt_path', 'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'cheque_date' => 'date',
    ];

    public function getChequeSummaryAttribute(): ?string
    {
        if ($this->payment_method !== 'cheque') {
            return null;
        }
        $parts = array_filter([
            $this->cheque_number ? '#' . $this->cheque_number : null,
            $this->cheque_bank,
            $this->cheque_date?->format('d M Y'),
        ]);

        return $parts ? implode(' · ', $parts) : null;
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function isRefund(): bool
    {
        return $this->entry_type === self::ENTRY_REFUND;
    }

    public function isPaymentEntry(): bool
    {
        return $this->entry_type === self::ENTRY_PAYMENT || $this->entry_type === null;
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
