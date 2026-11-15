<?php

namespace App\Observers;

use App\Models\VehicleDailyRunItem;
use App\Services\AccountingService;
use Illuminate\Support\Facades\Log;

class VehicleDailyRunItemObserver
{
    public function created(VehicleDailyRunItem $item): void
    {
        $itemId = (int) $item->id;
        defer(fn () => $this->postById($itemId));
    }

    public function updated(VehicleDailyRunItem $item): void
    {
        if (! $item->wasChanged(['type', 'name', 'cost'])) {
            return;
        }

        $itemId = (int) $item->id;
        defer(function () use ($itemId) {
            AccountingService::reverseEvent($this->eventKey($itemId));
            $this->postById($itemId);
        });
    }

    public function backfill(VehicleDailyRunItem $item): void
    {
        $this->postById((int) $item->id);
    }

    public function deleted(VehicleDailyRunItem $item): void
    {
        $itemId = (int) $item->id;
        defer(fn () => AccountingService::reverseEvent($this->eventKey($itemId)));
    }

    private function postById(int $itemId): void
    {
        $item = VehicleDailyRunItem::query()->with('run.vehicle')->find($itemId);
        if (! $item) {
            return;
        }

        $this->post($item);
    }

    private function post(VehicleDailyRunItem $item): void
    {
        try {
            $amount = round((float) $item->cost, 2);
            if ($amount <= 0) {
                return;
            }

            $debitId = AccountingService::accountId(AccountingService::GL_TRANSPORT_EXPENSE)
                ?? AccountingService::accountId(AccountingService::GL_GENERAL_EXPENSE);
            $creditId = AccountingService::accountId(AccountingService::GL_CASH);

            if (! $debitId || ! $creditId) {
                Log::warning('VehicleDailyRunItemObserver: chart of accounts not seeded yet');
                return;
            }

            $date = $item->run?->run_date instanceof \DateTimeInterface
                ? $item->run->run_date->format('Y-m-d')
                : now()->toDateString();

            $vehicle = $item->run?->vehicle?->number_plate ?? $item->run?->vehicle?->name ?? 'Vehicle';
            $label = trim(($item->name ?: ucfirst((string) $item->type)) . ' — ' . $vehicle);

            AccountingService::postOnce(
                $this->eventKey((int) $item->id),
                VehicleDailyRunItem::class,
                (int) $item->id,
                $debitId,
                $creditId,
                $amount,
                $date,
                'Vehicle run expense – ' . $label,
                "VRUN-{$item->id}",
                'vehicle_run_expense'
            );
        } catch (\Throwable $e) {
            Log::error('VehicleDailyRunItemObserver: accounting post failed', [
                'vehicle_daily_run_item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function eventKey(int $itemId): string
    {
        return "vehicle-run-item:{$itemId}:entry";
    }
}
