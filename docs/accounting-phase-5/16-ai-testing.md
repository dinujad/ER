# AI Testing

Test doubles: `FakeAiProvider`, forced OCR field payloads, SQLite in-memory schema from migration.

Distinctions:
- AI integration (mocked): VERIFIED via AccountingPhase5CoreTest
- Live AI provider: NOT VERIFIED
- OCR model quality: NOT VERIFIED (fake driver)
- Production credentials: NOT CONFIGURED
