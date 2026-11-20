<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Records who created / edited / deleted the model and what changed (Phase 6.1 audit trail).
 * Optional on the model: `protected array $auditExclude = [...]` and `public function auditLabel(): string`.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn ($model) => AuditLogger::recordModelEvent($model, 'created'));
        static::updated(fn ($model) => AuditLogger::recordModelEvent($model, 'updated'));
        static::deleted(fn ($model) => AuditLogger::recordModelEvent($model, 'deleted'));
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable')->orderByDesc('id');
    }
}
