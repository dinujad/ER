<?php

namespace App\Services\Accounting;

use App\Models\Accounting\FaAsset;
use App\Models\Accounting\FaAssetCategory;
use App\Models\Accounting\FaCapitalizationCandidate;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class PurchaseAssetCapitalizationService
{
    public static function syncCandidatesFromPurchase(Purchase $purchase): void
    {
        if (! Schema::hasTable('fa_capitalization_candidates')) {
            return;
        }

        $purchase->loadMissing('items');

        foreach ($purchase->items as $item) {
            if (! (bool) ($item->is_fixed_asset ?? false)) {
                continue;
            }

            $amount = AccountingService::normalizeMoney((float) ($item->line_total ?? 0));
            if ($amount <= 0) {
                continue;
            }

            $existing = FaCapitalizationCandidate::query()
                ->where('purchase_item_id', (int) $item->id)
                ->first();

            // Never reopen capitalized/cancelled candidates via purchase re-sync.
            if ($existing && in_array($existing->status, ['capitalized', 'cancelled'], true)) {
                continue;
            }

            FaCapitalizationCandidate::query()->updateOrCreate(
                ['purchase_item_id' => (int) $item->id],
                [
                    'business_id' => (int) ($purchase->business_id ?? 0),
                    'purchase_id' => (int) $purchase->id,
                    'fa_category_id' => $item->fa_category_id,
                    'amount' => $amount,
                    'status' => $existing?->status === 'approved' ? 'approved' : 'pending_review',
                    'gl_treatment' => $existing?->gl_treatment ?: 'clearing',
                    'clearing_event_key' => 'purchase:'.(int) $purchase->id.':confirmed',
                    'capitalize_event_key' => $existing?->capitalize_event_key
                        ?: 'fa:cap-from-po:pending:'.(int) $item->id,
                ]
            );
        }
    }

    public static function cancelOpenForPurchase(int $purchaseId): void
    {
        if (! Schema::hasTable('fa_capitalization_candidates')) {
            return;
        }

        FaCapitalizationCandidate::query()
            ->where('purchase_id', $purchaseId)
            ->whereIn('status', ['draft', 'pending_review', 'approved'])
            ->update(['status' => 'cancelled', 'updated_at' => now()]);
    }

    public static function approve(FaCapitalizationCandidate $candidate, ?int $userId = null): FaCapitalizationCandidate
    {
        if ($candidate->status !== 'pending_review') {
            throw new RuntimeException('Only pending review candidates can be approved.');
        }

        $candidate->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        AccountingAuditService::record(
            'fa_cap_candidate.approved',
            $candidate,
            null,
            $candidate->fresh()->toArray(),
            null,
            (int) $candidate->business_id,
            $userId
        );

        return $candidate->fresh();
    }

    public static function capitalize(
        FaCapitalizationCandidate $candidate,
        string $postingDate,
        ?int $userId = null
    ): FaAsset {
        if ($candidate->status === 'capitalized') {
            throw new RuntimeException('Candidate already capitalized.');
        }
        if (! in_array($candidate->status, ['approved', 'pending_review'], true)) {
            throw new RuntimeException('Candidate cannot be capitalized in status '.$candidate->status.'.');
        }

        return DB::transaction(function () use ($candidate, $postingDate, $userId) {
            $locked = FaCapitalizationCandidate::query()->lockForUpdate()->find($candidate->id);
            if (! $locked || $locked->status === 'capitalized') {
                throw new RuntimeException('Candidate already capitalized.');
            }

            $item = PurchaseItem::query()->find($locked->purchase_item_id);
            $purchase = Purchase::query()->with('supplier')->find($locked->purchase_id);
            if (! $item || ! $purchase) {
                throw new RuntimeException('Purchase line not found for candidate.');
            }

            $categoryId = (int) ($locked->fa_category_id ?: $item->fa_category_id);
            if ($categoryId <= 0) {
                $categoryId = (int) FaAssetCategory::query()->where('active', 1)->value('id');
            }
            if ($categoryId <= 0) {
                throw new RuntimeException('FA category is required to capitalize.');
            }

            $amount = AccountingService::normalizeMoney((float) $locked->amount);
            $assetName = $item->material?->name ?? ('PO asset line #'.$item->id);
            $reference = $purchase->purchase_no ?? $purchase->reference_no ?? 'PO-'.$purchase->id;

            $asset = FixedAssetService::createDraft([
                'business_id' => (int) $locked->business_id,
                'category_id' => $categoryId,
                'name' => $assetName,
                'asset_cost' => $amount,
                'purchase_date' => $purchase->date,
                'acquisition_date' => $purchase->date,
                'in_service_date' => $item->fa_in_service_date ?? $postingDate,
                'salvage_value' => $item->fa_salvage_value,
                'useful_life_months' => $item->fa_useful_life_months,
                'depreciation_method' => $item->fa_depreciation_method,
                'cost_center_id' => $item->fa_cost_center_id,
                'supplier_id' => $purchase->supplier_id,
                'purchase_reference' => $reference,
                'purchase_id' => (int) $purchase->id,
                'purchase_item_id' => (int) $item->id,
            ], $userId);

            $assetAccountId = (int) AccountingService::accountId(AccountingService::GL_FIXED_ASSETS);
            $glTreatment = $locked->gl_treatment ?: 'clearing';
            $capEventKey = 'fa:cap-from-po:'.(int) $locked->id;
            $txn = null;

            if ($glTreatment === 'already_fa') {
                $asset->update([
                    'status' => 'active',
                    'capitalized_at' => now(),
                    'approved_by' => $userId,
                    'in_service_date' => $asset->in_service_date ?: $postingDate,
                ]);
            } elseif ($glTreatment === 'reclass_from_rm') {
                $rmId = (int) AccountingService::accountId(AccountingService::GL_RAW_MATERIALS);
                if (! $assetAccountId || ! $rmId) {
                    throw new RuntimeException('Required GL accounts missing for reclass.');
                }
                $txn = AccountingService::postOnce(
                    $capEventKey,
                    'fa_capitalization_candidate',
                    (int) $locked->id,
                    $assetAccountId,
                    $rmId,
                    $amount,
                    $postingDate,
                    'Capitalize from PO '.$reference,
                    $asset->asset_code,
                    'fixed_asset_capitalization',
                    ['source_type' => 'fa_capitalization_candidate', 'source_id' => $locked->id]
                );
                $asset->update([
                    'status' => 'active',
                    'capitalization_txn' => $txn,
                    'capitalized_at' => now(),
                    'approved_by' => $userId,
                    'in_service_date' => $asset->in_service_date ?: $postingDate,
                    'net_book_value' => $amount,
                ]);
                DepreciationEngine::generateSchedule($asset->fresh());
            } else {
                $clearingId = (int) AccountingService::accountId(AccountingService::GL_FA_CLEARING);
                if (! $assetAccountId || ! $clearingId) {
                    throw new RuntimeException('Required GL accounts missing for clearing capitalization.');
                }
                $txn = AccountingService::postOnce(
                    $capEventKey,
                    'fa_capitalization_candidate',
                    (int) $locked->id,
                    $assetAccountId,
                    $clearingId,
                    $amount,
                    $postingDate,
                    'Capitalize from PO clearing '.$reference,
                    $asset->asset_code,
                    'fixed_asset_capitalization',
                    ['source_type' => 'fa_capitalization_candidate', 'source_id' => $locked->id]
                );
                $asset->update([
                    'status' => 'active',
                    'capitalization_txn' => $txn,
                    'capitalized_at' => now(),
                    'approved_by' => $userId,
                    'in_service_date' => $asset->in_service_date ?: $postingDate,
                    'net_book_value' => $amount,
                ]);
                DepreciationEngine::generateSchedule($asset->fresh());
            }

            $locked->update([
                'status' => 'capitalized',
                'asset_id' => $asset->id,
                'capitalize_event_key' => $capEventKey,
                'capitalization_txn' => $txn,
                'capitalized_at' => now(),
                'approved_by' => $userId ?: $locked->approved_by,
                'approved_at' => $locked->approved_at ?? now(),
            ]);

            FixedAssetService::recordEvent(
                $asset->fresh(),
                'capitalized_from_po',
                'Capitalized from purchase order',
                null,
                $asset->fresh()->toArray(),
                $userId
            );

            AccountingAuditService::record(
                'fa_cap_candidate.capitalized',
                $locked,
                null,
                $locked->fresh()->toArray(),
                null,
                (int) $locked->business_id,
                $userId
            );

            return $asset->fresh('category');
        });
    }
}