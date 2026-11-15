<?php

namespace App\Models;

use App\Services\ManufacturingOverheadService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManufacturingJob extends Model
{
    use HasFactory;

    protected $table = 'manufacturing_jobs';

    protected $fillable = [
        'job_number',
        'material_issue_id',
        'style_ref',
        'batch_ref',
        'issued_fabric_quantity',
        'accessories',
        'status',
        'issue_date',
        'created_by',
        'fabric_used_qty',
        'fabric_waste_qty',
        'total_cut_pieces',
        'product_id',
        'cutting_reject_qty',
    ];

    // Status Constants for safety
    const STATUS_FABRIC_ISSUED = 'FABRIC_ISSUED';
    const STATUS_SENT_TO_CUTTING = 'SENT_TO_CUTTING'; // Added strict step
    const STATUS_CUTTING_IN_PROGRESS = 'CUTTING_IN_PROGRESS';
    const STATUS_CUTTING_COMPLETED = 'CUTTING_COMPLETED';
    const STATUS_LINE_ASSIGNED = 'LINE_ASSIGNED';
    const STATUS_LINE_IN_PROGRESS = 'LINE_IN_PROGRESS';
    const STATUS_LINE_COMPLETED = 'LINE_COMPLETED';
    const STATUS_WASHING_OUT = 'WASHING_OUT';
    const STATUS_AFTER_WASH_RECEIVED = 'AFTER_WASH_RECEIVED';
    const STATUS_PACKING_COMPLETED = 'PACKING_COMPLETED';
    const STATUS_WAREHOUSE_RECEIVED = 'WAREHOUSE_RECEIVED';
    const STATUS_SOLD = 'SOLD';
    const STATUS_JOB_ENDED = 'JOB_ENDED';

    /** @deprecated Legacy DB value — use STATUS_WAREHOUSE_RECEIVED */
    const STATUS_LEGACY_COMPLETED = 'completed';

    /** Ordered workflow keys for UI stepper (maps status → step index). */
    public static function workflowStepOrder(): array
    {
        return [
            self::STATUS_FABRIC_ISSUED,
            self::STATUS_SENT_TO_CUTTING,
            self::STATUS_CUTTING_COMPLETED,
            self::STATUS_LINE_ASSIGNED,
            self::STATUS_LINE_IN_PROGRESS,
            self::STATUS_WASHING_OUT,
            self::STATUS_PACKING_COMPLETED,
            self::STATUS_WAREHOUSE_RECEIVED,
        ];
    }

    public static function workflowStepLabels(): array
    {
        return [
            self::STATUS_FABRIC_ISSUED => 'Fabric Issue',
            self::STATUS_SENT_TO_CUTTING => 'Sent to Cutting',
            self::STATUS_CUTTING_COMPLETED => 'Cutting Done',
            self::STATUS_LINE_ASSIGNED => 'Line Prod.',
            self::STATUS_WASHING_OUT => 'Washing',
            self::STATUS_PACKING_COMPLETED => 'Packing',
            self::STATUS_WAREHOUSE_RECEIVED => 'Finished',
        ];
    }

    public function workflowStepIndex(): int
    {
        $order = self::workflowStepOrder();
        $status = $this->status;

        if ($status === self::STATUS_CUTTING_IN_PROGRESS) {
            $status = self::STATUS_SENT_TO_CUTTING;
        }
        if (in_array($status, [self::STATUS_LINE_IN_PROGRESS, self::STATUS_LINE_COMPLETED], true)) {
            $status = self::STATUS_LINE_ASSIGNED;
        }
        if ($status === self::STATUS_AFTER_WASH_RECEIVED) {
            $status = self::STATUS_WASHING_OUT;
        }
        if ($status === self::STATUS_LEGACY_COMPLETED) {
            $status = self::STATUS_WAREHOUSE_RECEIVED;
        }
        if ($this->isEnded()) {
            return count($order);
        }

        $idx = array_search($status, $order, true);

        return $idx === false ? 0 : (int) $idx;
    }

    /** Stages available when reopening an ended job. */
    public static function reopenStageOptions(): array
    {
        return [
            self::STATUS_SENT_TO_CUTTING => 'Cutting — enter / update cutting',
            self::STATUS_CUTTING_COMPLETED => 'After cutting — assign lines again',
            self::STATUS_LINE_ASSIGNED => 'Line production (assigned)',
            self::STATUS_LINE_IN_PROGRESS => 'Line production (in progress)',
            self::STATUS_WASHING_OUT => 'Washing',
            self::STATUS_AFTER_WASH_RECEIVED => 'After wash received',
            self::STATUS_PACKING_COMPLETED => 'Packing',
        ];
    }

    public function isEnded(): bool
    {
        return $this->status === self::STATUS_JOB_ENDED;
    }

    public function canEnd(): bool
    {
        return !in_array($this->status, [self::STATUS_JOB_ENDED, self::STATUS_SOLD], true);
    }

    public function hasCuttingData(): bool
    {
        return (int) ($this->total_cut_pieces ?? 0) > 0;
    }

    public function hasLineAssignments(): bool
    {
        if ($this->relationLoaded('lineAssignments')) {
            return $this->lineAssignments->isNotEmpty();
        }

        return $this->lineAssignments()->exists();
    }

    /** Workflow action availability (not strict single-step). */
    public function hasRecipeProduct(): bool
    {
        return (bool) $this->product_id;
    }

    public function canSendToCutting(): bool
    {
        return $this->status === self::STATUS_FABRIC_ISSUED;
    }

    public function canEnterCuttingDetails(): bool
    {
        return $this->status !== self::STATUS_FABRIC_ISSUED && !$this->isEnded();
    }

    public function canAssignLines(): bool
    {
        return !$this->isEnded() && $this->hasCuttingData() && $this->hasRecipeProduct();
    }

    /** Line-level hourly totals only (excludes per-employee rows). */
    public function totalLineHourlyQuantity(): int
    {
        if (!$this->relationLoaded('hourlyProductions')) {
            $this->load('hourlyProductions');
        }

        return (int) $this->hourlyProductions
            ->whereNull('employee_id')
            ->sum('quantity');
    }

    /** Statuses where hourly output can still be recorded (including after a partial wash send). */
    public static function hourlyEntryStatuses(): array
    {
        return [
            self::STATUS_LINE_ASSIGNED,
            self::STATUS_LINE_IN_PROGRESS,
            self::STATUS_LINE_COMPLETED,
            self::STATUS_WASHING_OUT,
            self::STATUS_AFTER_WASH_RECEIVED,
        ];
    }

    /** Gate passes sent to washing (parent transfers only). */
    public function totalWashingSentQuantity(): int
    {
        if (!$this->relationLoaded('washingTransfers')) {
            $this->load('washingTransfers');
        }

        return (int) $this->washingTransfers
            ->filter(fn ($t) => $t->parent_id === null)
            ->sum('quantity_sent');
    }

    /** Pieces received back from wash (parent gate passes only — children would double-count). */
    public function totalWashingReturnedQuantity(): int
    {
        if (!$this->relationLoaded('washingTransfers')) {
            $this->load('washingTransfers');
        }

        return (int) $this->washingTransfers
            ->filter(fn ($t) => $t->parent_id === null)
            ->sum(fn ($t) => (int) ($t->quantity_returned ?? 0));
    }

    public function availableForWashingQuantity(): int
    {
        return $this->washingStockOnHand();
    }

    public function washingStock(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(WashingStock::class);
    }

    public function washingStockMovements(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WashingStockMovement::class)->latest();
    }

    public function washingStockReceived(): int
    {
        return app(\App\Services\WashingStockService::class)->received($this);
    }

    public function washingStockOnHand(): int
    {
        return app(\App\Services\WashingStockService::class)->onHand($this);
    }

    public function availableForWashingStockDeposit(): int
    {
        return app(\App\Services\WashingStockService::class)->availableToDeposit($this);
    }

    public function canRecordHourly(): bool
    {
        return $this->hasLineAssignments()
            && in_array($this->status, self::hourlyEntryStatuses(), true);
    }

    public function canCreateWashingGatePass(): bool
    {
        return !$this->isEnded() && $this->hasLineAssignments();
    }

    public function canAccessQcPacking(): bool
    {
        if ($this->isEnded()) {
            return false;
        }

        return $this->hasCuttingData()
            || $this->hasLineAssignments()
            || $this->washingTransfers()->exists();
    }

    public static function statusLabel(string $status): string
    {
        $labels = [
            self::STATUS_FABRIC_ISSUED => 'Fabric issued',
            self::STATUS_SENT_TO_CUTTING => 'Sent to cutting',
            self::STATUS_CUTTING_IN_PROGRESS => 'Cutting in progress',
            self::STATUS_CUTTING_COMPLETED => 'Cutting section complete',
            self::STATUS_LINE_ASSIGNED => 'Assigned to line',
            self::STATUS_LINE_IN_PROGRESS => 'Line in progress',
            self::STATUS_LINE_COMPLETED => 'Line production complete',
            self::STATUS_WASHING_OUT => 'Washing',
            self::STATUS_AFTER_WASH_RECEIVED => 'After wash received',
            self::STATUS_PACKING_COMPLETED => 'Packing completed',
            self::STATUS_WAREHOUSE_RECEIVED => 'Warehouse received',
            self::STATUS_SOLD => 'Sold',
            self::STATUS_JOB_ENDED => 'Job ended',
        ];
        return $labels[$status] ?? $status;
    }

    protected $casts = [
        'issue_date' => 'date',
        'accessories' => 'array',
    ];

    public function materialIssue()
    {
        return $this->belongsTo(MaterialIssue::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lineAssignments()
    {
        return $this->hasMany(JobLineAssignment::class);
    }

    public function hourlyProductions()
    {
        return $this->hasMany(HourlyProduction::class);
    }

    public function hourlyDailyTargets()
    {
        return $this->hasMany(HourlyProductionDailyTarget::class);
    }

    /** First line dispatch date, or job issue date. */
    public function productionStartDate(): \Carbon\Carbon
    {
        $dispatch = $this->lineAssignments()->min('dispatch_date');
        if ($dispatch) {
            return \Carbon\Carbon::parse($dispatch)->startOfDay();
        }

        return ($this->issue_date ?? $this->created_at)->copy()->startOfDay();
    }

    /** Dates allowed for hourly entry: job start → today (no future). */
    public function allowedProductionDates(): array
    {
        $start = $this->productionStartDate();
        $end = now()->startOfDay();
        if ($start->gt($end)) {
            return [$end->toDateString()];
        }

        $dates = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $dates[] = $d->toDateString();
        }

        return $dates;
    }

    public function hasDailyTargetsFor(string $date): bool
    {
        if (!$this->relationLoaded('lineAssignments')) {
            $this->load('lineAssignments');
        }

        $lines = $this->lineAssignments->pluck('line_name')->unique();
        if ($lines->isEmpty()) {
            return false;
        }

        $setCount = HourlyProductionDailyTarget::query()
            ->where('manufacturing_job_id', $this->id)
            ->where('production_date', $date)
            ->whereIn('line_name', $lines)
            ->count();

        return $setCount >= $lines->count();
    }

    public function washingTransfers()
    {
        return $this->hasMany(WashingTransfer::class);
    }

    public function materialIssues()
    {
        return $this->hasMany(MaterialIssue::class);
    }

    public function packingBatches()
    {
        return $this->hasMany(PackingBatch::class);
    }

    public function additionalCosts()
    {
        return $this->hasMany(ManufacturingJobAdditionalCost::class)->orderByDesc('created_at');
    }

    /**
     * Sum of snapshotted issue line costs for all material issues on this job.
     */
    public function estimatedMaterialCost(): float
    {
        if (!$this->relationLoaded('materialIssues')) {
            $this->load('materialIssues.material');
        }

        $sum = 0.0;
        foreach ($this->materialIssues as $issue) {
            $sum += $issue->effectiveLineCost();
        }

        return round($sum, 2);
    }

    /**
     * Fabric + accessory cost from stock issues (no admin-added production costs).
     */
    public function materialProductionCost(): float
    {
        $breakdown = $this->materialCostBreakdown();

        return round($breakdown['fabric'] + $breakdown['accessory'], 2);
    }

    /**
     * @return array{fabric: float, accessory: float}
     */
    public function materialCostBreakdown(): array
    {
        $this->loadMissing('materialIssues.material', 'packingBatches.materialIssues.material', 'materialIssue.material');

        $fabricCost = 0.0;
        $accessoryCost = 0.0;

        foreach ($this->materialIssues as $issue) {
            if (!$issue->material) {
                continue;
            }
            $line = $issue->effectiveLineCost();
            if ($issue->material->type === 'fabric') {
                $fabricCost += $line;
            } elseif ($issue->material->type === 'accessory') {
                $accessoryCost += $line;
            }
        }

        if ($fabricCost == 0.0 && (float) $this->fabric_used_qty > 0) {
            $fabricUnitPrice = 0.0;
            if ($this->materialIssue) {
                $fabricUnitPrice = $this->materialIssue->effectiveUnitCost();
            } else {
                $fabricMaterial = $this->materialIssues->first(fn ($i) => $i->material && $i->material->type === 'fabric');
                if ($fabricMaterial) {
                    $fabricUnitPrice = $fabricMaterial->effectiveUnitCost();
                }
            }
            $fabricCost = (float) $this->fabric_used_qty * $fabricUnitPrice;
        }

        foreach ($this->packingBatches as $batch) {
            foreach ($batch->materialIssues as $issue) {
                if (!$issue->material) {
                    continue;
                }
                // Avoid double-counting issues already included via job.materialIssues
                if ($this->materialIssues->contains('id', $issue->id)) {
                    continue;
                }
                $accessoryCost += $issue->effectiveLineCost();
            }
        }

        return [
            'fabric' => round($fabricCost, 2),
            'accessory' => round($accessoryCost, 2),
        ];
    }


    public function additionalProductionCost(): float
    {
        $this->loadMissing('additionalCosts');

        return round((float) $this->additionalCosts->sum('amount'), 2);
    }

    /**
     * Full production cost = materials + admin-added costs (sewing, washing, etc.).
     */
    public function totalProductionCost(): float
    {
        return round($this->materialProductionCost() + $this->additionalProductionCost(), 2);
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    public function costBreakdown(): array
    {
        $this->loadMissing('additionalCosts.creator');
        $material = $this->materialCostBreakdown();
        $additionalTotal = $this->additionalProductionCost();
        $output = $this->totalOutputQty();
        $total = $this->totalProductionCost();

        $byCategory = [];
        foreach (ManufacturingJobAdditionalCost::categoryOptions() as $key => $label) {
            $sum = (float) $this->additionalCosts->where('category', $key)->sum('amount');
            if ($sum > 0) {
                $byCategory[$key] = ['label' => $label, 'amount' => round($sum, 2)];
            }
        }

        $base = [
            'fabric' => $material['fabric'],
            'accessory' => $material['accessory'],
            'material_total' => round($material['fabric'] + $material['accessory'], 2),
            'additional_lines' => $this->additionalCosts,
            'additional_by_category' => $byCategory,
            'additional_total' => $additionalTotal,
            'total' => $total,
            'output_qty' => $output,
            'cost_per_unit' => $this->costPerUnit(),
        ];

        return app(ManufacturingOverheadService::class)->enrichCostBreakdown($this, $base);
    }

    /**
     * Total finished pieces across all packing batches (good + damage).
     */
    public function totalOutputQty(): float
    {
        $this->loadMissing('packingBatches');
        return (float) $this->packingBatches->sum('quantity');
    }

    /**
     * Per-piece cost for this manufacturing job. Returns 0 when no pieces produced yet.
     */
    public function costPerUnit(): float
    {
        $output = $this->totalOutputQty();
        if ($output <= 0) {
            return 0.0;
        }
        return round($this->totalProductionCost() / $output, 2);
    }

    /**
     * Target pieces for jobs list progress (cut total, else sum of line assignments).
     */
    public function progressTargetPieces(): int
    {
        $cut = (int) ($this->total_cut_pieces ?? 0);
        if ($cut > 0) {
            return $cut;
        }

        if ($this->relationLoaded('lineAssignments')) {
            return (int) $this->lineAssignments->sum('assigned_quantity');
        }

        return (int) $this->lineAssignments()->sum('assigned_quantity');
    }

    /**
     * Cumulative pieces produced (hourly line output + packing), whichever is further.
     */
    public function progressCompletedPieces(): int
    {
        if (!$this->relationLoaded('hourlyProductions')) {
            $this->load('hourlyProductions');
        }
        if (!$this->relationLoaded('packingBatches')) {
            $this->load('packingBatches');
        }

        $hourlyTotal = $this->totalLineHourlyQuantity();

        $packedTotal = (int) $this->packingBatches
            ->whereIn('status', ['completed', 'sent_to_final_check', 'packing'])
            ->sum('quantity');

        return max($hourlyTotal, $packedTotal);
    }

    public function progressPercent(): int
    {
        $target = $this->progressTargetPieces();
        if ($target <= 0) {
            return 0;
        }

        if (in_array($this->status, [self::STATUS_WAREHOUSE_RECEIVED, self::STATUS_SOLD, self::STATUS_LEGACY_COMPLETED], true)) {
            return 100;
        }

        return min(100, (int) round(($this->progressCompletedPieces() / $target) * 100));
    }
}
