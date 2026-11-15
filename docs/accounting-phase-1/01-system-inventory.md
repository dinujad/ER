# 01 — System inventory (Phase 1)

**Branch:** `fix/accounting-phase-1-audit`  
**Scope:** Existing DERMAS / E-Media ERP accounting (LKR only). No Phase 2–5 features.

## Core posting engine

| Path | Role |
|------|------|
| `app/Services/AccountingService.php` | ERP auto-post: `normalizeMoney`, `postEntry`, `postOnce`, `reverseEvent`, `reverseEntry`, `replaceEntry`, `settlementAccountId`, `collapseDuplicateJournals`, `accountId`, `expenseAccountId` |
| `app/Services/SalesAccountingService.php` | Order sale+COGS, customer payment/refund, sale returns |
| `app/Services/InventoryAccountingService.php` | Material issue, mfg costs, FG / material adjustments |
| `app/Services/ChequePaymentLifecycleService.php` | Cheque receipt / clearance / bounce |
| `app/Services/PurchaseReturnService.php` | Purchase return stock + AP/raw materials JE |
| `modules/Accounting/Services/AccountingService.php` | **Separate** module service for manual UI journals (not ERP `postOnce`) |
| `app/Console/Commands/BackfillAccountingPostingsCommand.php` | `accounting:backfill` |
| `app/Console/Commands/DiagnoseAccountingIntegrityCommand.php` | `accounting:diagnose` (read-only) |

## Observers

Registered in `app/Providers/AppServiceProvider.php` (and `#[ObservedBy]` on Order / SaleReturn):

Payment, Expense, Purchase, PurchasePayment, PurchaseReturn, SalesPersonExpense, PayrollRun, PayrollRunItem, MaterialIssue, MaterialStockMovement, ManufacturingJobAdditionalCost, StockLedger, VehicleDailyRunItem, Order, SaleReturn.

## Accounting module UI

Prefix `/accounting` and `/report/accounting/*` via `modules/Accounting/Http/routes.php`.

Controllers: JournalEntry, ChartOfAccount, AccountingTransaction (ERP ledger tabs), Accounting (legacy reports + transfers), Report, Reconcile, Budget, Dashboard, Settings, Install, Media, Data.

## Schema

- `chart_of_accounts`, `journal_entries`, `accounting_postings` (event_key unique)
- JE source columns: `source_type`, `source_id`, `payment_id`, `order_id`
- Seed: `database/seeders/ChartOfAccountsSeeder.php`

## Permissions

`config/permissions.php`: `accounting.view|manage|edit|delete`  
Middleware: `AccountingAccess`, `AccountingSessionDefaults`  
Gate bridge in `AppServiceProvider` for Spatie-style `@can('accounting.*')`.

## Tests (existing + Phase 1)

- `tests/Feature/AccountingServiceTest.php`
- `tests/Feature/ActionPermissionsTest.php` (clear-all)
- `tests/Unit/OperationsDataResetTest.php`
