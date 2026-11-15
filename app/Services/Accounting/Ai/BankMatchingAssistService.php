<?php

namespace App\Services\Accounting\Ai;

use App\Models\Accounting\BankStatementLine;
use App\Services\Accounting\BankMatchingService;
use Illuminate\Support\Facades\Schema;

/**
 * Enhances Phase 2 bank matching with explanations — recommendations only.
 */
class BankMatchingAssistService
{
    /**
     * @return array{suggestions:list<array>, unmatched_explanation:?string, discrepancy_insight:?string}
     */
    public function assist(BankStatementLine $line, int $chartOfAccountId, int $businessId): array
    {
        $suggestions = BankMatchingService::suggestions($line, $chartOfAccountId, 10);

        // Enrich with clearer explanations (deterministic)
        $enriched = array_map(function (array $s) {
            $s['explanation'] = sprintf(
                'Suggested match: %s #%d for LKR %s on %s (confidence %d%%). Reasons: %s. Requires authorized confirmation — no automatic GL posting.',
                $s['source_type'],
                $s['source_id'],
                $s['amount'],
                $s['date'] ?? 'n/a',
                $s['confidence'],
                $s['reason']
            );

            return $s;
        }, $suggestions);

        $unmatched = null;
        if ($enriched === []) {
            $amt = BankMatchingService::lineAmount($line);
            $unmatched = $amt <= 0
                ? 'Statement line has zero amount — nothing to match.'
                : 'No ERP transactions found within ±7 days with matching amount. Check date window, fees, or split payments.';
        }

        $discrepancy = null;
        if ($line->match_status === 'matched') {
            $discrepancy = 'Line already matched — unmatch before rematching. Cross-bank and cross-business matches are not permitted.';
        }

        if (Schema::hasTable('acct_ai_review_tasks') && $enriched !== [] && ($enriched[0]['confidence'] ?? 0) >= 80) {
            // Optional review task for high-confidence suggestions
        }

        return [
            'suggestions' => $enriched,
            'unmatched_explanation' => $unmatched,
            'discrepancy_insight' => $discrepancy,
            'auto_matched' => false,
            'currency' => 'LKR',
        ];
    }
}
