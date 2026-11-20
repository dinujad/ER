# Phase 6 — Xero / QuickBooks Gap Analysis & Roadmap

**Baseline:** `main` @ `67c0ad9b` (Phase 5) + uncommitted SSCL / invoice-tax work (2026-11-20)
**Currency:** LKR only
**Goal:** Professional-grade accounting comparable to Xero and QuickBooks Online, including VAT-registered
workflows, full drill-down from any report figure to its source document, and a complete
"who did what, when" history on every invoice and transaction.

Status legend: ✅ have · 🟡 partial · ❌ missing

---

## 1. What already exists (do not rebuild)

| Area | Status | Notes |
|---|---|---|
| Chart of accounts, hierarchy | ✅ | `ChartOfAccountHierarchyService` |
| Double-entry journals, manual journals, reversal | ✅ | `AccountingService::postOnce`, `ManualJournalService` |
| Auto-posting from ERP (sales, payments, returns, purchases, expenses, payroll, FA, loans) | ✅ | Observers → `accounting_postings` (has `source_type/source_id`) |
| Period locks / lock dates | ✅ | `AccountingPeriodService` |
| Bank statement import, matching, reconciliation | ✅ | Phase 2 + Phase 5 assist |
| P&L, Balance Sheet, Trial Balance, Cash Flow, Changes in Equity, GL | ✅ | Phase 4 |
| AR / AP ageing summary + detail, customer / supplier statements | ✅ | Module reports |
| Budgets, budget vs actual | ✅ | |
| Cost centres (≈ Xero tracking categories / QBO classes) | ✅ | Phase 3 |
| Fixed assets, depreciation, disposals | ✅ | Phase 3 |
| Loans | ✅ | Phase 3 |
| Recurring journals | ✅ | |
| Tax codes with effective dates, tax periods, tax summary | 🟡 | `TaxManagementService` — not wired to sales lines or purchases |
| Year-end close | ✅ | Phase 4 |
| Custom report builder, scheduled reports, KPI dashboard | ✅ | Phase 4 |
| AI assistant, OCR, anomalies | ✅ | Phase 5 |
| Accounting audit log | 🟡 | `AccountingAuditEvent` — accounting actions only, not orders / invoices / payments |
| Quotes, purchase orders | ✅ | Retail quotations, purchases |
| SSCL + VAT on sales invoices | 🟡 | Done 2026-11-20 at invoice-total level (not per line) |

---

## 2. Gaps vs Xero / QuickBooks

### A. Document drill-down & history (user priority #1)
| Feature (Xero / QBO) | Status | Gap |
|---|---|---|
| Invoice detail page: lines, tax, payments, credits, balance, status | 🟡 | Order page exists, but not an accounting view |
| "Where did this come from": source order, sales person, stock issue, quotation | ❌ | |
| Journal view of a document ("View journal" / "Transaction journal report") | ❌ | Postings exist, no UI from document |
| Drill-down: P&L / BS / TB figure → account transactions → source document | 🟡 | Account register exists; no link to the source document |
| History & notes on every document (who created / edited / approved / emailed / paid, with field before→after) | ❌ | Only accounting-module audit; orders / payments / returns / purchases not tracked |
| Global audit trail report (filter by user, date, document type, action) | 🟡 | Accounting-only |
| Attachments on any document | 🟡 | FA + OCR only |

### B. VAT-registered business workflows
| Feature | Status | Gap |
|---|---|---|
| Tax rate per invoice **line** (Standard 18%, Zero-rated, Exempt, Out of scope) | ❌ | Tax is invoice-level only |
| Line amounts tax-inclusive / tax-exclusive / no tax switch | ❌ | |
| Customer & supplier VAT registration (TIN / VAT no., default tax rate) | 🟡 | Customer TIN only |
| **Input VAT on purchases / bills** posted to 1160 Tax Receivable | ❌ | Purchase `tax_amount` is not posted as claimable VAT |
| VAT return report (output VAT − input VAT, per period, with drill-down to each invoice / bill) | ❌ | `taxSummary` aggregates GL only |
| SSCL return report | ❌ | SSCL currently shares 2300 with VAT |
| Separate GL accounts: VAT Output, VAT Input, SSCL Payable | ❌ | All in 2300 / 1160 |
| Tax-invoice compliance checks (TIN present, serial, sequence gaps) | 🟡 | Serial exists; no gap report |
| Simplified tax invoice for non-registered buyers | ✅ | Done 2026-11-20 |

### C. Sales / Receivables
| Feature | Status | Gap |
|---|---|---|
| Invoice statuses: Draft → Awaiting approval → Awaiting payment → Partially paid → Paid → Overdue → Voided | 🟡 | Order statuses only, no overdue |
| Payment terms & due date per customer / invoice | ❌ | |
| Credit notes (separate document, allocate to invoices or refund) | 🟡 | Sale returns + claim credit; no formal credit note |
| Overpayments / prepayments (customer credit balance) | 🟡 | |
| Void invoice (with reversing journal, keeps number) | ❌ | Cancel / delete only |
| Statements & payment reminders (email / SMS on overdue) | 🟡 | Statements exist; no reminders |
| Customer credit-limit enforcement | 🟡 | Column exists |
| Batch receive payments across invoices | 🟡 | Payment allocation exists |
| Invoice email / share link | ❌ | |

### D. Purchases / Payables
| Feature | Status | Gap |
|---|---|---|
| Bills with line tax, due dates, statuses | 🟡 | Purchases exist; no line tax / due date |
| Supplier credit notes | 🟡 | Purchase returns |
| Batch supplier payments | 🟡 | |
| Bill approval workflow | ❌ | |

### E. Banking
| Feature | Status | Gap |
|---|---|---|
| Bank rules (auto-code) | 🟡 | Phase 5 categorisation rules |
| Spend / receive money (direct, not via document) | 🟡 | Expenses |
| Transfers between accounts | ✅ | |
| Live bank feeds | ❌ | Not available for LK banks — import only (accepted) |

### F. Controls & productivity
| Feature | Status | Gap |
|---|---|---|
| Role-based permissions per accounting action (approve, void, edit locked) | 🟡 | `accounting.view` only for most screens |
| Find & recode (bulk change account / tax / cost centre) | ❌ | |
| Global search (invoice no., amount, contact) | ❌ | |
| Multi-currency | ❌ | Out of scope (LKR only) |

---

## 3. Roadmap (each phase = one deployable zip + migrations + tests)

| Phase | Scope | Why first |
|---|---|---|
| **6.1 Audit trail & document history** | `audit_logs` table + `Auditable` trait on Order, OrderItem, Payment, SaleReturn, Purchase, PurchasePayment, Expense, Customer, Supplier; records user, IP, action, before→after per field. History tab on every document. Global Audit Trail report with filters + CSV. | Requested explicitly; everything after this is tracked from day one. |
| **6.2 Invoice accounting view & drill-down** | Invoice detail page (Xero-style): header, lines, tax breakdown, payments, returns / credits, balance, status, source chain (quotation → order → check → issue → invoice), journal tab (Dr/Cr lines from postings), history tab. Link every ledger / register / TB / P&L line to its source document. | Requested explicitly. |
| **6.3 VAT engine** | Tax rates per line (Standard / Zero / Exempt / No VAT), inclusive / exclusive, separate GL accounts (VAT Output 2310, SSCL Payable 2320, VAT Input 1165), input VAT on purchases, VAT return + SSCL return reports with drill-down, tax-invoice sequence gap report. | Correct VAT figures for a VAT-registered business. |
| **6.4 Receivables like Xero** | Payment terms, due dates, invoice status incl. Overdue, credit notes, void, overpayments, reminders, credit-limit warnings, invoice email / PDF link. | |
| **6.5 Payables like Xero** | Bill due dates, line tax, approval, supplier credit notes, batch payments. | |
| **6.6 Controls** | Fine-grained permissions, find & recode, global search. | |

### Rules for every phase
- Reuse `AccountingService::postOnce` / `reverseEvent`; never a second ledger.
- Only new transactions change; existing posted history is not rewritten (same rule as the SSCL / VAT change).
- Each phase ships with migrations, feature tests, a docs file and a deploy zip.

---

## 4. Implementation status (2026-11-20)

All six phases implemented; `tests/Feature/AccountingPhase6Test.php` — 21 tests, full suite 189 passing.

| Phase | Delivered | Main screens (`/accounting/phase6/...`) |
|---|---|---|
| 6.1 | `audit_logs` table, `Auditable` trait on 14 models, business events (invoice issued / edited / printed / voided, credit note, reminder, bill approved, recode), history on ERP order page | `audit-trail`, `audit-trail/document/{type}/{id}` |
| 6.2 | Invoice list with Xero statuses, invoice drill-down (lines, tax, source chain, payments, returns, credit notes, journal, history, ledger check), journal view, account transactions; P&L / TB / BS / account register link to source | `invoices`, `invoices/{id}`, `journal/{txn}`, `account-transactions` |
| 6.3 | GL 2310 VAT output, 2320 SSCL payable, 1165 VAT input; VAT treatment per invoice (standard / zero / exempt); input VAT on bills + reversal on purchase returns; VAT & SSCL return with drill-down + ledger check + CSV; tax invoice register with serial gap check | `vat-return`, `vat-return/{section}`, `tax-invoice-register` |
| 6.4 | Payment terms (customer) → due date → Overdue; credit notes (return documents + price adjustments, PDF, void); void invoice (keeps number, reverses journals, returns stock); SMS reminders; overdue ageing; credit-limit warning on Issue Invoice | `credit-notes`, `receivables/overdue` |
| 6.5 | Supplier payment terms → bill due date; bill statuses; optional bill approval (`DERMAS_REQUIRE_BILL_APPROVAL`); bill drill-down; payment run + bank CSV | `bills`, `bills/{id}`, `bills/payment-run` |
| 6.6 | 7 new permissions (`DERMAS_STRICT_ACCOUNTING_PERMISSIONS`), find & recode via reclassification journals, global search | `recode`, `search` |

### Deliberate limits
- VAT treatment is per invoice, not per line (apparel sales are single-rate); per-line rates can be added later on the same engine.
- Existing (pre-6.3) invoices keep their original postings; only new / re-synced documents use the split VAT / SSCL accounts.
- Live bank feeds are not available for Sri Lankan banks — statement import remains the route.
