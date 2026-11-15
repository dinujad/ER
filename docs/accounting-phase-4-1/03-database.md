# Phase 4.1 — Database

No new migrations. Existing Phase 4 tables (`acct_financial_years`, `acct_year_end_sessions`, etc.) used as-is.

`php artisan migrate --pretend` reviewed against local MySQL — pending migrations are Phase 3/4 baseline creates only; **staging NOT VERIFIED** in this pass.