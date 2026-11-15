<?php

namespace App\Services;

use App\Models\ManufacturingJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pricing suggestions + cost driver analysis. Uses Gemini when API key is set; otherwise rule-based advisor.
 */
class CostAnalystAdvisorService
{
    /** @return array{price: float, profit_per_unit: float, markup_amount: float} */
    public function suggestedSellPrice(float $costPerUnit, float $marginPercent): array
    {
        if ($costPerUnit <= 0) {
            return ['price' => 0.0, 'profit_per_unit' => 0.0, 'markup_amount' => 0.0];
        }

        $raw = $costPerUnit * (1 + ($marginPercent / 100));
        $price = $this->roundRetailPrice($raw);
        $markup = round($price - $costPerUnit, 2);

        return [
            'price' => $price,
            'profit_per_unit' => round($price - $costPerUnit, 2),
            'markup_amount' => $markup,
        ];
    }

    /**
     * @param array<string, mixed> $report
     * @param array{labels: array, quantities: array, avg_prices: array} $salesHistory
     * @return array<string, mixed>
     */
    public function buildAdvisory(
        ManufacturingJob $job,
        array $report,
        float $marginPercent,
        array $salesHistory
    ): array {
        $actual = $report['actual'] ?? [];
        $planned = $report['planned'] ?? [];
        $variance = $report['variance'] ?? [];
        $costPerUnit = (float) ($actual['per_unit'] ?? 0);
        $suggested = $this->suggestedSellPrice($costPerUnit, $marginPercent);
        $ub = $actual['unit_breakdown'] ?? [];

        $drivers = $this->costDrivers($ub, $actual, $planned);
        $insights = $this->ruleBasedInsights($report, $marginPercent, $salesHistory, $suggested);
        $aiPlan = $this->optionalAiPlan($job, $report, $marginPercent, $drivers, $salesHistory, $suggested);

        return [
            'source' => $aiPlan ? 'ai+rules' : 'rules',
            'margin_percent' => $marginPercent,
            'suggested_sell_price' => $suggested['price'],
            'suggested_profit_per_unit' => $suggested['profit_per_unit'],
            'cost_drivers' => $drivers,
            'insights' => $insights,
            'ai_plan' => $aiPlan,
            'summary' => $this->buildSummary($costPerUnit, $suggested, $drivers, $variance),
        ];
    }

    /** @param array<string, float> $ub */
    private function costDrivers(array $ub, array $actual, array $planned): array
    {
        $total = max(0.01, (float) ($ub['total'] ?? $actual['per_unit'] ?? 1));
        $items = [
            ['key' => 'fabric', 'label' => 'Fabric / cloth', 'amount' => (float) ($ub['fabric'] ?? 0), 'hint' => 'Material purchase & issue price'],
            ['key' => 'accessory', 'label' => 'Trims & buttons', 'amount' => (float) ($ub['accessory'] ?? 0), 'hint' => 'Thread, buttons, zippers'],
            ['key' => 'sewing', 'label' => 'Sewing / labour', 'amount' => (float) ($ub['sewing'] ?? 0), 'hint' => 'Line wages, piece rate'],
            ['key' => 'overhead', 'label' => 'Overhead (electricity, rent…)', 'amount' => (float) ($ub['overhead'] ?? 0), 'hint' => 'Monthly bills ÷ finished pieces'],
        ];

        return collect($items)
            ->map(fn ($row) => array_merge($row, [
                'percent' => round(($row['amount'] / $total) * 100, 1),
            ]))
            ->sortByDesc('percent')
            ->values()
            ->all();
    }

    /** @return array<int, array{level: string, title: string, body: string}> */
    private function ruleBasedInsights(
        array $report,
        float $marginPercent,
        array $salesHistory,
        array $suggested
    ): array {
        $insights = [];
        $actual = $report['actual'] ?? [];
        $planned = $report['planned'] ?? [];
        $variance = $report['variance'] ?? [];
        $cost = (float) ($actual['per_unit'] ?? 0);

        if ($cost <= 0) {
            $insights[] = [
                'level' => 'warning',
                'title' => 'Cost not ready',
                'body' => 'Packing output or material issues are missing — complete QC/packing to calculate per-piece cost.',
            ];
            return $insights;
        }

        if ($planned['has_recipe'] ?? false) {
            $vPct = $variance['percent'] ?? null;
            if ($vPct !== null && abs($vPct) > 10) {
                $insights[] = [
                    'level' => $vPct > 0 ? 'danger' : 'success',
                    'title' => 'Recipe variance ' . ($vPct > 0 ? 'over' : 'under') . ' plan',
                    'body' => sprintf(
                        'Actual Rs %.2f/pc vs recipe plan Rs %.2f/pc (%s%.1f%%). Review fabric waste, sewing extras, or overhead for this month.',
                        $cost,
                        $planned['per_unit'] ?? 0,
                        $vPct > 0 ? '+' : '',
                        $vPct
                    ),
                ];
            }
        }

        $ub = $actual['unit_breakdown'] ?? [];
        if (($ub['overhead'] ?? 0) > 0 && $cost > 0 && ($ub['overhead'] / $cost) > 0.15) {
            $insights[] = [
                'level' => 'info',
                'title' => 'Overhead is a large share',
                'body' => 'Electricity, water, rent and other monthly expenses add Rs '
                    . number_format($ub['overhead'], 2)
                    . '/pc. Reduce utility bills or increase finished output to lower overhead per garment.',
            ];
        }

        if (($ub['fabric'] ?? 0) > 0 && $cost > 0 && ($ub['fabric'] / $cost) > 0.55) {
            $insights[] = [
                'level' => 'info',
                'title' => 'Fabric dominates cost',
                'body' => 'Cloth is over 55% of unit cost — negotiate fabric price, reduce wastage, or check recipe quantities.',
            ];
        }

        $histPrices = $salesHistory['avg_prices'] ?? [];
        if ($histPrices !== []) {
            $lastAvg = (float) end($histPrices);
            if ($lastAvg > 0 && $suggested['price'] > $lastAvg * 1.2) {
                $insights[] = [
                    'level' => 'warning',
                    'title' => 'Suggested price above recent sales',
                    'body' => sprintf(
                        'Suggested Rs %.2f vs recent average sell Rs %.2f — market may resist a sharp increase; consider phased pricing.',
                        $suggested['price'],
                        $lastAvg
                    ),
                ];
            } elseif ($lastAvg > 0 && $suggested['price'] < $lastAvg * 0.85) {
                $insights[] = [
                    'level' => 'success',
                    'title' => 'Room to match market price',
                    'body' => sprintf(
                        'You can price near Rs %.2f (recent avg) and still keep margin above %.0f%%.',
                        $lastAvg,
                        $marginPercent
                    ),
                ];
            }
        }

        $insights[] = [
            'level' => 'primary',
            'title' => 'Recommended sell price',
            'body' => sprintf(
                'At %.0f%% margin on Rs %.2f cost → sell at Rs %.2f (profit Rs %.2f/pc). Adjust margin slider to see alternatives.',
                $marginPercent,
                $cost,
                $suggested['price'],
                $suggested['profit_per_unit']
            ),
        ];

        return $insights;
    }

    /** @param array<int, array<string, mixed>> $drivers */
    private function buildSummary(float $cost, array $suggested, array $drivers, array $variance): string
    {
        $top = $drivers[0]['label'] ?? 'Production';
        $topPct = $drivers[0]['percent'] ?? 0;

        return sprintf(
            'Unit cost Rs %.2f — largest driver: %s (%.0f%%). Suggested sell Rs %.2f. Variance vs recipe: Rs %.2f/pc.',
            $cost,
            $top,
            $topPct,
            $suggested['price'],
            $variance['per_unit'] ?? 0
        );
    }

    private function roundRetailPrice(float $price): float
    {
        if ($price <= 0) {
            return 0.0;
        }
        if ($price < 500) {
            return round(ceil($price / 10) * 10 - 1, 0) ?: round($price, 0);
        }
        if ($price < 2000) {
            return round(ceil($price / 50) * 50 - 1, 0);
        }

        return round(ceil($price / 100) * 100 - 1, 0);
    }

    /** @return string|null */
    private function optionalAiPlan(
        ManufacturingJob $job,
        array $report,
        float $marginPercent,
        array $drivers,
        array $salesHistory,
        array $suggested
    ): ?string {
        $apiKey = env('GEMINI_API_KEY') ?: env('GOOGLE_API_KEY');
        if (!$apiKey) {
            return null;
        }

        try {
            $actual = $report['actual'] ?? [];
            $prompt = $this->buildAiPrompt($job, $actual, $marginPercent, $drivers, $salesHistory, $suggested);
            $model = config('cost_analyst.gemini_model', 'gemini-2.0-flash');
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            $response = Http::timeout(25)->post($url, [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['temperature' => 0.4, 'maxOutputTokens' => 1024],
            ]);

            if (!$response->successful()) {
                Log::warning('CostAnalyst Gemini failed', ['status' => $response->status()]);
                return null;
            }

            $text = $response->json('candidates.0.content.parts.0.text');

            return is_string($text) && trim($text) !== '' ? trim($text) : null;
        } catch (\Throwable $e) {
            Log::warning('CostAnalyst AI error: ' . $e->getMessage());
            return null;
        }
    }

    /** @param array<string, mixed> $actual */
    private function buildAiPrompt(
        ManufacturingJob $job,
        array $actual,
        float $marginPercent,
        array $drivers,
        array $salesHistory,
        array $suggested
    ): string {
        $productName = $job->product?->name ?? 'Garment';
        $driverText = collect($drivers)->map(fn ($d) => "{$d['label']}: Rs {$d['amount']} ({$d['percent']}%)")->join('; ');
        $salesText = json_encode($salesHistory);

        return <<<PROMPT
You are a garment factory cost analyst for Sri Lanka (LKR). Be concise, practical, in simple English with short Sinhala phrases where helpful.

Job: {$job->job_number}
Product: {$productName}
Actual cost per piece (LKR): {$actual['per_unit']}
Cost breakdown per pc: {$driverText}
Target margin: {$marginPercent}%
Suggested sell price (LKR): {$suggested['price']}
Historical monthly sales (JSON): {$salesText}

Write a short plan (max 12 bullet points) covering:
1) Is this cloth/production cost healthy?
2) Why cost might be high (electricity, machines, salaries, overhead, fabric)
3) Recommended selling price range
4) Risks if priced too low or too high vs history
5) 2-3 actions to improve margin next batch

Do not invent numbers not given. Use LKR.
PROMPT;
    }
}
