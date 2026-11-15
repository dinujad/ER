# Customer/Supplier Advances & Allocations

**Service:** `PaymentAllocationService`  
**UI:** `phase2/ar/payment_allocation.blade.php` (unallocated + AR reconcile banner)

## Enhancements

- `customerUnallocated` / `supplierUnallocated`
- Block allocation of bounced cheques
- Block allocation to cancelled/quotation orders
- Over-allocation still rejected
- Leftover payment amount treated as unapplied/advance (visible; no second ledger)

## Test

`test_customer_unallocated_advance_is_visible` — pay 150 allocate 100 → unallocated 50.
