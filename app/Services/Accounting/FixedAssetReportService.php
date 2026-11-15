<?php

namespace App\Services\Accounting;

use App\Models\Accounting\FaAsset;
use App\Models\Accounting\FaDepreciationSchedule;
use App\Models\Accounting\FaDisposal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixedAssetReportService
{
    /**
     * @return array<string, mixed>
     */
    public static function register(int $businessId = 0, ?string $status = null): array
    {
        if (! Schema::hasTable('fa_assets')) {
            return ['rows' => [], 'totals' => ['cost' => '0.00', 'accumulated' => '0.00', 'nbv' => '0.00']];
        }

        $q = FaAsset::query()->with('category')->where('business_id', $businessId);
        if ($status) {
            $q->where('status', $status);
        }

        $rows = [];
        $cost = $accum = $nbv = 0.0;
        foreach ($q->orderBy('asset_code')->get() as $asset) {
            $lineCost = round((float) $asset->asset_cost + (float) $asset->capitalized_costs, 2);
            $lineAccum = round((float) $asset->accumulated_depreciation, 2);
            $lineNbv = round((float) $asset->net_book_value, 2);
            $cost += $lineCost;
            $accum += $lineAccum;
            $nbv += $lineNbv;
            $rows[] = [
                'asset_code' => $asset->asset_code,
                'name' => $asset->name,
                'category' => $asset->category?->name,
                'status' => $asset->status,
                'cost' => number_format($lineCost, 2, '.', ''),
                'accumulated' => number_format($lineAccum, 2, '.', ''),
                'nbv' => number_format($lineNbv, 2, '.', ''),
            ];
        }

        return [
            'rows' => $rows,
            'totals' => [
                'cost' => number_format($cost, 2, '.', ''),
                'accumulated' => number_format($accum, 2, '.', ''),
                'nbv' => number_format($nbv, 2, '.', ''),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function depreciationSchedule(int $assetId): array
    {
        $asset = FaAsset::with('category')->findOrFail($assetId);
        $rows = FaDepreciationSchedule::query()
            ->where('asset_id', $assetId)
            ->orderBy('period_year')
            ->orderBy('period_month')
            ->get()
            ->map(fn ($s) => [
                'period' => sprintf('%04d-%02d', $s->period_year, $s->period_month),
                'scheduled' => number_format((float) $s->scheduled_amount, 2, '.', ''),
                'status' => $s->status,
                'txn' => $s->transaction_number,
            ])
            ->all();

        return ['asset' => $asset, 'rows' => $rows];
    }

    /**
     * @return array<string, mixed>
     */
    public static function history(int $assetId): array
    {
        $asset = FaAsset::with(['events' => fn ($q) => $q->orderByDesc('event_date')])->findOrFail($assetId);

        return [
            'asset' => $asset,
            'events' => $asset->events->map(fn ($e) => [
                'date' => $e->event_date?->format('Y-m-d'),
                'type' => $e->event_type,
                'description' => $e->description,
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function disposalReport(?string $from = null, ?string $to = null): array
    {
        if (! Schema::hasTable('fa_disposals')) {
            return ['rows' => [], 'totals' => ['proceeds' => '0.00', 'gain' => '0.00', 'loss' => '0.00']];
        }

        $q = FaDisposal::query()->with('asset');
        if ($from) {
            $q->whereDate('disposal_date', '>=', $from);
        }
        if ($to) {
            $q->whereDate('disposal_date', '<=', $to);
        }

        $proceeds = $gain = $loss = 0.0;
        $rows = [];
        foreach ($q->orderByDesc('disposal_date')->get() as $d) {
            $p = round((float) ($d->proceeds ?? 0), 2);
            $gl = round((float) ($d->gain_loss ?? 0), 2);
            $g = $gl > 0 ? $gl : 0.0;
            $l = $gl < 0 ? abs($gl) : 0.0;
            $proceeds += $p;
            $gain += $g;
            $loss += $l;
            $rows[] = [
                'date' => $d->disposal_date?->format('Y-m-d'),
                'asset' => $d->asset?->asset_code,
                'proceeds' => number_format($p, 2, '.', ''),
                'gain' => number_format($g, 2, '.', ''),
                'loss' => number_format($l, 2, '.', ''),
            ];
        }

        return [
            'rows' => $rows,
            'totals' => [
                'proceeds' => number_format($proceeds, 2, '.', ''),
                'gain' => number_format($gain, 2, '.', ''),
                'loss' => number_format($loss, 2, '.', ''),
            ],
        ];
    }

    public static function sanitizeSpreadsheetCell(?string $value): string
    {
        $value = (string) $value;
        if ($value !== '' && preg_match('/^[=+\-@]/', $value)) {
            return "'".$value;
        }

        return $value;
    }
}