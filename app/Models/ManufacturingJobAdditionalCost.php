<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManufacturingJobAdditionalCost extends Model
{
    public const CAT_SEWING = 'sewing';

    public const CAT_EMBROIDERY = 'embroidery';

    public const CAT_PRINTING = 'printing';

    public const CAT_WASHING = 'washing';

    public const CAT_TRANSPORT = 'transport';

    public const CAT_LABOUR = 'labour';

    public const CAT_OVERHEAD = 'overhead';

    public const CAT_OTHER = 'other';

    protected $fillable = [
        'manufacturing_job_id',
        'category',
        'description',
        'amount',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public static function categoryOptions(): array
    {
        return [
            self::CAT_SEWING => 'Sewing (Direct Labor)',
            self::CAT_EMBROIDERY => 'Embroidery',
            self::CAT_PRINTING => 'Printing',
            self::CAT_WASHING => 'Washing / Dyeing',
            self::CAT_TRANSPORT => 'Transport',
            self::CAT_LABOUR => 'Labour',
            self::CAT_OVERHEAD => 'Overhead',
            self::CAT_OTHER => 'Other',
        ];
    }

    public function categoryLabel(): string
    {
        return self::categoryOptions()[$this->category] ?? ucfirst($this->category);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(ManufacturingJob::class, 'manufacturing_job_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
