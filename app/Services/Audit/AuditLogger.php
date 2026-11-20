<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Writes the ERP audit trail. Never throws: a failed audit write is logged, the business save continues.
 */
class AuditLogger
{
    /** Columns never written to the trail. */
    private const ALWAYS_EXCLUDED = ['updated_at', 'created_at', 'password', 'remember_token'];

    private static ?bool $tableExists = null;

    private static bool $paused = false;

    public static function enabled(): bool
    {
        if (self::$paused) {
            return false;
        }
        if (self::$tableExists === null) {
            try {
                self::$tableExists = Schema::hasTable('audit_logs');
            } catch (\Throwable) {
                self::$tableExists = false;
            }
        }

        return self::$tableExists;
    }

    /** For tests / schema rebuilds. */
    public static function flushCache(): void
    {
        self::$tableExists = null;
    }

    /** Run a callback without writing audit rows (bulk maintenance commands). */
    public static function withoutAuditing(callable $callback): mixed
    {
        $previous = self::$paused;
        self::$paused = true;
        try {
            return $callback();
        } finally {
            self::$paused = $previous;
        }
    }

    public static function recordModelEvent(Model $model, string $event): void
    {
        if (!self::enabled()) {
            return;
        }

        $excluded = array_merge(self::ALWAYS_EXCLUDED, self::modelExcluded($model));
        $old = [];
        $new = [];

        if ($event === 'created') {
            $new = self::filter($model->getAttributes(), $excluded);
        } elseif ($event === 'updated') {
            $changes = self::filter($model->getChanges(), $excluded);
            if ($changes === []) {
                return;
            }
            foreach (array_keys($changes) as $key) {
                $old[$key] = $model->getOriginal($key);
            }
            $old = self::filter($old, []);
            $new = $changes;
        } elseif ($event === 'deleted') {
            $old = self::filter($model->getAttributes(), $excluded);
        }

        self::write($model, $event, $old, $new);
    }

    /**
     * Business action on a document (invoice issued, voided, printed …).
     *
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    public static function record(Model $model, string $event, array $old = [], array $new = [], ?string $note = null): void
    {
        if (!self::enabled()) {
            return;
        }

        self::write($model, $event, $old, $new, $note);
    }

    private static function write(Model $model, string $event, array $old, array $new, ?string $note = null): void
    {
        try {
            $user = auth()->user();
            $request = app()->runningInConsole() ? null : request();

            AuditLog::query()->create([
                'user_id' => $user?->getAuthIdentifier(),
                'user_name' => $user ? mb_substr((string) ($user->name ?? ('User #' . $user->getAuthIdentifier())), 0, 120) : 'System',
                'event' => mb_substr($event, 0, 40),
                'auditable_type' => $model->getMorphClass(),
                'auditable_id' => $model->getKey(),
                'document_label' => mb_substr(self::label($model), 0, 160),
                'old_values' => $old === [] ? null : $old,
                'new_values' => $new === [] ? null : $new,
                'note' => $note,
                'ip_address' => $request?->ip(),
                'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 255) : null,
                'url' => $request ? mb_substr($request->fullUrl(), 0, 255) : null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('AuditLogger: could not write audit row', [
                'model' => $model::class,
                'id' => $model->getKey(),
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public static function label(Model $model): string
    {
        if (method_exists($model, 'auditLabel')) {
            return (string) $model->auditLabel();
        }

        return class_basename($model) . ' #' . $model->getKey();
    }

    /** @return array<int, string> */
    private static function modelExcluded(Model $model): array
    {
        return property_exists($model, 'auditExclude') ? (array) $model->auditExclude : [];
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  array<int, string>  $excluded
     * @return array<string, mixed>
     */
    private static function filter(array $values, array $excluded): array
    {
        $out = [];
        foreach ($values as $key => $value) {
            if (in_array($key, $excluded, true)) {
                continue;
            }
            if ($value instanceof \DateTimeInterface) {
                $value = $value->format('Y-m-d H:i:s');
            } elseif (is_object($value)) {
                $value = method_exists($value, '__toString') ? (string) $value : json_decode(json_encode($value), true);
            }
            $out[$key] = $value;
        }

        return $out;
    }
}
