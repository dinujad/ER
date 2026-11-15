<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'business_name',
        'business_address',
        'br_number',
        'tin',
        'br_document_path',
        'credit_limit',
        'due_balance',
        'added_by',
        'customer_type',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'due_balance' => 'decimal:2',
    ];

    public function scopeRetail($query)
    {
        return $query->where('customer_type', 'retail');
    }

    public function scopeWholesale($query)
    {
        return $query->where('customer_type', 'wholesale');
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function saleReturns()
    {
        return $this->hasMany(SaleReturn::class);
    }

    public function saleReturnClaims()
    {
        return $this->hasMany(SaleReturnClaim::class);
    }

    public function previousReturnCredits()
    {
        return $this->hasMany(PreviousReturnCredit::class);
    }

    public function previousReturnClaims()
    {
        return $this->hasMany(PreviousReturnClaim::class);
    }

    public function scopeWithReturnCreditTotals($query)
    {
        return $query
            ->withSum('previousReturnCredits as previous_credit_sum', 'amount')
            ->withSum('previousReturnCredits as previous_claimed_sum', 'claimed_amount')
            ->withSum('saleReturns as system_claimable_sum', 'claimable_amount')
            ->withSum('saleReturns as system_claimed_sum', 'claimed_amount');
    }

    public function previousReturnRemaining(): float
    {
        if (array_key_exists('previous_credit_sum', $this->attributes)) {
            return max(0, round((float) $this->previous_credit_sum - (float) ($this->previous_claimed_sum ?? 0), 2));
        }

        return round((float) $this->previousReturnCredits()->sum('amount') - (float) $this->previousReturnCredits()->sum('claimed_amount'), 2);
    }

    public function systemReturnRemaining(): float
    {
        if (array_key_exists('system_claimable_sum', $this->attributes)) {
            return max(0, round((float) $this->system_claimable_sum - (float) ($this->system_claimed_sum ?? 0), 2));
        }

        return round((float) $this->saleReturns()->sum('claimable_amount') - (float) $this->saleReturns()->sum('claimed_amount'), 2);
    }

    public function fullReturnRemaining(): float
    {
        return round($this->previousReturnRemaining() + $this->systemReturnRemaining(), 2);
    }

    public function availableReturnClaimCredit(): float
    {
        return app(\App\Services\SaleReturnClaimService::class)->availableCreditForCustomer($this);
    }

    /**
     * Primary label everywhere (bills, orders, lists): business name, else contact name.
     */
    public function displayName(): string
    {
        $business = trim((string) ($this->business_name ?? ''));
        if ($business !== '') {
            return $business;
        }

        return trim((string) ($this->name ?? '')) ?: 'N/A';
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->displayName();
    }

    /**
     * Billing / delivery address preference: business address, else personal address.
     */
    public function displayAddress(): string
    {
        $business = trim((string) ($this->business_address ?? ''));
        if ($business !== '') {
            return $business;
        }

        return trim((string) ($this->address ?? '')) ?: 'N/A';
    }

    public function getDisplayAddressAttribute(): string
    {
        return $this->displayAddress();
    }
}
