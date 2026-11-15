<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AcctCostAllocation;
use App\Models\Accounting\AcctCostAllocationLine;
use App\Models\Accounting\AcctCostCenter;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CostCenterService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function create(array $data, ?int $userId = null): AcctCostCenter
    {
        $parentId = isset($data['parent_id']) ? (int) $data['parent_id'] : null;
        if ($parentId) {
            self::assertNoCircularParent(null, $parentId);
        }

        $cc = AcctCostCenter::create([
            'business_id' => (int) ($data['business_id'] ?? 0),
            'code' => $data['code'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'parent_id' => $parentId,
            'department_id' => $data['department_id'] ?? null,
            'manager_user_id' => $data['manager_user_id'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);

        AccountingAuditService::record('cost_center.created', $cc, null, $cc->toArray(), null, (int) $cc->business_id, $userId);

        return $cc;
    }

    public static function assertNoCircularParent(?int $selfId, int $parentId): void
    {
        $visited = [];
        $current = $parentId;
        while ($current) {
            if ($selfId && $current === $selfId) {
                throw new RuntimeException('Cost center hierarchy cannot be circular.');
            }
            if (in_array($current, $visited, true)) {
                throw new RuntimeException('Cost center hierarchy cannot be circular.');
            }
            $visited[] = $current;
            $current = (int) (AcctCostCenter::where('id', $current)->value('parent_id') ?: 0);
        }
    }

    /**
     * @param  array<int, array{cost_center_id:int, amount:float|string, percentage?:float|string}>  $lines
     */
    public static function createAllocation(array $header, array $lines, ?int $userId = null): AcctCostAllocation
    {
        $sourceAmount = round((float) ($header['source_amount'] ?? 0), 2);
        if ($sourceAmount <= 0) {
            throw new RuntimeException('Allocation source amount must be greater than zero.');
        }

        $lineTotal = 0.0;
        $normalized = [];
        foreach ($lines as $i => $line) {
            $amount = round((float) ($line['amount'] ?? 0), 2);
            if ($amount < 0) {
                throw new RuntimeException('Line '.($i + 1).': allocation amount cannot be negative.');
            }
            $ccId = (int) ($line['cost_center_id'] ?? 0);
            if ($ccId <= 0) {
                throw new RuntimeException('Line '.($i + 1).': cost center is required.');
            }
            $lineTotal = round($lineTotal + $amount, 2);
            $normalized[] = [
                'cost_center_id' => $ccId,
                'amount' => $amount,
                'percentage' => isset($line['percentage']) ? round((float) $line['percentage'], 4) : null,
            ];
        }

        if (number_format($lineTotal, 2, '.', '') !== number_format($sourceAmount, 2, '.', '')) {
            throw new RuntimeException(
                'Allocation lines must total the source amount exactly ('
                .number_format($lineTotal, 2, '.', '').' != '.number_format($sourceAmount, 2, '.', '').').'
            );
        }

        return DB::transaction(function () use ($header, $normalized, $sourceAmount, $userId) {
            $allocation = AcctCostAllocation::create([
                'business_id' => (int) ($header['business_id'] ?? 0),
                'name' => $header['name'],
                'allocation_date' => $header['allocation_date'],
                'source_type' => $header['source_type'] ?? null,
                'source_id' => $header['source_id'] ?? null,
                'source_amount' => $sourceAmount,
                'status' => 'posted',
                'created_by' => $userId,
                'notes' => $header['notes'] ?? null,
            ]);

            foreach ($normalized as $line) {
                AcctCostAllocationLine::create(array_merge($line, ['allocation_id' => $allocation->id]));
            }

            AccountingAuditService::record('cost_allocation.created', $allocation, null, $allocation->load('lines')->toArray(), null, (int) $allocation->business_id, $userId);

            return $allocation->fresh('lines');
        });
    }
}