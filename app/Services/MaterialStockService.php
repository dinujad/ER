<?php

namespace App\Services;

use App\Models\Material;
use App\Models\MaterialStockMovement;
use Illuminate\Support\Facades\DB;

class MaterialStockService
{
    /**
     * Apply a signed quantity change and write stock history.
     *
     * @throws \InvalidArgumentException when outbound would drive stock below zero (optional check)
     */
    public function applyMovement(
        Material $material,
        float $quantityChange,
        string $movementType,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $referenceLabel = null,
        ?string $notes = null,
        ?int $userId = null,
        bool $preventNegative = true
    ): MaterialStockMovement {
        if (abs($quantityChange) < 0.00001) {
            throw new \InvalidArgumentException('Movement quantity cannot be zero.');
        }

        return DB::transaction(function () use (
            $material,
            $quantityChange,
            $movementType,
            $referenceType,
            $referenceId,
            $referenceLabel,
            $notes,
            $userId,
            $preventNegative
        ) {
            $locked = Material::query()->whereKey($material->id)->lockForUpdate()->first();
            if (!$locked) {
                throw new \RuntimeException('Material not found.');
            }

            $before = (float) $locked->quantity;
            $after = round($before + $quantityChange, 2);

            if ($preventNegative && $after < -0.00001) {
                throw new \InvalidArgumentException(
                    "Insufficient stock for {$locked->name}. Available: {$before}, change: {$quantityChange}."
                );
            }

            $locked->update(['quantity' => $after]);

            return MaterialStockMovement::create([
                'material_id' => $locked->id,
                'movement_type' => $movementType,
                'quantity' => round($quantityChange, 2),
                'balance_after' => $after,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reference_label' => $referenceLabel,
                'notes' => $notes,
                'created_by' => $userId,
            ]);
        });
    }
}
