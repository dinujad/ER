<?php

namespace App\Services\Accounting\Ai;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Grounded read-only AI Accounting Assistant.
 * Never posts journals; never grants write tools to the LLM.
 */
class AiAssistantService
{
    public function __construct(
        protected AiProviderManager $providers = new AiProviderManager,
        protected FinancialToolRegistry $tools = new FinancialToolRegistry,
    ) {}

    /**
     * @param  array{from?:string,to?:string,locale?:string,conversation_id?:int}  $context
     * @return array<string, mixed>
     */
    public function ask(int $businessId, object $user, string $question, array $context = []): array
    {
        $question = trim($question);
        $maxChars = (int) config('accounting_ai.max_message_chars', 4000);
        if ($question === '' || mb_strlen($question) > $maxChars) {
            throw new RuntimeException('Invalid question length.');
        }

        $this->assertNoInjection($question);
        $this->assertUsageLimit($businessId);

        $locale = $this->detectLocale($question, $context['locale'] ?? null);
        $from = $context['from'] ?? date('Y-m-01', strtotime('first day of last month'));
        $to = $context['to'] ?? date('Y-m-t', strtotime('last day of last month'));

        $planned = $this->tools->detectTools($question);
        $toolResults = [];
        $sourceRefs = [];

        foreach ($planned as $tool) {
            try {
                $result = $this->tools->invoke($tool, $businessId, [
                    'from' => $from,
                    'to' => $to,
                    'as_of' => $to,
                ], $user);
                $toolResults[$tool] = $this->minimizePayload($result);
                $sourceRefs[] = [
                    'tool' => $tool,
                    'source' => $result['source'] ?? $tool,
                    'amount' => $result['primary_amount'] ?? null,
                    'period' => $result['period'] ?? "$from → $to",
                    'is_estimate' => (bool) ($result['is_estimate'] ?? false),
                    'url' => $this->drilldownFor($tool),
                ];
            } catch (\Throwable $e) {
                $toolResults[$tool] = [
                    'unavailable' => true,
                    'summary' => 'Tool failed: '.$e->getMessage(),
                    'source' => $tool,
                ];
            }
        }

        $conversationId = $this->ensureConversation($businessId, (int) $user->id, $locale, $from, $to, $context['conversation_id'] ?? null);
        $this->storeMessage($conversationId, 'user', $question);

        $provider = $this->providers->resolve($businessId);
        $settings = $this->providers->businessSettings($businessId);

        // Prefer fake when provider not available / not enabled — never fake a live reply silently when live was expected
        $usedLive = false;
        $status = 'ok';
        $errorCode = null;
        $reply = '';

        try {
            if ($provider->name() !== 'fake' && (! ($settings['enabled'] ?? false) || ! $provider->isAvailable())) {
                throw new AiProviderException('Configured AI provider is unavailable or disabled.', 'not_configured');
            }

            $response = $provider->chat([
                ['role' => 'user', 'content' => $this->sanitizeForModel($question)],
            ], [
                'locale' => $locale,
                'tool_results' => $toolResults,
                'planned_tools' => $planned,
                'timeout' => $settings['timeout_seconds'] ?? 30,
                'max_tokens' => $settings['max_tokens'] ?? 2048,
            ]);

            $reply = (string) ($response['content'] ?? '');
            $usedLive = $provider->name() !== 'fake';
            $this->logUsage($businessId, (int) $user->id, 'assistant', $response, 'ok');
        } catch (AiProviderException $e) {
            $status = 'provider_error';
            $errorCode = $e->errorCode;
            // Deterministic grounded fallback — clearly not a live model
            $fake = new FakeAiProvider;
            $fallback = $fake->chat([['role' => 'user', 'content' => $question]], [
                'locale' => $locale,
                'tool_results' => $toolResults,
            ]);
            $reply = ($locale === 'si'
                ? "AI සැපයුම්කරු ලබාගත නොහැක ({$e->errorCode}). විශ්වාසනීය මෙවලම් ප්‍රතිඵල පමණක් පහත දැක්වේ.\n\n"
                : "AI provider unavailable ({$e->errorCode}). Showing trusted tool results only (not a live model reply).\n\n")
                .$fallback['content'];
            $this->logUsage($businessId, (int) $user->id, 'assistant', [
                'provider' => $provider->name(),
                'model' => null,
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
            ], $status, $errorCode);
        }

        $messageId = $this->storeMessage($conversationId, 'assistant', $reply, $planned, $toolResults, $sourceRefs);

        return [
            'conversation_id' => $conversationId,
            'message_id' => $messageId,
            'reply' => $reply,
            'locale' => $locale,
            'tools_used' => $planned,
            'tool_results' => $toolResults,
            'source_refs' => $sourceRefs,
            'provider' => $provider->name(),
            'live_provider' => $usedLive,
            'status' => $status,
            'error_code' => $errorCode,
            'period' => ['from' => $from, 'to' => $to],
            'currency' => 'LKR',
            'read_only' => true,
        ];
    }

    public function assertNoInjection(string $text): void
    {
        foreach (config('accounting_ai.prompt_injection_patterns', []) as $pattern) {
            if (@preg_match($pattern, $text) === 1) {
                throw new RuntimeException('Message rejected by prompt-injection policy.');
            }
        }
    }

    protected function sanitizeForModel(string $text): string
    {
        return "UNTRUSTED_USER_MESSAGE:\n".$text."\nEND_UNTRUSTED_USER_MESSAGE";
    }

    protected function detectLocale(string $question, ?string $forced): string
    {
        if ($forced === 'si' || $forced === 'en') {
            return $forced;
        }
        if (preg_match('/[\x{0D80}-\x{0DFF}]/u', $question)) {
            return 'si';
        }

        return 'en';
    }

    protected function assertUsageLimit(int $businessId): void
    {
        if (! Schema::hasTable('acct_ai_usage_logs')) {
            return;
        }
        $settings = $this->providers->businessSettings($businessId);
        $limit = (int) ($settings['monthly_request_limit'] ?? 500);
        $count = DB::table('acct_ai_usage_logs')
            ->where('business_id', $businessId)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
        if ($count >= $limit) {
            throw new RuntimeException('Monthly AI request limit reached for this business.');
        }
    }

    protected function ensureConversation(int $businessId, int $userId, string $locale, string $from, string $to, ?int $existing): int
    {
        if (! Schema::hasTable('acct_ai_conversations')) {
            return 0;
        }
        if ($existing) {
            $row = DB::table('acct_ai_conversations')
                ->where('id', $existing)
                ->where('business_id', $businessId)
                ->where('user_id', $userId)
                ->first();
            if ($row) {
                DB::table('acct_ai_conversations')->where('id', $existing)->update([
                    'last_message_at' => now(),
                    'context_from' => $from,
                    'context_to' => $to,
                    'locale' => $locale,
                    'updated_at' => now(),
                ]);

                return (int) $existing;
            }
        }

        return (int) DB::table('acct_ai_conversations')->insertGetId([
            'business_id' => $businessId,
            'user_id' => $userId,
            'title' => 'Accounting chat',
            'locale' => $locale,
            'context_from' => $from,
            'context_to' => $to,
            'last_message_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  list<string>|null  $toolCalls
     * @param  array<string, mixed>|null  $toolResults
     * @param  list<array<string, mixed>>|null  $sourceRefs
     */
    protected function storeMessage(
        int $conversationId,
        string $role,
        string $content,
        ?array $toolCalls = null,
        ?array $toolResults = null,
        ?array $sourceRefs = null
    ): int {
        if ($conversationId <= 0 || ! Schema::hasTable('acct_ai_messages')) {
            return 0;
        }

        return (int) DB::table('acct_ai_messages')->insertGetId([
            'conversation_id' => $conversationId,
            'role' => $role,
            'content' => $content,
            'tool_calls' => $toolCalls ? json_encode(array_values($toolCalls)) : null,
            'tool_results' => $toolResults ? json_encode($toolResults) : null,
            'source_refs' => $sourceRefs ? json_encode($sourceRefs) : null,
            'is_estimate' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $response
     */
    protected function logUsage(
        int $businessId,
        int $userId,
        string $feature,
        array $response,
        string $status,
        ?string $errorCode = null
    ): void {
        if (! Schema::hasTable('acct_ai_usage_logs')) {
            return;
        }
        DB::table('acct_ai_usage_logs')->insert([
            'business_id' => $businessId,
            'user_id' => $userId,
            'feature' => $feature,
            'provider' => $response['provider'] ?? 'unknown',
            'model' => $response['model'] ?? null,
            'prompt_tokens' => (int) ($response['prompt_tokens'] ?? 0),
            'completion_tokens' => (int) ($response['completion_tokens'] ?? 0),
            'estimated_cost' => null,
            'status' => $status,
            'error_code' => $errorCode,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function minimizePayload(array $payload): array
    {
        $keep = [
            'tool', 'source', 'primary_amount', 'previous_amount', 'change_amount',
            'period', 'from', 'to', 'as_of', 'currency', 'is_estimate', 'unavailable',
            'summary', 'income', 'expense', 'net', 'assets', 'asset_count',
            'closing_cash', 'note', 'certainty',
        ];
        $out = [];
        foreach ($keep as $k) {
            if (array_key_exists($k, $payload)) {
                $out[$k] = $payload[$k];
            }
        }
        if (isset($payload['lines']) && is_iterable($payload['lines'])) {
            $out['lines'] = collect($payload['lines'])->take(10)->values()->all();
        }
        if (isset($payload['rows']) && is_iterable($payload['rows'])) {
            $out['rows'] = collect($payload['rows'])->take(10)->values()->all();
        }

        return $out;
    }

    protected function drilldownFor(string $tool): string
    {
        return match ($tool) {
            'getProfitAndLoss' => url('accounting/phase4/statements/profit-and-loss'),
            'getBalanceSheet' => url('accounting/phase4/statements/balance-sheet'),
            'getCashFlow', 'getCashFlowForecast' => url('accounting/phase4/cash-forecast'),
            'getTrialBalance' => url('accounting/phase4/statements/trial-balance'),
            'getCustomerAging' => url('report/accounting/accounts_receivable_ageing_summary'),
            'getSupplierAging' => url('report/accounting/accounts_payable_ageing_summary'),
            'getBudgetVariance' => url('accounting/budget-versions/vs-actual'),
            'getCostCenterProfitability' => url('accounting/cost-center-reports/profit-and-loss'),
            'getFixedAssetSummary', 'getDepreciationSummary' => url('accounting/fixed-assets'),
            'getLoanSummary' => url('accounting/loans'),
            'getTaxSummary' => url('accounting/phase4/tax'),
            default => url('accounting/dashboard'),
        };
    }
}
