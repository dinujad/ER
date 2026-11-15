<?php

namespace App\Services\Accounting;

use App\Models\Accounting\FaAsset;
use App\Models\Accounting\FaAssetCategory;
use App\Models\Accounting\FaAssetEvent;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FixedAssetService
{
    public static function nextAssetCode(int $businessId = 0): string
    {
        $year = date('Y');
        $prefix = 'FA-'.$year.'-';
        $last = FaAsset::query()
            ->where('business_id', $businessId)
            ->where('asset_code', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('asset_code');

        $seq = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return $prefix.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function createDraft(array $data, ?int $userId = null): FaAsset
    {
        $category = FaAssetCategory::findOrFail((int) $data['category_id']);
        $assetCost = AccountingService::normalizeMoney($data['asset_cost'] ?? 0);

        return DB::transaction(function () use ($data, $category, $assetCost, $userId) {
            $asset = FaAsset::create([
                'business_id' => (int) ($data['business_id'] ?? 0),
                'category_id' => $category->id,
                'asset_code' => $data['asset_code'] ?? self::nextAssetCode((int) ($data['business_id'] ?? 0)),
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'purchase_date' => $data['purchase_date'] ?? null,
                'acquisition_date' => $data['acquisition_date'] ?? null,
                'in_service_date' => $data['in_service_date'] ?? null,
                'asset_cost' => $assetCost,
                'capitalized_costs' => round((float) ($data['capitalized_costs'] ?? 0), 2),
                'salvage_value' => round(max(0, (float) ($data['salvage_value'] ?? $category->default_salvage_value ?? 0)), 2),
                'useful_life_months' => (int) ($data['useful_life_months'] ?? $category->default_useful_life_months ?? 60),
                'depreciation_method' => $data['depreciation_method'] ?? $category->default_depreciation_method ?? 'straight_line',
                'accumulated_depreciation' => 0,
                'net_book_value' => $assetCost + round((float) ($data['capitalized_costs'] ?? 0), 2),
                'status' => 'draft',
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'created_by' => $userId,
            ]);

            self::recordEvent($asset, 'created', 'Asset draft created', null, $asset->toArray(), $userId);
            AccountingAuditService::record('fa_asset.created', $asset, null, $asset->toArray(), null, (int) $asset->business_id, $userId);

            return $asset->fresh('category');
        });
    }

    public static function capitalize(
        FaAsset $asset,
        string $postingDate,
        ?string $creditGl = null,
        ?int $userId = null
    ): FaAsset {
        if (! in_array($asset->status, ['draft', 'approved'], true)) {
            throw new RuntimeException('Only draft or approved assets can be capitalized.');
        }

        $amount = $asset->totalCapitalizedCost();
        if ($amount <= 0) {
            throw new RuntimeException('Capitalized amount must be greater than zero.');
        }

        $category = $asset->category;
        $assetAccountId = (int) ($category->asset_account_id ?: AccountingService::accountId(AccountingService::GL_FIXED_ASSETS));
        $creditAccountId = (int) ($creditGl
            ? AccountingService::accountId($creditGl)
            : AccountingService::accountId(AccountingService::GL_ACCOUNTS_PAY));

        if (! $assetAccountId || ! $creditAccountId) {
            throw new RuntimeException('Required GL accounts are missing for capitalization.');
        }

        return DB::transaction(function () use ($asset, $postingDate, $amount, $assetAccountId, $creditAccountId, $userId) {
            $before = $asset->toArray();

            $txn = AccountingService::postOnce(
                'fa:capitalize:'.$asset->id,
                'fa_asset',
                (int) $asset->id,
                $assetAccountId,
                $creditAccountId,
                $amount,
                $postingDate,
                'Capitalize '.$asset->name,
                $asset->asset_code,
                'fixed_asset_capitalization',
                [
                    'source_type' => 'fa_asset',
                    'source_id' => $asset->id,
                    'cost_center_id' => $asset->cost_center_id,
                ]
            );

            $asset->update([
                'status' => 'active',
                'capitalization_txn' => $txn,
                'capitalized_at' => now(),
                'approved_by' => $userId,
                'in_service_date' => $asset->in_service_date ?: $postingDate,
                'net_book_value' => $amount,
            ]);

            $asset = $asset->fresh('category');
            self::recordEvent($asset, 'capitalized', 'Asset capitalized', $before, $asset->toArray(), $userId);
            AccountingAuditService::record('fa_asset.capitalized', $asset, $before, $asset->toArray(), null, (int) $asset->business_id, $userId);

            DepreciationEngine::generateSchedule($asset);

            return $asset;
        });
    }

    public static function recordEvent(
        FaAsset $asset,
        string $type,
        string $description,
        ?array $before,
        ?array $after,
        ?int $userId
    ): FaAssetEvent {
        return FaAssetEvent::create([
            'asset_id' => $asset->id,
            'event_type' => $type,
            'event_date' => now()->toDateString(),
            'description' => $description,
            'before_state' => $before,
            'after_state' => $after,
            'user_id' => $userId,
        ]);
    }
}