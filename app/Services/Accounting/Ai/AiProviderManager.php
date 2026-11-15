<?php

namespace App\Services\Accounting\Ai;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AiProviderManager
{
    public function resolve(?int $businessId = null): AiProviderInterface
    {
        $settings = $this->businessSettings($businessId);
        $providerKey = $settings['provider'] ?? config('accounting_ai.default_provider', 'fake');

        if (! ($settings['enabled'] ?? config('accounting_ai.enabled', false))) {
            // Always allow fake for local/tests when explicitly requested
            if ($providerKey !== 'fake') {
                return new FakeAiProvider;
            }
        }

        return $this->make($providerKey, $settings);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function make(string $providerKey, array $settings = []): AiProviderInterface
    {
        $cfg = config('accounting_ai.providers.'.$providerKey, config('accounting_ai.providers.fake'));
        $driver = $cfg['driver'] ?? 'fake';
        $timeout = (int) ($settings['timeout_seconds'] ?? config('accounting_ai.timeout_seconds', 30));
        $maxTokens = (int) ($settings['max_tokens'] ?? config('accounting_ai.max_tokens', 2048));
        $model = (string) ($settings['model'] ?? $cfg['model'] ?? 'fake-deterministic-v1');

        if ($driver === 'fake' || $providerKey === 'fake') {
            return new FakeAiProvider;
        }

        return new OpenAiCompatibleProvider(
            $providerKey,
            $cfg['api_key'] ?? null,
            $settings['endpoint'] ?? ($cfg['endpoint'] ?? null),
            $model,
            $timeout,
            $maxTokens,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function businessSettings(?int $businessId = null): array
    {
        $defaults = [
            'provider' => config('accounting_ai.default_provider', 'fake'),
            'model' => null,
            'endpoint' => null,
            'timeout_seconds' => config('accounting_ai.timeout_seconds', 30),
            'max_tokens' => config('accounting_ai.max_tokens', 2048),
            'monthly_request_limit' => config('accounting_ai.monthly_request_limit', 500),
            'enabled' => (bool) config('accounting_ai.enabled', false),
            'external_disclosure_accepted' => false,
            'retention_days' => config('accounting_ai.retention_days', 90),
            'allowed_features' => config('accounting_ai.features', []),
        ];

        if ($businessId === null || ! Schema::hasTable('acct_ai_provider_settings')) {
            return $defaults;
        }

        $row = DB::table('acct_ai_provider_settings')->where('business_id', $businessId)->first();
        if (! $row) {
            return $defaults;
        }

        return array_merge($defaults, [
            'provider' => $row->provider,
            'model' => $row->model,
            'endpoint' => $row->endpoint,
            'timeout_seconds' => (int) $row->timeout_seconds,
            'max_tokens' => (int) $row->max_tokens,
            'monthly_request_limit' => (int) $row->monthly_request_limit,
            'enabled' => (bool) $row->enabled,
            'external_disclosure_accepted' => (bool) $row->external_disclosure_accepted,
            'retention_days' => (int) $row->retention_days,
            'allowed_features' => json_decode($row->allowed_features ?? 'null', true) ?: $defaults['allowed_features'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveSettings(int $businessId, array $data, ?int $userId = null): void
    {
        if (! Schema::hasTable('acct_ai_provider_settings')) {
            return;
        }

        $payload = [
            'provider' => $data['provider'] ?? 'fake',
            'model' => $data['model'] ?? null,
            'endpoint' => $data['endpoint'] ?? null,
            'timeout_seconds' => (int) ($data['timeout_seconds'] ?? 30),
            'max_tokens' => (int) ($data['max_tokens'] ?? 2048),
            'monthly_request_limit' => (int) ($data['monthly_request_limit'] ?? 500),
            'enabled' => (bool) ($data['enabled'] ?? false),
            'external_disclosure_accepted' => (bool) ($data['external_disclosure_accepted'] ?? false),
            'retention_days' => (int) ($data['retention_days'] ?? 90),
            'allowed_features' => json_encode($data['allowed_features'] ?? config('accounting_ai.features')),
            'updated_by' => $userId,
            'updated_at' => now(),
        ];

        $exists = DB::table('acct_ai_provider_settings')->where('business_id', $businessId)->exists();
        if ($exists) {
            DB::table('acct_ai_provider_settings')->where('business_id', $businessId)->update($payload);
        } else {
            $payload['business_id'] = $businessId;
            $payload['created_at'] = now();
            DB::table('acct_ai_provider_settings')->insert($payload);
        }
    }
}
