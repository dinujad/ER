# AR/AP ↔ GL Reconciliation

**Helpers:** `StatementPdfService::arControlReconciliation` / `apControlReconciliation`  
**UI surface:** AR payment allocation page shows AR control vs subledger summary  

## Method

- **GL:** Account register closing for GL 1200 (AR) / 2000 (AP) through as-of date.
- **AR subledger:** Sum max(0, order total − effective payments) excluding cancelled/quotation/draft; exclude bounced cheques and refunds when columns exist.
- **AP subledger:** Sum max(0, bill total − payments) excluding cancelled/draft.

## Tested scenario

Credit sale 1000 + cash receipt 400 → GL AR 600 = subledger 600 (difference 0.00).  
File: `AccountingPhase21StabilityTest::test_ar_subledger_matches_gl_for_credit_sale_partial_payment`

## Notes

Valid timing differences (unposted documents, advances in non-AR accounts, opening balances) are documented in `notes[]` — historical journals are not silently rewritten.
