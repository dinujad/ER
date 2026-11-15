<?php

namespace App\Services;

use App\Models\SalesRouteRun;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class SalesRouteRunService
{
    public function isReady(): bool
    {
        return Schema::hasTable('sales_route_runs')
            && Schema::hasColumn('sales_route_runs', 'driver_name');
    }

    public function activeRunFor(User $salesPerson): ?SalesRouteRun
    {
        if (! $this->isReady()) {
            return null;
        }

        return SalesRouteRun::query()
            ->with(['salesRoute', 'expenses'])
            ->where('user_id', $salesPerson->id)
            ->active()
            ->latest('started_at')
            ->first();
    }

    public function recentFor(User $salesPerson, int $limit = 8): Collection
    {
        if (! $this->isReady()) {
            return collect();
        }

        return $salesPerson->salesRouteRuns()
            ->with(['salesRoute', 'expenses'])
            ->latest('started_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  array{driver_name: string, assistants?: string|null}  $crew
     */
    public function start(User $salesPerson, array $crew): SalesRouteRun
    {
        $this->assertReady();

        return DB::transaction(function () use ($salesPerson, $crew) {
            User::query()->whereKey($salesPerson->id)->lockForUpdate()->first();

            $existing = SalesRouteRun::query()
                ->where('user_id', $salesPerson->id)
                ->active()
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw new RuntimeException('You already have a started route. Complete it before starting another.');
            }

            $salesPerson->refresh();
            $route = $salesPerson->salesRoute;
            if (! $route || ! $route->is_active) {
                throw new RuntimeException('No active sales route is assigned. Ask admin to assign a route first.');
            }

            $driverName = trim((string) ($crew['driver_name'] ?? ''));
            $assistants = trim((string) ($crew['assistants'] ?? ''));

            if ($driverName === '') {
                throw new RuntimeException('Enter the driver name before starting the route.');
            }

            return SalesRouteRun::create([
                'user_id' => $salesPerson->id,
                'sales_route_id' => $route->id,
                'started_at' => now(),
                'status' => SalesRouteRun::STATUS_ACTIVE,
                'driver_name' => $driverName,
                'assistants' => $assistants !== '' ? $assistants : null,
            ]);
        });
    }

    public function complete(User $salesPerson): SalesRouteRun
    {
        $this->assertReady();

        return DB::transaction(function () use ($salesPerson) {
            User::query()->whereKey($salesPerson->id)->lockForUpdate()->first();

            $runs = SalesRouteRun::query()
                ->where('user_id', $salesPerson->id)
                ->active()
                ->orderBy('started_at')
                ->lockForUpdate()
                ->get();

            if ($runs->isEmpty()) {
                throw new RuntimeException('No started route to complete.');
            }

            $now = now();
            foreach ($runs as $run) {
                $run->update([
                    'completed_at' => $now,
                    'status' => SalesRouteRun::STATUS_COMPLETED,
                ]);
            }

            $latest = $runs->sortByDesc(fn (SalesRouteRun $run) => $run->started_at?->timestamp ?? 0)->first();
            if (! $latest) {
                throw new RuntimeException('No started route to complete.');
            }

            return $latest->fresh(['salesRoute', 'expenses']);
        });
    }

    protected function assertReady(): void
    {
        if (! $this->isReady()) {
            throw new RuntimeException('Route trips are not set up yet. Ask admin to run database migrations.');
        }
    }
}
