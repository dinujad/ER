<?php

namespace App\Services\Manufacturing;

use App\Models\ManufacturingJob;
use App\Models\Material;
use App\Models\MaterialIssue;
use App\Models\ProductRecipe;
use App\Services\WorkflowNotificationService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class JobWorkflowService
{
    public function assertCanSendToCutting(ManufacturingJob $job): void
    {
        if (!$job->canSendToCutting()) {
            throw new RuntimeException('Job must be in fabric-issued state to send to cutting.');
        }
    }

    public function assertProductHasRecipe(int $productId): void
    {
        if (!ProductRecipe::where('product_id', $productId)->exists()) {
            throw new RuntimeException('Selected product has no recipe. Create a recipe first.');
        }
    }

    public function sendToCuttingWithRecipe(ManufacturingJob $job, int $productId): void
    {
        $this->assertCanSendToCutting($job);
        $this->assertProductHasRecipe($productId);

        $job->update([
            'product_id' => $productId,
            'status' => ManufacturingJob::STATUS_SENT_TO_CUTTING,
        ]);

        WorkflowNotificationService::notifyTransfer($job, 'cutting');
    }

    public function assertCanAssignLines(ManufacturingJob $job): void
    {
        if (!$job->canAssignLines()) {
            throw new RuntimeException('Complete cutting details first before assigning lines.');
        }

        if (!$job->hasRecipeProduct()) {
            throw new RuntimeException('Recipe must be selected before send to cutting.');
        }
    }

    /**
     * @param  array<string, int>  $lines  line_name => qty
     * @param  array<int, array{issue_qty?: float}>  $materials
     */
    public function assignLines(ManufacturingJob $job, array $lines, string $dispatchDate, array $materials = []): void
    {
        $this->assertCanAssignLines($job);

        $totalAssigned = (int) array_sum($lines);
        $totalCut = (int) $job->total_cut_pieces;

        if ($totalAssigned < 1) {
            throw new RuntimeException('Assign at least 1 piece to a line.');
        }

        if ($totalAssigned > $totalCut) {
            throw new RuntimeException("Total assigned ({$totalAssigned}) cannot exceed total cut pieces ({$totalCut}).");
        }

        DB::transaction(function () use ($job, $lines, $dispatchDate, $materials) {
            $job->lineAssignments()->delete();

            $job->update(['status' => ManufacturingJob::STATUS_LINE_ASSIGNED]);

            foreach ($lines as $lineName => $qty) {
                if ($qty > 0) {
                    $job->lineAssignments()->create([
                        'line_name' => $lineName,
                        'assigned_quantity' => $qty,
                        'dispatch_date' => $dispatchDate,
                    ]);
                    WorkflowNotificationService::notifyTransfer($job, null, $lineName);
                }
            }

            $this->issueAccessoryMaterials($job, $materials);
        });
    }

    /**
     * @param  array<int, array{issue_qty?: float}>  $materials
     */
    public function issueAccessoryMaterials(ManufacturingJob $job, array $materials): void
    {
        foreach ($materials as $matId => $data) {
            $qty = (float) ($data['issue_qty'] ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $material = Material::lockForUpdate()->find($matId);
            if (!$material) {
                throw new RuntimeException("Material #{$matId} not found.");
            }
            if ($material->quantity < $qty) {
                throw new RuntimeException("{$material->name}: need {$qty}, only {$material->quantity} in stock.");
            }

            $material->decrement('quantity', $qty);
            MaterialIssue::create([
                'manufacturing_job_id' => $job->id,
                'material_id' => $material->id,
                'quantity_issued' => $qty,
                'issued_to' => "Job {$job->job_number} (Line Issue)",
            ]);
        }
    }
}
