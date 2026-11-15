<?php

namespace Modules\Accounting\Entities;

use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    protected $fillable = [
        'chart_of_account_id',
        'business_id',
        'financial_year',
        'month_1',
        'month_2',
        'month_3',
        'month_4',
        'month_5',
        'month_6',
        'month_7',
        'month_8',
        'month_9',
        'month_10',
        'month_11',
        'month_12',
    ];

    protected $appends = ['quarterly', 'yearly'];

    public function getQuarterlyAttribute()
    {
        return collect([
            1 => (float) $this->month_1 + (float) $this->month_2 + (float) $this->month_3,
            2 => (float) $this->month_4 + (float) $this->month_5 + (float) $this->month_6,
            3 => (float) $this->month_7 + (float) $this->month_8 + (float) $this->month_9,
            4 => (float) $this->month_10 + (float) $this->month_11 + (float) $this->month_12,
        ]);
    }

    public function getYearlyAttribute()
    {
        return $this->quarterly->sum();
    }
}
