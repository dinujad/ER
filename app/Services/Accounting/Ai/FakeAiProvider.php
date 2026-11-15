<?php

namespace App\Services\Accounting\Ai;

/**
 * Deterministic test double — never invents financial amounts.
 * Amounts must come from tool_results injected by AiAssistantService.
 */
class FakeAiProvider implements AiProviderInterface
{
    public function name(): string
    {
        return 'fake';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function chat(array $messages, array $options = []): array
    {
        $lastUser = '';
        foreach (array_reverse($messages) as $m) {
            if (($m['role'] ?? '') === 'user') {
                $lastUser = (string) ($m['content'] ?? '');
                break;
            }
        }

        $toolResults = $options['tool_results'] ?? null;
        $locale = $options['locale'] ?? 'en';

        if (! empty($options['force_error'])) {
            throw new AiProviderException('Forced provider failure', 'provider_error');
        }

        if (! empty($options['force_timeout'])) {
            throw new AiProviderException('Provider timeout', 'timeout');
        }

        $content = $this->buildGroundedReply($lastUser, $toolResults, $locale);

        return [
            'content' => $content,
            'tool_calls' => $options['planned_tools'] ?? null,
            'prompt_tokens' => max(1, (int) (strlen($lastUser) / 4)),
            'completion_tokens' => max(1, (int) (strlen($content) / 4)),
            'provider' => 'fake',
            'model' => 'fake-deterministic-v1',
        ];
    }

    /**
     * @param  array<string, mixed>|null  $toolResults
     */
    protected function buildGroundedReply(string $question, ?array $toolResults, string $locale): string
    {
        if ($toolResults === null || $toolResults === []) {
            return $locale === 'si'
                ? 'මෙම ඉල්ලීම සඳහා විශ්වාසනීය මූල්‍ය දත්ත නොමැත. කරුණාකර වාර්තා මෙවලම් භාවිතා කරන්න.'
                : 'No trusted financial tool results are available for this question. I will not invent amounts.';
        }

        $lines = [];
        $lines[] = $locale === 'si'
            ? 'පහත පිළිතුර විශ්වාසනීය backend මූල්‍ය සේවාවලින් පමණි (LKR).'
            : 'The following answer uses trusted backend financial services only (LKR).';

        foreach ($toolResults as $tool => $payload) {
            if (! is_array($payload)) {
                continue;
            }
            $amount = $payload['primary_amount'] ?? $payload['net'] ?? $payload['total'] ?? null;
            $period = $payload['period'] ?? (($payload['from'] ?? '').' → '.($payload['to'] ?? $payload['as_of'] ?? ''));
            $source = $payload['source'] ?? $tool;
            $factOrEst = ! empty($payload['is_estimate']) ? 'estimate' : 'actual';

            if ($amount !== null) {
                $lines[] = sprintf(
                    '- %s: LKR %s | period: %s | source: %s | type: %s',
                    $tool,
                    number_format((float) $amount, 2, '.', ''),
                    trim((string) $period) ?: 'n/a',
                    $source,
                    $factOrEst
                );
            } else {
                $summary = $payload['summary'] ?? json_encode($payload, JSON_UNESCAPED_UNICODE);
                $lines[] = sprintf('- %s: %s | source: %s', $tool, is_string($summary) ? $summary : 'see structured data', $source);
            }

            if (! empty($payload['unavailable'])) {
                $lines[] = '  Note: some requested data was unavailable; no values were invented.';
            }
        }

        $lines[] = $locale === 'si'
            ? 'මෙම සංඛ්‍යා LLM ගණනයක් නොවේ. පළ කිරීම් සඳහා මිනිස් අනුමතිය අවශ්‍යයි.'
            : 'These figures are not LLM calculations. Human authorization is required for any posting.';

        return implode("\n", $lines);
    }
}
