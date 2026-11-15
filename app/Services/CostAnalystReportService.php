<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Cost Analyst — every cost/expense line from across the ERP, filterable by date range, with net profit.
 */
class CostAnalystReportService
{
    public function __construct(
        private readonly CostReportService $costReport,
        private readonly ExpenseLedgerService $ledgerService
    ) {}

    /** @return array<string, mixed> */
    public function build(string $from, string $to, Request $request): array
    {
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $sourceFilter = (string) $request->get('source', 'all');
        $topicFilter = (string) $request->get('topic', 'all');

        $ledger = $this->ledgerService->collect($from, $to, $sourceFilter);

        if ($topicFilter !== 'all' && $topicFilter !== '') {
            $ledger = $ledger->filter(fn (array $row) => ($row['topic'] ?? '') === $topicFilter)->values();
        }

        $sourceTotals = $this->totalsByKey($ledger, 'source_key', 'source');
        $topicTotals = $ledger
            ->groupBy('topic')
            ->map(function (Collection $group, string $topic) {
                $first = $group->first();

                return [
                    'topic' => $topic,
                    'label' => (string) ($first['topic_label'] ?? $topic),
                    'count' => $group->count(),
                    'total' => round((float) $group->sum('amount'), 2),
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->all();

        $ledgerTotal = round((float) $ledger->sum('amount'), 2);
        $costSheet = $this->costReport->build($from, $to, $request);

        return [
            'filters' => [
                'from' => $from,
                'to' => $to,
                'source' => $sourceFilter,
                'topic' => $topicFilter,
            ],
            'revenue' => $costSheet['revenue'],
            'summary' => array_merge($costSheet['summary'] ?? [], [
                'ledger_total' => $ledgerTotal,
                'ledger_count' => $ledger->count(),
                'accounting_linked_count' => $ledger->where('accounting_linked', true)->count(),
            ]),
            'topics' => $costSheet['topics'],
            'simple_average' => $costSheet['simple_average'] ?? [],
            'source_totals' => $sourceTotals,
            'topic_totals' => $topicTotals,
            'ledger' => $ledger->sortByDesc('date')->values()->all(),
            'source_options' => ExpenseLedgerService::SOURCE_OPTIONS,
            'topic_options' => collect($this->costReport->topics())
                ->mapWithKeys(fn (array $meta, string $key) => [$key => $meta['label']])
                ->all(),
            'notes' => array_merge($costSheet['notes'] ?? [], [
                'Cost Analyst lists every cost line recorded in the system for the selected period (not only ERP Expenses).',
                'Use Source filter to narrow to one module; Net Profit uses the full cost sheet total (same as Cost Report).',
                'Accounting column shows whether a journal entry was posted in the Accounting module.',
            ]),
        ];
    }

    /** @param Collection<int, array<string, mixed>> $ledger */
    private function totalsByKey(Collection $ledger, string $groupKey, string $labelKey): array
    {
        return $ledger
            ->groupBy($groupKey)
            ->map(fn (Collection $group) => [
                'label' => (string) ($group->first()[$labelKey] ?? ''),
                'count' => $group->count(),
                'total' => round((float) $group->sum('amount'), 2),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();
    }
}
