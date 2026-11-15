<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesRoutePettyCash extends Model
{
    protected $table = 'sales_route_petty_cash';

    protected $fillable = [
        'sales_route_run_id',
        'amount',
        'note',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function routeRun(): BelongsTo
    {
        return $this->belongsTo(SalesRouteRun::class, 'sales_route_run_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
