<?php

namespace App\Services\Accounting;

use App\Models\Accounting\FaAsset;
use App\Models\Accounting\FaDisposal;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AssetDisposalService
{
    public static function dispose(
        FaAsset $asset,
        string $disposalType,
        string $disposalDate,
        float $proceeds = 0,
        ?int $userId = null
    ): FaDisposal {
        if (! in_array($asset->status, ['active', 'fully_depreciated'], true)) {
            throw new RuntimeException('Only active assets can be disposed.');
        }

        $proceeds = round(max(0, $proceeds), 2);
        $cost = $asset->totalCapitalizedCost();
        $accum = round((float) $asset->accumulated_depreciation, 2);
        $nbv = round(max(0, $cost - $accum), 2);
        $gainLoss = round($proceeds - $nbv, 2);

        $category = $asset->category;
        $assetAccountId = (int) ($category->asset_account_id ?: AccountingService::accountId(AccountingService::GL_FIXED_ASSETS));
        $accumAccountId = (int) ($category->accumulated_depreciation_account_id ?: AccountingService::accountId(AccountingService::GL_ACCUMULATED_DEPRECIATION));
        $bankId = AccountingService::accountId(AccountingService::GL_BANK);
        $gainId = (int) ($category->disposal_gain_account_id ?: AccountingService::accountId(AccountingService::GL_GAIN_ON_DISPOSAL));
        $lossId = (int) ($category->disposal_loss_account_id ?: AccountingService::accountId(AccountingService::GL_LOSS_ON_DISPOSAL));

        if (! $assetAccountId || ! $accumAccountId) {
            throw new RuntimeException('Disposal GL accounts are not configured.');
        }

        return DB::transaction(function () use (
            $asset, $disposalType, $disposalDate, $proceeds, $cost, $accum, $nbv, $gainLoss,
            $accumAccountId, $assetAccountId, $bankId, $gainId, $lossId, $userId
        ) {
            $lines = [];
            if ($accum > 0) {
                $lines[] = ['account_id' => $accumAccountId, 'debit' => $accum, 'credit' => 0];
            }
            if ($proceeds > 0 && $bankId) {
                $lines[] = ['account_id' => $bankId, 'debit' => $proceeds, 'credit' => 0];
            }
            if ($gainLoss < 0 && $lossId) {
                $lines[] = ['account_id' => $lossId, 'debit' => abs($gainLoss), 'credit' => 0];
            }
            $lines[] = ['account_id' => $assetAccountId, 'debit' => 0, 'credit' => $cost];
            if ($gainLoss > 0 && $gainId) {
                $lines[] = ['account_id' => $gainId, 'debit' => 0, 'credit' => $gainLoss];
            }

            $txn = AccountingService::postMultiLine(
                $lines,
                $disposalDate,
                'Disposal of '.$asset->name,
                $asset->asset_code,
                'asset_disposal',
                [
                    'source_type' => 'fa_disposal',
                    'source_id' => $asset->id,
                    'cost_center_id' => $asset->cost_center_id,
                ],
                'fa:dispose:'.$asset->id
            );

            $disposal = FaDisposal::create([
                'asset_id' => $asset->id,
                'disposal_date' => $disposalDate,
                'disposal_type' => $disposalType,
                'proceeds' => $proceeds,
                'cost_at_disposal' => $cost,
                'accumulated_at_disposal' => $accum,
                'gain_loss' => $gainLoss,
                'status' => 'posted',
                'transaction_number' => $txn,
                'created_by' => $userId,
            ]);

            $before = $asset->toArray();
            $asset->update([
                'status' => 'disposed',
                'disposal_date' => $disposalDate,
                'net_book_value' => 0,
            ]);

            FixedAssetService::recordEvent($asset, 'disposed', 'Asset disposed', $before, $asset->fresh()->toArray(), $userId);
            AccountingAuditService::record('fa_asset.disposed', $disposal, $before, $asset->fresh()->toArray(), null, (int) $asset->business_id, $userId);

            return $disposal;
        });
    }
}