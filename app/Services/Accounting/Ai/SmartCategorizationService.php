<?php

namespace App\Services\Accounting\Ai;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Suggests expense/purchase classification — never auto-posts.
 */
class SmartCategorizationService
{
    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function suggest(int $businessId, string $sourceType, ?int $sourceId, array $context = []): array
    {
        $description = mb_strtolower(trim((string) ($context['description'] ?? '')));
        $supplierId = $context['supplier_id'] ?? null;
        $amount = isset($context['amount']) ? round((float) $context['amount'], 2) : null;

        $account = $this->suggestAccount($businessId, $description, $supplierId);
        $tax = $this->suggestTaxCode($businessId, $context);
        $costCenter = $this->suggestCostCenter($businessId, $context);
        $fixedAssetCandidate = $this->fixedAssetCandidate($description, $amount);

        $confidence = (float) ($account['confidence'] ?? 0);
        $reason = $account['reason'] ?? 'No strong historical match';

        $id = null;
        if (Schema::hasTable('acct_categorization_suggestions')) {
            $id = (int) DB::table('acct_categorization_suggestions')->insertGetId([
                'business_id' => $businessId,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'suggestion_type' => 'expense_account',
                'suggested_account_id' => $account['account_id'] ?? null,
                'suggested_tax_code' => $tax['code'] ?? null,
                'suggested_cost_center_id' => $costCenter['id'] ?? null,
                'suggested_project_id' => $context['project_id'] ?? null,
                'payload' => json_encode([
                    'description' => $context['description'] ?? null,
                    'fixed_asset_candidate' => $fixedAssetCandidate,
                    'inventory_classification' => $context['inventory'] ?? null,
                ]),
                'confidence' => $confidence,
                'reason' => $reason,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (Schema::hasTable('acct_ai_review_tasks')) {
                DB::table('acct_ai_review_tasks')->updateOrInsert(
                    [
                        'business_id' => $businessId,
                        'subject_type' => 'categorization_suggestion',
                        'subject_id' => $id,
                        'task_type' => 'categorization',
                    ],
                    [
                        'title' => 'Categorization: '.($context['description'] ?? ('#'.$id)),
                        'suggestion_payload' => json_encode([
                            'suggestion_id' => $id,
                            'account_id' => $account['account_id'] ?? null,
                            'tax_code' => $tax['code'] ?? null,
                            'cost_center_id' => $costCenter['id'] ?? null,
                            'fixed_asset_candidate' => $fixedAssetCandidate,
                        ]),
                        'confidence' => $confidence,
                        'status' => 'pending',
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }

        return [
            'suggestion_id' => $id,
            'account' => $account,
            'tax' => $tax,
            'cost_center' => $costCenter,
            'fixed_asset_candidate' => $fixedAssetCandidate,
            'confidence' => $confidence,
            'reason' => $reason,
            'status' => 'pending',
            'auto_posted' => false,
        ];
    }

    public function decide(int $businessId, int $suggestionId, string $decision, ?int $userId, ?string $note = null, ?int $overrideAccountId = null): void
    {
        if (! in_array($decision, ['accepted', 'rejected'], true)) {
            throw new RuntimeException('Invalid decision.');
        }
        $row = DB::table('acct_categorization_suggestions')
            ->where('id', $suggestionId)
            ->where('business_id', $businessId)
            ->first();
        if (! $row) {
            throw new RuntimeException('Suggestion not found.');
        }

        $update = [
            'status' => $decision,
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
            'review_note' => $note,
            'updated_at' => now(),
        ];
        if ($overrideAccountId && $decision === 'accepted') {
            // Validate GL account belongs to same business when column exists
            if (Schema::hasTable('chart_of_accounts')) {
                $q = DB::table('chart_of_accounts')->where('id', $overrideAccountId);
                if (Schema::hasColumn('chart_of_accounts', 'business_id') && $businessId > 0) {
                    $q->where(function ($qq) use ($businessId) {
                        $qq->where('business_id', $businessId)->orWhere('business_id', 0);
                    });
                }
                if (! $q->exists()) {
                    throw new RuntimeException('Invalid GL account for this business.');
                }
            }
            $update['suggested_account_id'] = $overrideAccountId;
        }

        DB::table('acct_categorization_suggestions')->where('id', $suggestionId)->update($update);

        if (Schema::hasTable('acct_ai_review_tasks')) {
            DB::table('acct_ai_review_tasks')
                ->where('business_id', $businessId)
                ->where('subject_type', 'categorization_suggestion')
                ->where('subject_id', $suggestionId)
                ->update(['status' => $decision === 'accepted' ? 'accepted' : 'rejected', 'updated_at' => now()]);
        }

        if (Schema::hasTable('acct_ai_review_decisions')) {
            $task = DB::table('acct_ai_review_tasks')
                ->where('business_id', $businessId)
                ->where('subject_type', 'categorization_suggestion')
                ->where('subject_id', $suggestionId)
                ->first();
            if ($task) {
                DB::table('acct_ai_review_decisions')->insert([
                    'review_task_id' => $task->id,
                    'business_id' => $businessId,
                    'user_id' => $userId ?? 0,
                    'decision' => $decision,
                    'edited_payload' => $overrideAccountId ? json_encode(['account_id' => $overrideAccountId]) : null,
                    'note' => $note,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * @return array{account_id:?int, gl_code:?string, name:?string, confidence:float, reason:string}
     */
    protected function suggestAccount(int $businessId, string $description, mixed $supplierId): array
    {
        $empty = ['account_id' => null, 'gl_code' => null, 'name' => null, 'confidence' => 0.0, 'reason' => 'No match'];

        if (! Schema::hasTable('chart_of_accounts')) {
            return $empty;
        }

        // Historical expense mapping by supplier
        if ($supplierId && Schema::hasTable('purchases') && Schema::hasColumn('purchases', 'chart_of_account_id')) {
            $hist = DB::table('purchases')
                ->where('supplier_id', $supplierId)
                ->whereNotNull('chart_of_account_id')
                ->orderByDesc('id')
                ->value('chart_of_account_id');
            if ($hist) {
                $acc = DB::table('chart_of_accounts')->where('id', $hist)->first();
                if ($acc) {
                    return [
                        'account_id' => (int) $acc->id,
                        'gl_code' => $acc->gl_code ?? null,
                        'name' => $acc->name ?? null,
                        'confidence' => 85.0,
                        'reason' => 'Historical supplier account mapping',
                    ];
                }
            }
        }

        if ($description !== '') {
            $acc = DB::table('chart_of_accounts')
                ->where('account_type', 'expense')
                ->where(function ($q) use ($description) {
                    $q->whereRaw('LOWER(name) like ?', ['%'.$description.'%']);
                })
                ->orderBy('id')
                ->first();
            if ($acc) {
                return [
                    'account_id' => (int) $acc->id,
                    'gl_code' => $acc->gl_code ?? null,
                    'name' => $acc->name ?? null,
                    'confidence' => 55.0,
                    'reason' => 'Description matched expense account name',
                ];
            }
        }

        $fallback = DB::table('chart_of_accounts')
            ->where('account_type', 'expense')
            ->orderBy('id')
            ->first();
        if ($fallback) {
            return [
                'account_id' => (int) $fallback->id,
                'gl_code' => $fallback->gl_code ?? null,
                'name' => $fallback->name ?? null,
                'confidence' => 25.0,
                'reason' => 'Low-confidence default expense account — requires user approval',
            ];
        }

        return $empty;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{code:?string, confidence:float}
     */
    protected function suggestTaxCode(int $businessId, array $context): array
    {
        if (! Schema::hasTable('acct_tax_codes')) {
            return ['code' => null, 'confidence' => 0];
        }
        $code = DB::table('acct_tax_codes')
            ->when(Schema::hasColumn('acct_tax_codes', 'business_id') && $businessId > 0, fn ($q) => $q->where('business_id', $businessId))
            ->orderBy('id')
            ->value('code');

        return ['code' => $code, 'confidence' => $code ? 40.0 : 0.0];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{id:?int, confidence:float}
     */
    protected function suggestCostCenter(int $businessId, array $context): array
    {
        if (! empty($context['cost_center_id'])) {
            return ['id' => (int) $context['cost_center_id'], 'confidence' => 90.0];
        }
        if (! Schema::hasTable('acct_cost_centers')) {
            return ['id' => null, 'confidence' => 0];
        }
        $id = DB::table('acct_cost_centers')
            ->when(Schema::hasColumn('acct_cost_centers', 'business_id') && $businessId > 0, fn ($q) => $q->where('business_id', $businessId))
            ->orderBy('id')
            ->value('id');

        return ['id' => $id ? (int) $id : null, 'confidence' => $id ? 30.0 : 0.0];
    }

    /**
     * Never auto-classify as fixed asset solely from name resemblance.
     *
     * @return array{candidate:bool, requires_review:bool, reason:string}
     */
    protected function fixedAssetCandidate(string $description, ?float $amount): array
    {
        $looksLikeEquipment = (bool) preg_match('/computer|laptop|printer|machine|vehicle|equipment|furniture/i', $description);
        if ($looksLikeEquipment) {
            return [
                'candidate' => true,
                'requires_review' => true,
                'reason' => 'Name resembles equipment — NOT auto-classified as fixed asset; accountant must confirm capitalization policy.',
            ];
        }

        return ['candidate' => false, 'requires_review' => false, 'reason' => 'No fixed-asset indicators'];
    }
}
