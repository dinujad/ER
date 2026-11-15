# Phase 2 — Remaining Issues

| Item | Severity | Status |
|------|----------|--------|
| Interactive bank match suggestions UI | Medium | Schema ready; UI incomplete |
| Excel (.xlsx) statement import | Low | CSV only |
| Customer/supplier statement PDF | Medium | Ledger HTML only |
| Payment allocation HTTP screens | Medium | Service exists; limited UI |
| Recurring sales invoices / purchase bills | Medium | Journals only |
| `draft_notify` email | Low | No mailer hook yet |
| Circular parent validation on COA update | Medium | Delete guards only |
| Account tree UI (nested table) | Low | Flat list + parent field preserved |
| Opening balance wizard vs AR/AP subledgers | Medium | COA opening + period controls; dedicated wizard partial |
| Production `accounting:diagnose` on live DB | Ops | Not run (no prod mutation) |
| Phase 1 partial items (cheque Eloquent E2E, sale-return COGS items, inventory qty vs GL) | Inherited | Still PARTIAL from Phase 1 |

## Honest completion

Phase 2 is **partially complete**: foundation + journals + register + periods + recurring + statement import + ledgers are shipped and unit-tested; banking matcher / full AR-AP statement PDF / document-level recurring remain open.
