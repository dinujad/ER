# Anomaly Detection

`AnomalyDetectionService` + `accounting:process-anomalies` daily 02:00.

Deterministic rules with per-business thresholds. Alerts idempotent via `idempotency_key`.

Statuses: new / under_review / resolved / dismissed. No auto reverse/delete.
