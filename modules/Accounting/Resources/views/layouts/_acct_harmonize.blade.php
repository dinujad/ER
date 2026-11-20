{{-- Loaded after the 5.5 design system: maps plain Bootstrap pages (cards, stat cards, filter forms,
     tables, tabs) onto the same tokens so every accounting page looks like the 5.5 reports. --}}
<style>
.acct-ws-content .card {
  border: 1px solid var(--acct-border); border-radius: var(--acct-radius);
  box-shadow: var(--acct-shadow); background: var(--acct-surface); overflow: hidden;
}
.acct-ws-content .card > .card-header {
  background: #fff; border-bottom: 1px solid var(--acct-border); padding: 12px 18px;
  font-family: var(--acct-display);
}
.acct-ws-content .card > .card-header strong { font-weight: 700; color: var(--acct-ink); }
.acct-ws-content .card > .card-body { padding: 16px 18px; }
.acct-ws-content .card > .card-body.p-0 { padding: 0; }

/* stat cards: <div class="card"><div class="card-body py-2"><div class="small text-muted">Label</div><div class="fs-5 fw-bold">Value</div> */
.acct-ws-content .card > .card-body.py-2 { padding: 16px 18px !important; }
.acct-ws-content .card > .card-body.py-2 > .small.text-muted:first-child {
  font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; font-weight: 750; color: var(--acct-muted);
}
.acct-ws-content .card > .card-body.py-2 > .fs-5.fw-bold,
.acct-ws-content .card > .card-body.py-2 > .fs-4.fw-bold {
  font-family: var(--acct-display); font-weight: 800; font-size: 1.3rem; margin-top: 8px; color: var(--acct-ink); letter-spacing: -.3px;
}
.acct-ws-content .card.border-danger { border-color: #f3c2c8 !important; }
.acct-ws-content .card.border-success { border-color: #bfe8d8 !important; }
.acct-ws-content .card.border-primary { border-color: #c9d7fb !important; }

/* filter forms directly in the page body look like the 5.5 toolbar */
.acct-ws-content .acct-page > form[method="GET"],
.acct-ws-content .acct-page > form[method="get"] {
  background: var(--acct-surface); border: 1px solid var(--acct-border); border-radius: var(--acct-radius);
  padding: 14px 16px; margin: 0 0 1.1rem; box-shadow: var(--acct-shadow);
}
.acct-ws-content .acct-page > form[method="GET"].row,
.acct-ws-content .acct-page > form[method="get"].row { margin-left: 0; margin-right: 0; }
.acct-ws-content .acct-page form .form-label.small,
.acct-ws-content .acct-page form label.small {
  font-size: .72rem; font-weight: 700; color: var(--acct-muted); text-transform: uppercase; letter-spacing: .03em;
}

/* tables */
.acct-ws-content .table-responsive {
  border: 1px solid var(--acct-border); border-radius: var(--acct-radius); background: #fff; margin-bottom: 1rem;
}
.acct-ws-content .card .table-responsive { border: 0; border-radius: 0; margin-bottom: 0; }
.acct-ws-content .table-responsive > .table { margin-bottom: 0; }
.acct-ws-content .table > thead > tr > th,
.acct-ws-content .table > thead.table-light > tr > th {
  background: #f7f9fc; color: var(--acct-muted); font-size: .72rem; font-weight: 700;
  text-transform: uppercase; letter-spacing: .04em; border-bottom: 1px solid var(--acct-border); white-space: nowrap;
}
.acct-ws-content .table > :is(tbody, tfoot) > tr > td { border-color: #eef2f7; vertical-align: middle; }
.acct-ws-content .table-hover > tbody > tr:hover > * { --bs-table-bg-state: #f4f7ff; }
.acct-ws-content .font-monospace { font-family: var(--acct-font) !important; font-variant-numeric: tabular-nums; font-weight: 600; }

/* tabs & pills */
.acct-ws-content .nav-tabs { border-bottom: 1px solid var(--acct-border); gap: 4px; }
.acct-ws-content .nav-tabs .nav-link {
  border: 0; border-bottom: 2px solid transparent; color: var(--acct-muted); font-weight: 700; padding: .55rem .9rem;
}
.acct-ws-content .nav-tabs .nav-link:hover { color: var(--acct-ink); }
.acct-ws-content .nav-tabs .nav-link.active { color: var(--acct-accent); border-bottom-color: var(--acct-accent); background: transparent; }
.acct-ws-content .nav-pills .nav-link { border-radius: 999px; font-weight: 700; color: var(--acct-muted); }
.acct-ws-content .nav-pills .nav-link.active { background: var(--acct-accent); color: #fff; }

/* alerts, badges, inputs, buttons */
.acct-ws-content .alert { border-radius: 12px; border: 0; box-shadow: var(--acct-shadow); }
.acct-ws-content .badge { border-radius: 999px; font-weight: 700; padding: .35em .65em; }
.acct-ws-content :is(.form-control, .form-select) { border-radius: var(--acct-radius-sm); border-color: var(--acct-border); }
.acct-ws-content :is(.form-control, .form-select):focus { box-shadow: var(--acct-focus); border-color: var(--acct-accent); }
.acct-workspace .btn { border-radius: var(--acct-radius-sm); font-weight: 600; }
.acct-workspace .btn-primary { background: var(--acct-accent); border-color: var(--acct-accent); }
.acct-workspace .btn-primary:hover { background: #2a52d4; border-color: #2a52d4; }
.acct-workspace .btn-xs { padding: .18rem .6rem; font-size: .75rem; border-radius: 7px; line-height: 1.4; }
.acct-ws-content .acct-jump { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 14px; }
.acct-ws-content .acct-jump .btn { border-radius: 999px; }
.acct-ws-content .acct-actions { display: inline-flex; flex-wrap: wrap; gap: 6px; align-items: center; justify-content: flex-end; }
.acct-ws-content .list-group-item-action { border-radius: 10px; margin-bottom: 4px; border: 1px solid var(--acct-border); }
.acct-ws-content pre { background: #f7f9fc; border-radius: 10px; padding: 10px 12px; }

/* definition-list pages (simple KPI / summary pages) */
.acct-ws-content .acct-page > dl.row {
  background: var(--acct-surface); border: 1px solid var(--acct-border); border-radius: var(--acct-radius);
  padding: 16px 18px; margin: 0 0 1rem; box-shadow: var(--acct-shadow);
}
</style>
