<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesCommission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'month',
        'cash_rate',
        'cheque_rate',
        'status',
        'payment_method',
        'paid_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
