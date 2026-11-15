# AI Accounting Assistant

Routes: `GET/POST accounting/phase5/assistant`, conversation show.

Service: `App\Services\Accounting\Ai\AiAssistantService`.

Supports English + Sinhala (Unicode Sinhala detection). Suggested questions in UI.

Tools allowlist in `FinancialToolRegistry::ALLOWED`.

Tests: Sinhala/English amount preservation, injection reject, unauthorized tool access.
