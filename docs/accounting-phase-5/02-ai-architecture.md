# AI Architecture

Flow: User question → auth/tenant scope → deterministic intent (`FinancialToolRegistry::detectTools`) → allowlisted read-only tools → trusted backend services (`FinancialStatementService`, etc.) → minimized structured results → provider explanation (`FakeAiProvider` / OpenAI-compatible) → source refs.

Constraints enforced in code:
- No LLM SQL
- No journal write tools
- Prompt-injection pattern rejection
- Usage limits + usage logs without API keys
- Amounts in replies come from tool_results, not model invention (fake provider)
