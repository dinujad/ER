<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcctCostCenter extends Model
{
    protected $table = 'acct_cost_centers';

    protected $fillable = [
        'business_id', 'code', 'name', 'description',
        'parent_id', 'department_id', 'manager_user_id', 'status',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}