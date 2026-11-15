# Phase 5 — Feature Gap Analysis

**Branch:** `feature/accounting-phase-5`  
**Baseline:** `fc5207d2` (Phase 4.1 on main)  
**Currency:** LKR only

## Classification

| Feature | Status | Notes |
|---------|--------|-------|
| Authoritative GL / postOnce | EXISTING | Preserved |
| Financial statements / dashboard / forecast | EXISTING | Reused by AI tools |
| BankMatchingService suggestions | EXISTING / PARTIAL | Enhanced via BankMatchingAssistService explanations |
| Recurring + scheduled reports | EXISTING | Not duplicated |
| Document/Media storage | EXISTING | OCR uses private Storage disk |
| Queues / schedule | EXISTING | Added anomaly + automation commands |
| Permissions accounting.view/manage | EXISTING | Extended with accounting.ai.* |
| AI provider abstraction | MISSING → IMPLEMENTED | Fake + OpenAI-compatible |
| AI Accounting Assistant | MISSING → IMPLEMENTED | Allowlisted tools, grounded replies |
| OCR document capture | MISSING → IMPLEMENTED | Draft only, no auto-post |
| Smart categorization | MISSING → IMPLEMENTED | Suggestions + review |
| Anomaly detection | MISSING → IMPLEMENTED | Deterministic rules + alerts |
| Financial insights | MISSING → IMPLEMENTED | GL-backed facts |
| Workflow automation | MISSING → IMPLEMENTED | Allowlisted triggers/actions, occurrence keys |
| AI Review Center | MISSING → IMPLEMENTED | Unified pending review |
| Live OpenAI credentials | BLOCKED | Not configured — FakeAiProvider used in tests |
| MySQL staging | NOT VERIFIED | Same as Phase 4.1 |

## Baseline tests before Phase 5 code

`php artisan test --filter=Accounting` → **87 passed** on `fc5207d2`.
