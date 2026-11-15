<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalaryComponent extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'type', 'calculation_type', 'default_value', 'is_active', 'sort_order'];

    const TYPE_ALLOWANCE = 'allowance';
    const TYPE_DEDUCTION = 'deduction';
    const CALC_FIXED = 'fixed';
    const CALC_PERCENTAGE = 'percentage';

    protected $casts = [
        'default_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function isAllowance(): bool
    {
        return $this->type === self::TYPE_ALLOWANCE;
    }

    public function isDeduction(): bool
    {
        return $this->type === self::TYPE_DEDUCTION;
    }
}
