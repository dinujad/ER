<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WashingTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'manufacturing_job_id',
        'parent_id',
        'quantity_sent',
        'sent_from',
        'washing_type',
        'vehicle_number',
        'washing_factory_name',
        'admin_signatory',
        'driver_signatory',
        'factory_signatory',
        'remarks',
        'batch_no',
        'status',
        'quantity_returned',
    ];

    public static function washingTypeOptions(): array
    {
        return [
            'normal_wash' => 'Normal Wash',
            'stone_wash' => 'Stone Wash',
            'enzyme_wash' => 'Enzyme Wash',
            'bleach_wash' => 'Bleach Wash',
            'acid_wash' => 'Acid Wash',
            'garment_dye' => 'Garment Dye',
            'softener_wash' => 'Softener / Silicon Wash',
            'other' => 'Other',
        ];
    }

    public function getWashingTypeLabelAttribute(): string
    {
        return self::washingTypeOptions()[$this->washing_type] ?? ($this->washing_type ?: '—');
    }

    public function job()
    {
        return $this->belongsTo(ManufacturingJob::class, 'manufacturing_job_id');
    }

    public function qcChecks()
    {
        return $this->hasMany(QcCheck::class);
    }

    public function qcCheck()
    {
        return $this->hasOne(QcCheck::class)->latest();
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function isReceiptBatch(): bool
    {
        return !is_null($this->parent_id);
    }

    /** Outbound gate passes only (exclude child receipt rows). */
    public function scopeParentGatePasses($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeReadyForQc($query)
    {
        return $query->where('status', 'washing_completed');
    }

    public static function statusOptions(): array
    {
        return [
            'pending' => 'Gate Pass Created',
            'received' => 'In Wash Factory',
            'fully_received' => 'Fully Received (All Pieces Back)',
            'received_back' => 'Received Back (Ready for QC)',
            'washing_completed' => 'Sent to QC',
            'returned' => 'Returned (Legacy)',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusOptions()[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    /** Pieces still at wash factory (parent gate passes only). */
    public function pendingAtWashQuantity(): int
    {
        if ($this->isReceiptBatch()) {
            return 0;
        }

        return max(0, (int) $this->quantity_sent - (int) ($this->quantity_returned ?? 0));
    }
}
