# COA Hierarchy Validation

**Service:** `app/Services/Accounting/ChartOfAccountHierarchyService.php`  
**Wired:** `ChartOfAccountController@store` / `@update`  
**Existing:** delete blocked if journals/children exist  

## Rules enforced

- No self-parent
- No circular ancestor chain (A←B←A, multi-level)
- Parent must exist
- Cross-business parent rejected (non-zero different business_id)
- Shared COA (business_id=0) may parent business accounts

## Tests

`AccountingPhase21StabilityTest`: self-parent, two-level cycle, cross-business, valid parent.
