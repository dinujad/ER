<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'user_name', 'event', 'auditable_type', 'auditable_id', 'document_label',
        'old_values', 'new_values', 'note', 'ip_address', 'user_agent', 'url', 'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    /** Human labels for custom business events. */
    public const EVENT_LABELS = [
        'created' => 'Created',
        'updated' => 'Edited',
        'deleted' => 'Deleted',
        'invoice_issued' => 'Invoice issued',
        'invoice_edited' => 'Invoice amounts edited',
        'invoice_printed' => 'Invoice PDF downloaded',
        'invoice_voided' => 'Invoice voided',
        'credit_note_issued' => 'Credit note issued',
        'reminder_sent' => 'Payment reminder sent',
        'bill_approved' => 'Bill approved',
        'recoded' => 'Recoded (find & recode)',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function eventLabel(): string
    {
        return self::EVENT_LABELS[$this->event] ?? ucwords(str_replace('_', ' ', $this->event));
    }

    public function documentTypeLabel(): string
    {
        return ucwords(str_replace('_', ' ', \Illuminate\Support\Str::snake(class_basename($this->auditable_type))));
    }

    /**
     * Field-level changes for display: [['field' => 'Total amount', 'old' => '1,000.00', 'new' => '1,200.00'], ...]
     *
     * @return array<int, array{field: string, old: string, new: string}>
     */
    public function changes(): array
    {
        $old = (array) ($this->old_values ?? []);
        $new = (array) ($this->new_values ?? []);
        $keys = array_values(array_unique(array_merge(array_keys($old), array_keys($new))));

        return array_map(fn (string $key) => [
            'field' => ucfirst(str_replace('_', ' ', $key)),
            'old' => self::display($old[$key] ?? null),
            'new' => self::display($new[$key] ?? null),
        ], $keys);
    }

    public static function display(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return (string) $value;
    }
}
