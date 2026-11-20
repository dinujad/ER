<style>
.acct-ws-content {
    --acct-surface: #fff;
    --acct-muted: #526681;
    --acct-border: #d9e3ef;
    --acct-positive: #137654;
    --acct-negative: #a83b4c;
    --acct-warn: #9b521c;
    --acct-radius: 14px;
    --acct-shadow: 0 4px 16px rgba(15, 39, 68, .06);
    --acct-display: 'IBM Plex Sans', 'Source Sans 3', sans-serif;
}
.acct-dash-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    padding: clamp(16px, 3vw, 26px);
    margin-bottom: 18px;
    background: #fff;
    border: 1px solid var(--acct-border);
    border-inline-start: 4px solid var(--acct-accent);
    border-radius: var(--acct-radius);
    box-shadow: var(--acct-shadow);
}
.acct-dash-controls > div { flex: 1 1 130px; }
.acct-kpi-grid a.acct-kpi:hover { background: #f5f9ff; border-color: #91b5e5; }
.acct-kpi-grid a.acct-kpi:focus-visible { outline: 2px solid #4385e6; outline-offset: 2px; }
.acct-kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 190px), 1fr)); gap: 14px; margin-bottom: 1.1rem; }
.acct-kpi {
  background: var(--acct-surface); border: 1px solid var(--acct-border); border-radius: var(--acct-radius);
  padding: 18px 20px; box-shadow: var(--acct-shadow); position: relative; overflow: hidden;
  color: inherit; min-height: 132px;
}
.acct-kpi::before { display: none; }
.acct-kpi-top { display: flex; justify-content: space-between; align-items: center; gap: 8px; }
.acct-kpi-icon {
  width: 32px; height: 32px; border-radius: 10px; display: grid; place-items: center;
  background: #eef3ff; color: var(--acct-accent); flex-shrink: 0;
}
.acct-kpi-icon.green { background: #e8f8f1; color: var(--acct-positive); }
.acct-kpi-icon.orange { background: #fff1e7; color: var(--acct-warn); }
.acct-kpi-icon.purple { background: #f2eaff; color: #8151c7; }
.acct-kpi-label { font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: var(--acct-muted); font-weight: 750; }
.acct-kpi-value { font-family: var(--acct-display); font-weight: 800; font-size: 1.35rem; margin-top: 14px; color: var(--acct-ink); letter-spacing: -.4px; overflow-wrap: anywhere; }
.acct-kpi-meta { font-size: .75rem; color: var(--acct-muted); margin-top: 8px; }
.acct-kpi.positive .acct-kpi-value { color: var(--acct-positive); }
.acct-kpi.negative .acct-kpi-value { color: var(--acct-negative); }
.acct-panel {
  background: var(--acct-surface); border: 1px solid var(--acct-border); border-radius: var(--acct-radius);
  box-shadow: var(--acct-shadow); margin-bottom: 1rem;
}
.acct-panel-body { padding: 16px 18px; }
.acct-panel-header {
  padding: 12px 18px; border-bottom: 1px solid var(--acct-border);
  display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap;
}
.acct-panel-header h2 { font-size: 0.95rem; font-weight: 700; margin: 0; font-family: var(--acct-display); }

.acct-table-wrap { overflow-x: auto; border-radius: var(--acct-radius); border: 1px solid var(--acct-border); background: #fff; }
.acct-fin-table {
  margin: 0; font-size: .9rem; --bs-table-bg: transparent;
}
.acct-fin-table thead th {
  position: sticky; top: 0; z-index: 1;
  background: #f8fafc; color: #475569; font-size: .72rem; text-transform: uppercase;
  letter-spacing: .04em; font-weight: 700; border-bottom: 1px solid var(--acct-border);
  white-space: nowrap; padding: .7rem .85rem;
}
.acct-fin-table tbody td { padding: .65rem .85rem; vertical-align: middle; }
.acct-fin-table tbody tr:hover { background: #f8fafc; }
.acct-amt { font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }
.acct-amt.pos { color: var(--acct-positive); }
.acct-amt.neg { color: var(--acct-negative); }

.acct-empty, .acct-error, .acct-unavailable {
  text-align: center; padding: 2.5rem 1.25rem; border: 1px dashed var(--acct-border);
  border-radius: var(--acct-radius); background: #fff; color: var(--acct-muted);
}
.acct-empty i, .acct-error i, .acct-unavailable i { font-size: 1.75rem; display: block; margin-bottom: .5rem; opacity: .7; }
.acct-empty h3, .acct-error h3, .acct-unavailable h3 {
  font-family: var(--acct-display); font-size: 1.05rem; color: var(--acct-ink); margin: 0 0 .35rem;
}

.acct-skeleton { background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%); background-size: 200% 100%; animation: acctShimmer 1.2s infinite; border-radius: 8px; }
@keyframes acctShimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
.acct-skel-kpi { height: 88px; }
.acct-skel-row { height: 42px; margin-bottom: 8px; }


.acct-badge {
  display: inline-flex; align-items: center; gap: 4px; font-size: .72rem; font-weight: 700;
  padding: 3px 8px; border-radius: 999px; background: #f1f5f9; color: #475569;
}
.acct-badge.ok { background: #d1fae5; color: #065f46; }
.acct-badge.warn { background: #fef3c7; color: #92400e; }
.acct-badge.danger { background: #fee2e2; color: #991b1b; }
.acct-badge.info { background: #dbeafe; color: #1e40af; }

.acct-quick-actions { display: flex; flex-wrap: wrap; gap: 8px; }
.acct-quick-actions a {
  display: inline-flex; align-items: center; gap: 6px; padding: 8px 12px;
  border-radius: 999px; background: #fff; border: 1px solid var(--acct-border);
  color: var(--acct-ink); text-decoration: none; font-size: .85rem; font-weight: 600;
}
.acct-quick-actions a:hover { border-color: var(--acct-accent); color: var(--acct-accent); }

.acct-chart-card { min-height: 260px; }
.acct-chart-card canvas { max-height: 240px; }

.acct-dash-hero-kpis { grid-template-columns: repeat(4, minmax(0, 1fr)); }
@media (max-width: 1100px) { .acct-dash-hero-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 560px) { .acct-dash-hero-kpis { grid-template-columns: 1fr; } }
.acct-dash-eyebrow {
  color: var(--acct-accent); font-size: .7rem; letter-spacing: .16em; text-transform: uppercase;
  font-weight: 800; margin-bottom: .4rem;
}
.acct-dash-heading { font-family: var(--acct-display); font-size: 1.75rem; font-weight: 790; letter-spacing: -.6px; margin: 0 0 .35rem; color: var(--acct-ink); }
.acct-dash-sub { margin: 0; color: var(--acct-muted); font-size: .85rem; }
.acct-dash-controls { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
.acct-dash-middle, .acct-dash-bottom {
  display: grid; grid-template-columns: minmax(0, 1.65fr) minmax(280px, 1fr); gap: 16px; margin-bottom: 16px;
}
.acct-dash-cash-total { font-family: var(--acct-display); font-size: 1.65rem; font-weight: 830; letter-spacing: -.5px; margin: 8px 0 2px; }
.acct-dash-cash-list { margin-top: 8px; border-top: 1px solid var(--acct-border); }
.acct-dash-cash-row {
  display: flex; justify-content: space-between; align-items: center; gap: 12px;
  padding: 14px 0; border-bottom: 1px solid #eff2f7; font-size: .85rem;
}
.acct-dash-cash-row:last-child { border-bottom: 0; }
.acct-dash-cash-left { display: flex; gap: 12px; align-items: center; min-width: 0; }
.acct-dash-round {
  width: 33px; height: 33px; border-radius: 10px; display: grid; place-items: center;
  background: #eff4fc; color: #4e6aa9; flex-shrink: 0;
}
.acct-dash-cash-row small { display: block; color: #94a0b1; margin-top: 2px; font-size: .7rem; }
.acct-dash-actions {
  display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding: 4px 0 8px;
}
.acct-dash-actions a {
  border: 1px solid var(--acct-border); background: #fff; border-radius: 11px; padding: 12px 10px;
  display: flex; gap: 10px; align-items: center; color: #42536b; font-size: .78rem; font-weight: 700;
  text-decoration: none; transition: border-color .15s, background .15s, transform .15s;
}
.acct-dash-actions a:hover { border-color: #95acf4; background: #f6f9ff; transform: translateY(-1px); color: var(--acct-ink); }
.acct-dash-actions a i { color: var(--acct-accent); font-size: 1.05rem; }
.acct-dash-reports { display: grid; gap: 6px; }
.acct-dash-reports a {
  display: flex; align-items: center; gap: 8px; padding: 9px 11px; border-radius: 9px;
  border: 1px solid var(--acct-border); color: var(--acct-ink); text-decoration: none; font-size: .8rem; font-weight: 650;
}
.acct-dash-reports a:hover { border-color: var(--acct-accent); background: #f6f9ff; }
.acct-dash-status {
  display: inline-flex; padding: 4px 8px; font-weight: 760; font-size: .68rem; border-radius: 6px;
  background: #e8f8ef; color: var(--acct-positive);
}
.acct-dash-legend { display: flex; gap: 16px; align-items: center; margin: 0 0 8px; font-size: .75rem; color: #6f7c91; }
.acct-dash-legend i { width: 8px; height: 8px; display: inline-block; border-radius: 50%; margin-right: 5px; }
@media (max-width: 1100px) {
  .acct-dash-middle, .acct-dash-bottom { grid-template-columns: 1fr; }
}
@media (max-width: 600px) {
  .acct-dash-heading { font-size: 1.35rem; }
  .acct-kpi-value { font-size: 1.1rem; }
  .acct-dash-actions { grid-template-columns: 1fr; }
}
</style>
