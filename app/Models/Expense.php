<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'expense_category_id',
        'amount',
        'payment_method',
        'expense_date',
        'reference',
        'note',
        'created_by',
        'register_id',
        'accounting_txn_number',
    ];

    public function register()
    {
        return $this->belongsTo(Register::class);
    }

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
