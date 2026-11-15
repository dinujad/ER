# Cheque Lifecycle Verification

**Service:** `ChequePaymentLifecycleService`  
Received → DR PDC / CR AR; Cleared → DR Bank / CR PDC; Bounce reverses journals.

## Test

`test_cheque_lifecycle_received_cleared_idempotent`

- onChequeReceived posts once (idempotent on retry)
- markDeposited → markCleared
- Duplicate clear rejected

## Status

FULLY IMPLEMENTED & TESTED at service/Eloquent level. HTTP controller E2E still environment-dependent.
