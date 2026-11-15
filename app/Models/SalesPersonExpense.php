<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesPersonExpense extends Model
{
    public const TYPE_FUEL = 'fuel';

    public const TYPE_TRAVEL = 'travel';

    public const TYPE_FOOD = 'food';

    public const TYPE_ACCOMMODATION = 'accommodation';

    public const TYPE_SUPPLIES = 'supplies';

    public const TYPE_OTHERS = 'others';

    public static function typeOptions(): array
    {
        return [
            self::TYPE_FUEL => 'Fuel',
            self::TYPE_TRAVEL => 'Travel / transport',
            self::TYPE_FOOD => 'Food',
            self::TYPE_ACCOMMODATION => 'Accommodation',
            self::TYPE_SUPPLIES => 'Supplies',
            self::TYPE_OTHERS => 'Others',
        ];
    }

    protected $fillable = [
        'user_id',
        'sales_route_run_id',
        'expense_date',
        'type',
        'description',
        'amount',
        'slip_path',
        'notes',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function routeRun(): BelongsTo
    {
        return $this->belongsTo(SalesRouteRun::class, 'sales_route_run_id');
    }

    public function typeLabel(): string
    {
        if ($this->type === null || $this->type === '') {
            return '—';
        }

        return self::typeOptions()[$this->type] ?? ucfirst((string) $this->type);
    }
}
