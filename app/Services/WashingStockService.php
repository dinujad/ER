<?php

namespace App\Services;

use App\Models\ManufacturingJob;
use App\Models\WashingStock;
use App\Models\WashingStockMovement;
use App\Models\WashingTransfer;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WashingStockService
{
    public function received(ManufacturingJob $job): int
    {
        if ($job->relationLoaded('washingStock')) {
            return (int) ($job->washingStock?->quantity_received ?? 0);
        }

        return (int) (WashingStock::query()->where('manufacturing_job_id', $job->id)->value('quantity_received') ?? 0);
    }

    public function onHand(ManufacturingJob $job): int
    {
        return max(0, $this->received($job) - $job->totalWashingSentQuantity());
    }

    public function availableToDeposit(ManufacturingJob $job): int
    {
        return max(0, $job->totalLineHourlyQuantity() - $this->received($job));
    }

    public function deposit(ManufacturingJob $job, int $qty, ?int $userId = null, ?string $note = null): WashingStock
    {
        if ($qty < 1) {
            throw new RuntimeException('Enter how many pieces to put into washing stock.');
        }

        return DB::transaction(function () use ($job, $qty, $userId, $note) {
            $job->refresh();
            $job->load(['hourlyProductions', 'washingStock']);

            $available = $this->availableToDeposit($job);
            if ($qty > $available) {
                throw new RuntimeException("Cannot deposit more than available from line ($available pcs). Produced: {$job->totalLineHourlyQuantity()}, already in stock: {$this->received($job)}.");
            }

            $stock = WashingStock::query()
                ->where('manufacturing_job_id', $job->id)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                $stock = WashingStock::create([
                    'manufacturing_job_id' => $job->id,
                    'quantity_received' => $qty,
                ]);
            } else {
                $stock->increment('quantity_received', $qty);
                $stock->refresh();
            }

            WashingStockMovement::create([
                'manufacturing_job_id' => $job->id,
                'washing_stock_id' => $stock->id,
                'type' => WashingStockMovement::TYPE_IN,
                'quantity' => $qty,
                'user_id' => $userId,
                'note' => $note,
            ]);

            return $stock;
        });
    }

    public function recordGatePassOut(ManufacturingJob $job, WashingTransfer $transfer, ?int $userId = null): void
    {
        $qty = (int) $transfer->quantity_sent;
        if ($qty < 1) {
            return;
        }

        $stock = WashingStock::query()->where('manufacturing_job_id', $job->id)->first();
        if (! $stock) {
            return;
        }

        WashingStockMovement::create([
            'manufacturing_job_id' => $job->id,
            'washing_stock_id' => $stock->id,
            'type' => WashingStockMovement::TYPE_OUT,
            'quantity' => $qty,
            'user_id' => $userId,
            'note' => 'Gate pass #'.$transfer->id,
            'washing_transfer_id' => $transfer->id,
        ]);
    }
}
