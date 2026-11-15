<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialIssue extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_id',
        'quantity_issued',
        'unit_cost',
        'line_cost',
        'issued_to',
        'manufacturing_job_id',
        'production_section_id',
        'recipe_step_id',
        'packing_batch_id',
    ];

    protected $casts = [
        'quantity_issued' => 'decimal:2',
        'unit_cost' => 'decimal:4',
        'line_cost' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (MaterialIssue $issue) {
            $issue->snapshotCostFromMaterial();
        });
    }

    /**
     * Snapshot purchase-driven unit cost onto the issue.
     * New issues / material changes take current materials.unit_price;
     * quantity-only updates keep the existing unit_cost and recalc line_cost.
     */
    public function snapshotCostFromMaterial(): void
    {
        $needsUnitRefresh = ! $this->exists
            || $this->isDirty('material_id')
            || $this->unit_cost === null;

        if ($needsUnitRefresh) {
            $material = $this->relationLoaded('material')
                ? $this->material
                : ($this->material_id ? Material::query()->find($this->material_id) : null);

            $this->unit_cost = round((float) ($material?->unit_price ?? 0), 4);
        }

        $this->line_cost = round((float) $this->quantity_issued * (float) $this->unit_cost, 2);
    }

    /** Effective unit cost for reporting (snapshot, else live material price). */
    public function effectiveUnitCost(): float
    {
        if ($this->unit_cost !== null) {
            return (float) $this->unit_cost;
        }

        return (float) ($this->material?->unit_price ?? 0);
    }

    /** Effective line cost for reporting. */
    public function effectiveLineCost(): float
    {
        if ($this->line_cost !== null) {
            return (float) $this->line_cost;
        }

        return round((float) $this->quantity_issued * $this->effectiveUnitCost(), 2);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function job()
    {
        return $this->belongsTo(ManufacturingJob::class, 'manufacturing_job_id');
    }

    public function productionSection()
    {
        return $this->belongsTo(ProductionSection::class);
    }

    public function recipeStep()
    {
        return $this->belongsTo(RecipeStep::class);
    }

    public function packingBatch()
    {
        return $this->belongsTo(PackingBatch::class, 'packing_batch_id');
    }

    public function cuttingBatches()
    {
        return $this->hasMany(CuttingBatch::class);
    }
}
