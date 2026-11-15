<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PreviousReturnCredit extends Model
{
    protected $fillable = [
        'customer_id',
        'amount',
        'claimed_amount',
        'note',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'claimed_amount' => 'decimal:2',
    ];

    public function remaining(): float
    {
        return max(0, round((float) $this->amount - (float) $this->claimed_amount, 2));
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function claims(): HasMany
    {
        return $this->hasMany(PreviousReturnClaim::class);
    }
}
