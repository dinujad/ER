# AI Provider Integration

Config: `config/accounting_ai.php` + env (`ACCOUNTING_AI_*`).

Providers: `fake`, `openai`, `compatible`, `self_hosted` (OpenAI-compatible HTTP).

`AiProviderManager` loads per-business settings from `acct_ai_provider_settings` (no API key column). Keys only from env.

Failure codes: `not_configured`, `invalid_api_key`, `rate_limit`, `timeout`, `malformed_response`, `unavailable`.

**LIVE AI PROVIDER VERIFIED:** No — credentials not configured in this environment.
