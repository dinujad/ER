# Phase 4.1 — MySQL / Staging

- `php artisan migrate --pretend` executed locally against configured MySQL — shows Phase 3/4 table creates where not yet applied; **no 4.1-specific DDL**.
- **Staging NOT VERIFIED** — production year-end reopen and multi-tenant scope should be validated on a staging copy with real `business.id` sessions.