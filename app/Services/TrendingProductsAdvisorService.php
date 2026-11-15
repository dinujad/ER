<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Trend-based pricing advice — always presented as E media AI agent recommendation.
 */
class TrendingProductsAdvisorService
{
    /** @param array<string, mixed> $stats */
    public function buildRecommendation(Product $product, array $stats, array $salesHistory): array
    {
        $growth = (float) ($stats['growth_percent'] ?? 0);
        $rank = (int) ($stats['rank'] ?? 99);
        $currentAvg = (float) ($stats['avg_unit_price'] ?? 0);
        $totalQty = (float) ($stats['total_qty'] ?? 0);
        $prevQty = (float) ($stats['prev_qty'] ?? 0);

        $logic = $this->ruleBasedPlan($growth, $rank, $currentAvg, $totalQty, $prevQty);
        $aiText = $this->optionalGeminiAdvice($product, $stats, $salesHistory, $logic);

        $body = $aiText ?? $logic['narrative'];

        return [
            'agent_brand' => config('trending_products.agent_brand', 'E media AI agent recommendation'),
            'source' => $aiText ? 'ai+rules' : 'rules',
            'trend_label' => $logic['trend_label'],
            'price_action' => $logic['price_action'],
            'suggested_increase_percent' => $logic['increase_percent'],
            'suggested_wholesale_price' => $logic['suggested_price'],
            'current_avg_price' => $currentAvg,
            'logic_summary' => $logic['summary'],
            'insights' => $logic['insights'],
            'recommendation' => $body,
        ];
    }

    /** @return array<string, mixed> */
    private function ruleBasedPlan(
        float $growth,
        int $rank,
        float $currentAvg,
        float $totalQty,
        float $prevQty
    ): array {
        $hot = (float) config('trending_products.hot_growth_percent', 25);
        $rising = (float) config('trending_products.rising_growth_percent', 10);
        $maxInc = (float) config('trending_products.max_price_increase_percent', 15);
        $defaultInc = (float) config('trending_products.default_price_increase_percent', 5);

        $increase = 0.0;
        $action = 'hold';
        $trendLabel = 'Stable';
        $insights = [];

        if ($totalQty <= 0) {
            return [
                'trend_label' => 'No sales',
                'price_action' => 'hold',
                'increase_percent' => 0,
                'suggested_price' => $currentAvg,
                'summary' => 'No sales in this period — keep price unchanged until demand appears.',
                'narrative' => "Period එකේ sales නැහැ. Price change කරන්න එපා — marketing / stock check කරන්න.",
                'insights' => [[
                    'level' => 'secondary',
                    'title' => 'No demand signal',
                    'body' => 'Sell volume 0 in selected dates.',
                ]],
            ];
        }

        if ($growth >= $hot || ($rank <= 3 && $growth >= $rising)) {
            $trendLabel = 'Hot trending';
            $action = 'increase';
            $increase = min($maxInc, $rank <= 3 ? 12 : 10);
        } elseif ($growth >= $rising || $rank <= 5) {
            $trendLabel = 'Rising';
            $action = 'increase';
            $increase = min(8, $defaultInc + 2);
        } elseif ($growth >= 3) {
            $trendLabel = 'Growing';
            $action = 'increase';
            $increase = $defaultInc;
        } elseif ($growth < -10) {
            $trendLabel = 'Cooling';
            $action = 'review';
            $increase = 0;
        }

        $suggested = $currentAvg > 0 && $increase > 0
            ? $this->roundPrice($currentAvg * (1 + $increase / 100))
            : $currentAvg;

        if ($action === 'increase') {
            $insights[] = [
                'level' => 'success',
                'title' => 'Trend supports higher price',
                'body' => sprintf(
                    'Sales qty %s%.1f%% vs previous period (%.0f → %.0f pcs). Rank #%d — demand strong.',
                    $growth >= 0 ? '+' : '',
                    $growth,
                    $prevQty,
                    $totalQty,
                    $rank
                ),
            ];
            $insights[] = [
                'level' => 'primary',
                'title' => 'Suggested wholesale price',
                'body' => sprintf(
                    'Current avg Rs %.2f → try Rs %.2f (+%.0f%%). Monitor next 2 weeks; if qty stays high, hold new price.',
                    $currentAvg,
                    $suggested,
                    $increase
                ),
            ];
        } elseif ($action === 'review') {
            $insights[] = [
                'level' => 'warning',
                'title' => 'Sales slowing',
                'body' => sprintf('Qty down %.1f%% vs previous period — avoid price increase; check competition or quality.', abs($growth)),
            ];
        } else {
            $insights[] = [
                'level' => 'info',
                'title' => 'Hold current price',
                'body' => 'Demand stable — no strong signal to raise price yet.',
            ];
        }

        $summary = $action === 'increase'
            ? sprintf('%s · suggest +%.0f%% wholesale (Rs %.2f → Rs %.2f)', $trendLabel, $increase, $currentAvg, $suggested)
            : sprintf('%s · keep price at Rs %.2f', $trendLabel, $currentAvg);

        $narrative = $action === 'increase'
            ? sprintf(
                "මේ product එක trend වෙනවා (%s, rank #%d). Logic අනුව wholesale price Rs %.2f → Rs %.2f (+%.0f%%) try කරන්න පුළුවන්. Demand තියෙන නිසා margin improve වෙනවා.",
                $trendLabel,
                $rank,
                $currentAvg,
                $suggested,
                $increase
            )
            : sprintf(
                'Trend: %s. Price Rs %.2f hold කරන්න. Growth %.1f%% — sharp increase recommend නැහැ.',
                $trendLabel,
                $currentAvg,
                $growth
            );

        return [
            'trend_label' => $trendLabel,
            'price_action' => $action,
            'increase_percent' => $increase,
            'suggested_price' => $suggested,
            'summary' => $summary,
            'narrative' => $narrative,
            'insights' => $insights,
        ];
    }

    private function roundPrice(float $price): float
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

    /** @param array<string, mixed> $stats */
    /** @param array<string, mixed> $logic */
    private function optionalGeminiAdvice(Product $product, array $stats, array $salesHistory, array $logic): ?string
    {
        $apiKey = env('GEMINI_API_KEY') ?: env('GOOGLE_API_KEY');
        if (!$apiKey) {
            return null;
        }

        try {
            $model = config('trending_products.gemini_model', 'gemini-2.0-flash');
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            $prompt = "You are the E media AI agent for a Sri Lankan garment factory ERP. "
                . "Give concise Sinhala/English pricing advice (max 180 words).\n\n"
                . "Product: {$product->name} (SKU {$product->sku})\n"
                . 'Period qty sold: ' . ($stats['total_qty'] ?? 0) . "\n"
                . 'Growth vs prev period: ' . ($stats['growth_percent'] ?? 0) . "%\n"
                . 'Rank: #' . ($stats['rank'] ?? '?') . "\n"
                . 'Avg sell price: Rs ' . ($stats['avg_unit_price'] ?? 0) . "\n"
                . 'Rule suggestion: ' . ($logic['summary'] ?? '') . "\n"
                . 'Top variation: ' . ($stats['top_variation_name'] ?? 'N/A') . "\n"
                . "Should they increase wholesale price because it is trending? Give actionable steps.";

            $response = Http::timeout(25)->post($url, [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['temperature' => 0.35, 'maxOutputTokens' => 512],
            ]);

            if (!$response->successful()) {
                return null;
            }

            $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

            return is_string($text) && trim($text) !== '' ? trim($text) : null;
        } catch (\Throwable $e) {
            Log::warning('TrendingProductsAdvisor Gemini failed: ' . $e->getMessage());

            return null;
        }
    }
}
