# AI Provider Integration

Config: `config/accounting_ai.php` + env (`ACCOUNTING_AI_*`).

Providers: `fake`, `openai`, `gemini`, `compatible`, `self_hosted`.

- `gemini` → `GeminiAiProvider` (Google Generative Language `generateContent`)
- Env: `ACCOUNTING_AI_GEMINI_API_KEY`, `ACCOUNTING_AI_GEMINI_MODEL` (default `gemini-2.0-flash`), optional `ACCOUNTING_AI_GEMINI_ENDPOINT`
- Enable: `ACCOUNTING_AI_ENABLED=true` + AI Settings → provider `gemini` + disclosure checkbox

`AiProviderManager` loads per-business settings from `acct_ai_provider_settings` (no API key column). Keys only from env.

Failure codes: `not_configured`, `invalid_api_key`, `rate_limit`, `timeout`, `malformed_response`, `unavailable`.

**LIVE AI PROVIDER VERIFIED:** No — credentials not configured in this environment. Gemini HTTP contract covered by mocked tests.
