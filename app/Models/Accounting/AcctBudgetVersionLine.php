<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcctBudgetVersionLine extends Model
{
    protected $table = 'acct_budget_version_lines';

    protected $fillable = [
        'budget_version_id', 'chart_of_account_id',
        'month_1', 'month_2', 'month_3', 'month_4', 'month_5', 'month_6',
        'month_7', 'month_8', 'month_9', 'month_10', 'month_11', 'month_12',
        'annual_total',
    ];

    protected $casts = [
        'month_1' => 'decimal:4',
        'month_2' => 'decimal:4',
        'month_3' => 'decimal:4',
        'month_4' => 'decimal:4',
        'month_5' => 'decimal:4',
        'month_6' => 'decimal:4',
        'month_7' => 'decimal:4',
        'month_8' => 'decimal:4',
        'month_9' => 'decimal:4',
        'month_10' => 'decimal:4',
        'month_11' => 'decimal:4',
        'month_12' => 'decimal:4',
        'annual_total' => 'decimal:4',
    ];

    public function budgetVersion(): BelongsTo
    {
        return $this->belongsTo(AcctBudgetVersion::class, 'budget_version_id');
    }

    public function monthAmount(int $month): float
    {
        $key = 'month_'.$month;

        return round((float) ($this->{$key} ?? 0), 2);
    }
}