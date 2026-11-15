<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesRouteRun extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'user_id',
        'sales_route_id',
        'started_at',
        'completed_at',
        'status',
        'notes',
        'driver_name',
        'assistants',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function salesPerson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function salesRoute(): BelongsTo
    {
        return $this->belongsTo(SalesRoute::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(SalesPersonExpense::class, 'sales_route_run_id');
    }

    public function pettyCashEntries(): HasMany
    {
        return $this->hasMany(SalesRoutePettyCash::class, 'sales_route_run_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function endedAt(): \Carbon\CarbonInterface
    {
        return $this->completed_at ?? now();
    }

    public function startedLabel(string $format = 'd M Y, h:i A'): string
    {
        return $this->started_at?->format($format) ?? '—';
    }

    public function durationSeconds(): int
    {
        if (! $this->started_at) {
            return 0;
        }

        return (int) max(0, $this->started_at->diffInSeconds($this->endedAt()));
    }

    public function durationLabel(): string
    {
        $seconds = $this->durationSeconds();
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'm';
        }

        return $minutes . 'm';
    }
}
