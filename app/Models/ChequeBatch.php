<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChequeBatch extends Model
{
    protected $fillable = [
        'batch_number',
        'user_id',
        'amount',
        'payment_date',
        'cheque_date',
        'cheque_number',
        'cheque_bank',
        'cheque_image_path',
        'cheque_exception_requested',
        'cheque_exception_reason',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'cheque_date' => 'date',
        'cheque_exception_requested' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public static function numberForId(int $id): string
    {
        return 'MC'.str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    }
}
