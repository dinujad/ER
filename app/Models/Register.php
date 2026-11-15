<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Register extends Model
{
    protected $fillable = [
        'store_id', 'opening_cash', 'closing_cash', 'expected_cash', 'difference',
        'status', 'opened_at', 'closed_at', 'opening_note', 'closing_note',
        'opened_by', 'closed_by',
    ];

    protected $casts = [
        'opening_cash' => 'decimal:2',
        'closing_cash' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'difference' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function openedBy()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
