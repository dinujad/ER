<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcctBudgetVersion extends Model
{
    protected $table = 'acct_budget_versions';

    protected $fillable = [
        'business_id', 'budget_id', 'version_no', 'name', 'financial_year', 'status',
        'cost_center_id', 'department_id', 'job_id', 'notes',
        'created_by', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(AcctBudgetVersionLine::class, 'budget_version_id');
    }
}