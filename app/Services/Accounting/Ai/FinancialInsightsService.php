<?php

namespace App\Services\Accounting\Ai;

use App\Services\Accounting\FinancialStatementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Deterministic insights with optional AI explanation (never fabricates amounts).
 */
class FinancialInsightsService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function generate(int $businessId, ?string $from = null, ?string $to = null): array
    {
        $from = $from ?: date('Y-m-01');
        $to = $to ?: date('Y-m-d');
        $prevFrom = date('Y-m-d', strtotime($from.' -1 month'));
        $prevTo = date('Y-m-d', strtotime($to.' -1 month'));

        $cur = FinancialStatementService::profitAndLoss($from, $to);
        $prev = FinancialStatementService::profitAndLoss($prevFrom, $prevTo);

        $insights = [];
        $insights[] = $this->build(
            $businessId,
            'revenue_change',
            'Revenue change',
            $from,
            $to,
            'income',
            'prior_period',
            (float) ($cur['income'] ?? 0),
            (float) ($cur['income'] ?? 0) - (float) ($prev['income'] ?? 0),
            $this->pct((float) ($cur['income'] ?? 0), (float) ($prev['income'] ?? 0)),
            sprintf('Revenue (actual) was LKR %s in %s → %s versus LKR %s in the prior comparable period.', number_format((float) ($cur['income'] ?? 0), 2), $from, $to, number_format((float) ($prev['income'] ?? 0), 2)),
            'Possible drivers include sales volume, pricing, or product mix — not confirmed by this metric alone.',
            'Open comparative P&L for account-level drill-down.',
            url('accounting/phase4/statements/profit-and-loss')
        );

        $insights[] = $this->build(
            $businessId,
            'expense_increase',
            'Expense change',
            $from,
            $to,
            'expense',
            'prior_period',
            (float) ($cur['expense'] ?? 0),
            (float) ($cur['expense'] ?? 0) - (float) ($prev['expense'] ?? 0),
            $this->pct((float) ($cur['expense'] ?? 0), (float) ($prev['expense'] ?? 0)),
            sprintf('Expenses (actual) were LKR %s vs prior LKR %s.', number_format((float) ($cur['expense'] ?? 0), 2), number_format((float) ($prev['expense'] ?? 0), 2)),
            'Increase may correlate with payroll, purchases, or utilities; causality not established.',
            'Review expense breakdown and budget variance.',
            url('accounting/phase4/statements/profit-and-loss')
        );

        $insights[] = $this->build(
            $businessId,
            'profit_margin',
            'Profit trend',
            $from,
            $to,
            'net',
            'prior_period',
            (float) ($cur['net'] ?? 0),
            (float) ($cur['net'] ?? 0) - (float) ($prev['net'] ?? 0),
            $this->pct((float) ($cur['net'] ?? 0), (float) ($prev['net'] ?? 0)),
            sprintf('Net profit (actual) LKR %s (prior LKR %s).', number_format((float) ($cur['net'] ?? 0), 2), number_format((float) ($prev['net'] ?? 0), 2)),
            null,
            'Compare cost centers and product margins if available.',
            url('accounting/dashboard')
        );

        $persisted = [];
        foreach ($insights as $insight) {
            $persisted[] = $this->persist($insight);
        }

        return $persisted;
    }

    /**
     * @param  array<string, mixed>  $insight
     * @return array<string, mixed>
     */
    protected function persist(array $insight): array
    {
        if (! Schema::hasTable('acct_financial_insights')) {
            return $insight;
        }
        $id = (int) DB::table('acct_financial_insights')->insertGetId(array_merge($insight, [
            'generated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));
        $insight['id'] = $id;

        return $insight;
    }

    /**
     * @return array<string, mixed>
     */
    protected function build(
        int $businessId,
        string $category,
        string $title,
        string $from,
        string $to,
        string $metric,
        string $comparison,
        float $actual,
        float $change,
        ?float $changePct,
        string $fact,
        ?string $explanation,
        ?string $action,
        ?string $url
    ): array {
        return [
            'business_id' => $businessId,
            'category' => $category,
            'title' => $title,
            'period_from' => $from,
            'period_to' => $to,
            'source_metric' => $metric,
            'comparison_basis' => $comparison,
            'actual_amount' => round($actual, 2),
            'change_amount' => round($change, 2),
            'change_pct' => $changePct,
            'observed_fact' => $fact,
            'possible_explanation' => $explanation,
            'suggested_action' => $action,
            'drilldown_url' => $url,
            'certainty' => 'observed',
        ];
    }

    protected function pct(float $current, float $previous): ?float
    {
        if (abs($previous) < 0.0001) {
            return null;
        }

        return round((($current - $previous) / abs($previous)) * 100, 4);
    }
}
