# 03 — Module integration matrix (Phase 1)

| Source module | Model / table | Trigger | DR | CR | Types | Reversal | Test coverage | Status |
|---------------|---------------|---------|----|----|-------|----------|---------------|--------|
| Wholesale / POS sales | `orders` | OrderObserver → SalesAccountingService when `completed`/`paid`/`credit` | AR 1200 | Sales 4000 | `sale` | reverseEvent on delete/status leave | AccountingServiceTest (partial) | OK (gate by design) |
| Sale COGS | `order_items` | same | COGS 5000 | FG 1330 | `sale_cogs` | same | partial | OK |
| Customer payment | `payments` | PaymentObserver | Cash/Bank | AR | `customer_payment` | reverseEvent | yes | OK |
| Customer refund | `payments` | PaymentObserver | AR | Cash/Bank | `customer_refund` | reverseEvent | cheque→Bank tested | OK |
| Cheque receive | `payments` | ChequePaymentLifecycleService | PDC 1150 | AR | `cheque_receipt` | bounce reverses | settlement tests | OK |
| Cheque clear | `payments` | lifecycle | Bank | PDC | `cheque_clearance` | reverse | partial | OK |
| Sale return | `sale_returns` | SaleReturnObserver | 4010 | AR | `sale_return` | reverse | backfill | OK |
| Sale return COGS | items | same | FG | COGS | `sale_return_cogs` | reverse | backfill | OK |
| Purchase confirm | `purchases` | PurchaseObserver | Raw 1310 | AP 2000 | `purchase` | reverse | backfill | OK |
| Purchase payment | `purchase_payments` | PurchasePaymentObserver | AP | Cash/Bank | `purchase_payment` | reverse | import fixed | OK |
| Supplier refund | purchase_payments | same | Cash/Bank | AP | `supplier_refund` | reverse | — | OK |
| Purchase return | `purchase_returns` | PurchaseReturnService | AP | Raw 1310 | `purchase_return` | observer on delete | — | OK |
| Expense | `expenses` | ExpenseObserver | 6000–6900 | Cash/Bank | `expense` | reverse | mapping test | OK |
| Salesperson expense | `sales_person_expenses` | SPE observer | 6300/6900/6000 | Cash | `sales_person_expense` | reverse | — | OK |
| Vehicle run | `vehicle_daily_run_items` | Vehicle observer | 6300 | Cash | `vehicle_run_expense` | reverse | — | OK |
| Payroll accrual | `payroll_runs` | Payroll observers | 6100 | 2100 | `payroll_accrual` | reverse | — | OK |
| Payroll payment | items | same | 2100 | Cash | `payroll_payment` | reverse | — | OK |
| Material issue | `material_issues` | InventoryAccountingService | WIP | Raw | `manufacturing_material_issue` | reverse | — | OK |
| Mfg additional cost | mfg costs | same | WIP | Cash | `manufacturing_additional_cost` | reverse | — | OK |
| Stock adj / FG | stock ledgers / movements | same | FG/Raw ↔ equity/other | | adjustments / FG / overhead | reverse | — | OK |
| Quotations / drafts | orders not recognized | — | — | — | none | — | — | OK (no revenue) |
| Expense clear-all | route | **disabled 410** | — | — | — | N/A | ActionPermissionsTest | **Fixed P0** |

## Known intentional gaps (not Phase 1 bugs)

- Orders in `pending` / `processing` / `afterchecking` do not post sale/COGS until completed.
- Cheque *deposit* (before clear) has no separate JE (receipt already booked PDC).
- Payroll posts net salary only (no statutory tax split).
- Mfg additional cost / SPE / vehicle / payroll payment credit Cash only (not Bank/AP).
