<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use \App\Models\Concerns\Auditable;

    use HasFactory;

    protected $fillable = [
        'code', 'name', 'company_name', 'phone', 'email', 'address',
        'tax_vat_no', 'opening_balance', 'status', 'notes', 'payment_terms_days',
        'bank_account_name', 'bank_account_no', 'bank_name', 'bank_code', 'branch_code',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
    ];

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function payments()
    {
        return $this->hasMany(PurchasePayment::class);
    }

    public function getTotalPurchasesAttribute(): float
    {
        return (float) $this->purchases()->where('status', 'confirmed')->sum('total');
    }

    public function getTotalReturnsAttribute(): float
    {
        $purchaseIds = $this->purchases()->where('status', 'confirmed')->pluck('id');

        return (float) PurchaseReturn::query()->whereIn('purchase_id', $purchaseIds)->sum('total');
    }

    public function getTotalPaidAttribute(): float
    {
        $onPurchases = (float) $this->purchases()->where('status', 'confirmed')->sum('paid_amount');
        $payments = (float) $this->payments()
            ->where(function ($q) {
                $q->where('entry_type', PurchasePayment::ENTRY_PAYMENT)
                    ->orWhereNull('entry_type');
            })
            ->sum('amount');
        $refunds = (float) $this->payments()
            ->where('entry_type', PurchasePayment::ENTRY_REFUND)
            ->sum('amount');

        return $onPurchases + $payments - $refunds;
    }

    public function getOutstandingBalanceAttribute(): float
    {
        return (float) $this->opening_balance + $this->total_purchases - $this->total_returns - $this->total_paid;
    }

    public static function generateCode(): string
    {
        $last = static::orderByDesc('id')->first();
        $num = $last ? ((int) preg_replace('/\D/', '', $last->code)) + 1 : 1;
        return 'SUP-' . str_pad((string) $num, 4, '0', STR_PAD_LEFT);
    }

    public function auditLabel(): string
    {
        return 'Supplier ' . ($this->company_name ?: $this->name ?: '#' . $this->getKey());
    }
}
