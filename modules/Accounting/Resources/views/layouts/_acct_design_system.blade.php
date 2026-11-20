{{-- E-Media Accounting Design System (Phase 5.5) — Bootstrap 5 compatible tokens --}}
<style>
:root {
  --acct-navy: #101f35;
  --acct-navy-2: #1b2e49;
  --acct-navy-3: #253952;
  --acct-accent: #3563ed;
  --acct-accent-soft: #e9f0ff;
  --acct-bg: #f5f7fb;
  --acct-surface: #ffffff;
  --acct-ink: #17243b;
  --acct-muted: #738099;
  --acct-border: #e7ebf3;
  --acct-positive: #139b72;
  --acct-negative: #dc6472;
  --acct-warn: #d48236;
  --acct-radius: 16px;
  --acct-radius-sm: 9px;
  --acct-shadow: 0 7px 24px rgba(27, 46, 78, .045);
  --acct-sidebar-w: 292px;
  --acct-sidebar-w-collapsed: 76px;
  --acct-font: 'Source Sans 3', system-ui, sans-serif;
  --acct-display: 'IBM Plex Sans', 'Source Sans 3', sans-serif;
  --acct-focus: 0 0 0 3px rgba(37, 99, 235, .35);
}

@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after { animation-duration: .01ms !important; transition-duration: .01ms !important; }
}

body.acct-workspace {
  --acct-sidebar: var(--acct-navy);
  --acct-sidebar-2: var(--acct-navy-2);
  --acct-bg: #f1f5f9;
  --acct-ink: #0f172a;
  font-family: var(--acct-font);
  background: var(--acct-bg);
}

/* —— Shell —— */
.acct-ws-shell { display: flex; min-height: 100vh; }
.acct-ws-sidebar {
  width: var(--acct-sidebar-w);
  flex-shrink: 0;
  background: linear-gradient(185deg, var(--acct-navy) 0%, var(--acct-navy-2) 55%, var(--acct-navy-3) 100%);
  color: #fff;
  display: flex; flex-direction: column;
  position: sticky; top: 0; height: 100vh;
  overflow: hidden;
  z-index: 1040;
  transition: width .2s ease;
}
body.acct-ws-collapsed .acct-ws-sidebar {
  width: var(--acct-sidebar-w-collapsed);
}
.acct-ws-brand {
  display: flex; gap: 12px; align-items: center;
  padding: 16px 14px; border-bottom: 1px solid rgba(255,255,255,.08);
}
.acct-ws-brand-mark {
  width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;
  background: rgba(53,99,237,.45); display: grid; place-items: center; font-size: 1.15rem;
}
.acct-ws-brand-title { font-family: var(--acct-display); font-weight: 700; font-size: 1.05rem; line-height: 1.2; }
.acct-ws-brand-sub { font-size: .72rem; opacity: .65; }
body.acct-ws-collapsed .acct-ws-brand {
  justify-content: center; padding: 14px 8px;
}
body.acct-ws-collapsed .acct-ws-brand-text { display: none; }
body.acct-ws-collapsed .acct-ws-brand-mark { width: 36px; height: 36px; }

.acct-ws-nav-search { padding: 10px 12px 6px; }
.acct-ws-nav-search input {
  width: 100%; border: 1px solid rgba(255,255,255,.12); background: rgba(0,0,0,.2);
  color: #fff; border-radius: 8px; padding: 8px 10px; font-size: .85rem;
}
.acct-ws-nav-search input::placeholder { color: rgba(255,255,255,.45); }
.acct-ws-nav-search input:focus { outline: none; box-shadow: var(--acct-focus); border-color: var(--acct-accent); }
body.acct-ws-collapsed .acct-ws-nav-search { display: none; }

.acct-ws-nav {
  padding: 6px 8px 20px; flex: 1; overflow-y: auto; overflow-x: hidden;
  scrollbar-width: thin;
  scrollbar-color: rgba(255,255,255,.22) transparent;
}
.acct-ws-nav::-webkit-scrollbar { width: 6px; }
.acct-ws-nav::-webkit-scrollbar-track { background: transparent; }
.acct-ws-nav::-webkit-scrollbar-thumb {
  background: rgba(255,255,255,.22);
  border-radius: 999px;
}
.acct-ws-nav::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,.35); }
body.acct-ws-collapsed .acct-ws-nav { padding: 8px 6px 16px; }
.acct-ws-group { margin-bottom: 4px; position: relative; }
.acct-ws-group-btn {
  width: 100%; display: flex; align-items: center; justify-content: space-between; gap: 8px;
  background: transparent; border: 0; color: rgba(255,255,255,.55);
  font-size: .68rem; letter-spacing: .07em; text-transform: uppercase; font-weight: 700;
  padding: 10px 10px 6px; cursor: pointer; text-align: left;
}
.acct-ws-group-btn .group-ico {
  display: none; font-size: 1.15rem; line-height: 1; color: rgba(255,255,255,.9);
}
.acct-ws-group-btn:hover, .acct-ws-group-btn:focus-visible { color: #fff; outline: none; }
.acct-ws-group-btn .chev { transition: transform .15s ease; opacity: .7; }
.acct-ws-group.open .acct-ws-group-btn .chev { transform: rotate(90deg); }
.acct-ws-group-body { display: none; padding: 0 2px 6px; }
.acct-ws-group.open .acct-ws-group-body { display: block; }

/* Compact / collapsed: icon rail only */
body.acct-ws-collapsed .acct-ws-group-btn {
  justify-content: center; padding: 11px 0; margin: 2px 0;
  border-radius: 10px; color: #fff; font-size: 1rem; letter-spacing: 0; text-transform: none;
}
body.acct-ws-collapsed .acct-ws-group-btn:hover,
body.acct-ws-collapsed .acct-ws-group.open-collapsed .acct-ws-group-btn {
  background: rgba(255,255,255,.12);
}
body.acct-ws-collapsed .acct-ws-group-btn .group-ico { display: inline-block; }
body.acct-ws-collapsed .acct-ws-group-btn span.label,
body.acct-ws-collapsed .acct-ws-group-btn .chev { display: none !important; }
body.acct-ws-collapsed .acct-ws-group-body { display: none !important; }
body.acct-ws-collapsed .acct-ws-group.open-collapsed .acct-ws-group-body {
  display: block !important;
  position: fixed;
  left: calc(var(--acct-sidebar-w-collapsed) - 4px);
  top: 80px; /* JS updates this */
  background: #1b2e49; min-width: 230px; max-height: min(70vh, 420px); overflow: auto;
  border-radius: 12px; padding: 8px; border: 1px solid rgba(255,255,255,.08);
  box-shadow: 0 12px 32px rgba(9, 26, 50, .35); z-index: 1060;
}
/* Invisible bridge so cursor can move from icon → submenu without leaving the group */
body.acct-ws-collapsed .acct-ws-group.open-collapsed .acct-ws-group-body::before {
  content: '';
  position: absolute;
  top: 0; bottom: 0;
  left: -20px;
  width: 24px;
}
body.acct-ws-collapsed .acct-ws-group.open-collapsed .acct-ws-group-body .acct-ws-link {
  justify-content: flex-start; padding: 8px 11px;
}
body.acct-ws-collapsed .acct-ws-group.open-collapsed .acct-ws-group-body .acct-ws-link span {
  display: inline !important;
}

.acct-ws-link {
  display: flex; align-items: center; gap: 10px;
  color: rgba(255,255,255,.86); text-decoration: none;
  padding: 8px 11px; border-radius: 8px; font-size: .9rem; margin-bottom: 1px;
}
.acct-ws-link:hover { background: rgba(255,255,255,.08); color: #fff; }
.acct-ws-link:focus-visible { outline: none; box-shadow: var(--acct-focus); }
.acct-ws-link.active { background: #fff; color: var(--acct-navy); font-weight: 600; }
.acct-ws-link i { width: 1.15rem; text-align: center; flex-shrink: 0; }
body.acct-ws-collapsed .acct-ws-link span { display: none; }
body.acct-ws-collapsed .acct-ws-link { justify-content: center; padding: 10px; }

.acct-ws-sidebar-foot {
  padding: 10px; border-top: 1px solid rgba(255,255,255,.08); display: grid; gap: 8px;
  flex-shrink: 0;
}
.acct-ws-collapse-btn, .acct-ws-back {
  display: flex; align-items: center; justify-content: center; gap: 8px;
  color: #fff; text-decoration: none; border: 0; cursor: pointer;
  padding: 10px 12px; border-radius: 8px; background: rgba(255,255,255,.1);
  font-weight: 600; font-size: .85rem; width: 100%;
  white-space: nowrap; overflow: hidden;
}
.acct-ws-collapse-btn i, .acct-ws-back i { flex-shrink: 0; font-size: 1.1rem; }
.acct-ws-back:hover, .acct-ws-collapse-btn:hover { background: rgba(255,255,255,.18); color: #fff; }
body.acct-ws-collapsed .acct-ws-sidebar-foot {
  padding: 10px 8px; justify-items: center;
}
body.acct-ws-collapsed .acct-ws-collapse-btn,
body.acct-ws-collapsed .acct-ws-back {
  width: 44px; height: 44px; padding: 0; gap: 0; border-radius: 11px;
}
body.acct-ws-collapsed .acct-ws-collapse-btn span,
body.acct-ws-collapsed .acct-ws-back span { display: none !important; }

.acct-ws-main { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.acct-ws-header {
  background: rgba(255,255,255,.92); backdrop-filter: blur(8px);
  border-bottom: 1px solid var(--acct-border);
  padding: 12px 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
  position: sticky; top: 0; z-index: 1030;
}
.acct-ws-title { font-family: var(--acct-display); font-weight: 700; font-size: 1.08rem; margin: 0; }
.acct-ws-chip {
  font-size: .72rem; font-weight: 600; background: #f1f5f9; color: #475569;
  border-radius: 999px; padding: 4px 10px; border: 1px solid var(--acct-border);
}
.acct-ws-chip.accent { background: var(--acct-accent-soft); color: #1d4ed8; border-color: #bfdbfe; }
.acct-ws-content { padding: 20px 22px 48px; max-width: 1440px; width: 100%; margin: 0 auto; }
.acct-ws-menu-btn {
  display: none; border: 1px solid var(--acct-border); background: #fff; border-radius: 8px;
  width: 40px; height: 40px; align-items: center; justify-content: center;
}
.acct-ws-backdrop { display: none; }

/* —— Page primitives —— */
.acct-page-header { margin-bottom: 1.25rem; }
.acct-page-header h1, .acct-page-title {
  font-family: var(--acct-display); font-weight: 700; font-size: 1.35rem; margin: 0 0 .25rem;
}
.acct-page-sub { color: var(--acct-muted); font-size: .9rem; margin: 0; }
.acct-toolbar {
  display: flex; flex-wrap: wrap; gap: .75rem; align-items: flex-end;
  background: var(--acct-surface); border: 1px solid var(--acct-border);
  border-radius: var(--acct-radius); padding: 14px 16px; margin-bottom: 1.1rem;
  box-shadow: var(--acct-shadow);
}
.acct-toolbar .form-label { font-size: .75rem; font-weight: 600; color: var(--acct-muted); margin-bottom: .2rem; }
.acct-kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 1.1rem; }
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
.acct-kpi-value { font-family: var(--acct-display); font-weight: 800; font-size: 1.35rem; margin-top: 14px; color: var(--acct-ink); letter-spacing: -.4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.acct-kpi-meta { font-size: .75rem; color: var(--acct-muted); margin-top: 8px; }
.acct-kpi.positive .acct-kpi-value { color: var(--acct-positive); }
.acct-kpi.negative .acct-kpi-value { color: var(--acct-negative); }
.acct-card, .acct-panel {
  background: var(--acct-surface); border: 1px solid var(--acct-border); border-radius: var(--acct-radius);
  box-shadow: var(--acct-shadow); margin-bottom: 1rem;
}
.acct-card-body, .acct-panel-body { padding: 16px 18px; }
.acct-card-header, .acct-panel-header {
  padding: 12px 18px; border-bottom: 1px solid var(--acct-border);
  display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap;
}
.acct-card-header h2, .acct-panel-header h2 { font-size: 0.95rem; font-weight: 700; margin: 0; font-family: var(--acct-display); }

.acct-table-wrap { overflow-x: auto; border-radius: var(--acct-radius); border: 1px solid var(--acct-border); background: #fff; }
.acct-workspace .table, .acct-fin-table {
  margin: 0; font-size: .9rem; --bs-table-bg: transparent;
}
.acct-workspace .table thead th, .acct-fin-table thead th {
  position: sticky; top: 0; z-index: 1;
  background: #f8fafc; color: #475569; font-size: .72rem; text-transform: uppercase;
  letter-spacing: .04em; font-weight: 700; border-bottom: 1px solid var(--acct-border);
  white-space: nowrap; padding: .7rem .85rem;
}
.acct-workspace .table tbody td, .acct-fin-table tbody td { padding: .65rem .85rem; vertical-align: middle; }
.acct-workspace .table tbody tr:hover, .acct-fin-table tbody tr:hover { background: #f8fafc; }
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

.acct-workspace .form-control, .acct-workspace .form-select {
  border-radius: var(--acct-radius-sm); border-color: var(--acct-border);
}
.acct-workspace .form-control:focus, .acct-workspace .form-select:focus {
  border-color: var(--acct-accent); box-shadow: var(--acct-focus);
}
.acct-workspace .btn { border-radius: var(--acct-radius-sm); font-weight: 600; }
.acct-workspace .btn-primary { background: var(--acct-accent); border-color: var(--acct-accent); }
.acct-workspace .alert { border-radius: var(--acct-radius-sm); }

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

/* —— Dashboard concept layout —— */
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

@media (max-width: 991.98px) {
  .acct-ws-sidebar {
    position: fixed; left: 0; top: 0; transform: translateX(-105%);
    transition: transform .2s ease; box-shadow: 8px 0 24px rgba(0,0,0,.25);
    width: var(--acct-sidebar-w) !important;
  }
  body.acct-ws-open .acct-ws-sidebar { transform: translateX(0); }
  .acct-ws-menu-btn { display: inline-flex; }
  .acct-ws-backdrop {
    display: none; position: fixed; inset: 0; background: rgba(15,23,42,.45); z-index: 1035;
  }
  body.acct-ws-open .acct-ws-backdrop { display: block; }
  body.acct-ws-collapsed .acct-ws-sidebar { width: var(--acct-sidebar-w) !important; overflow: hidden; }
  body.acct-ws-collapsed .acct-ws-brand { justify-content: flex-start; padding: 16px 14px; }
  body.acct-ws-collapsed .acct-ws-brand-text,
  body.acct-ws-collapsed .acct-ws-nav-search,
  body.acct-ws-collapsed .acct-ws-link span,
  body.acct-ws-collapsed .acct-ws-group-btn span.label,
  body.acct-ws-collapsed .acct-ws-group-btn .chev,
  body.acct-ws-collapsed .acct-ws-collapse-btn span,
  body.acct-ws-collapsed .acct-ws-back span { display: initial !important; }
  body.acct-ws-collapsed .acct-ws-group-btn .group-ico { display: none; }
  body.acct-ws-collapsed .acct-ws-group-btn { justify-content: space-between; padding: 10px 10px 6px; }
  body.acct-ws-collapsed .acct-ws-link { justify-content: flex-start; }
  body.acct-ws-collapsed .acct-ws-collapse-btn,
  body.acct-ws-collapsed .acct-ws-back { width: 100%; height: auto; padding: 10px 12px; gap: 8px; }
}
@media print {
  .acct-ws-sidebar, .acct-ws-header, .acct-ws-backdrop, .acct-ws-collapse-btn, .acct-adv-customize, .acct-adv-toolbar-actions { display: none !important; }
  .acct-ws-content { max-width: none; padding: 0; }
}

/* —— Advanced reports (QB-inspired) —— */
.acct-adv-toolbar {
  display: flex; flex-wrap: wrap; justify-content: space-between; gap: 12px;
  background: #fff; border: 1px solid var(--acct-border); border-radius: var(--acct-radius);
  padding: 12px 14px; margin-bottom: 1rem; box-shadow: var(--acct-shadow);
}
.acct-adv-toolbar-main { display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; }
.acct-adv-toolbar-actions { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
.acct-adv-toolbar .form-label { font-size: .72rem; font-weight: 700; color: var(--acct-muted); margin-bottom: .15rem; }
.acct-adv-stage { display: grid; grid-template-columns: 1fr; gap: 12px; align-items: start; }
.acct-adv-stage:has([data-adv-customize]:not([hidden])) { grid-template-columns: minmax(0, 1fr) 280px; }
.acct-adv-customize {
  background: #fff; border: 1px solid var(--acct-border); border-radius: var(--acct-radius);
  box-shadow: var(--acct-shadow); position: sticky; top: 72px;
}
.acct-adv-customize-head {
  display: flex; justify-content: space-between; align-items: center;
  padding: 10px 12px; border-bottom: 1px solid var(--acct-border);
}
.acct-adv-customize-body { padding: 12px; }
.acct-swatch { display: inline-block; width: 10px; height: 10px; border-radius: 2px; margin-right: 8px; }
.acct-adv-group-btn {
  border: 0; background: transparent; font-weight: 700; padding: 0; color: inherit; cursor: pointer;
}
.acct-adv-group-btn.collapsed .bi-caret-down-fill { transform: rotate(-90deg); }
.acct-adv-group-btn .bi { transition: transform .15s ease; display: inline-block; margin-right: 4px; }
.acct-adv-neg-red .acct-amt.neg, .acct-adv-neg-red tr[data-amount^="-"] .acct-amt { color: var(--acct-negative); }
.acct-adv-section-total { background: #f8fafc; font-weight: 700; }
.acct-adv-grand-total { background: #eff6ff; font-weight: 800; }
@media (max-width: 991.98px) {
  .acct-adv-stage:has([data-adv-customize]:not([hidden])) { grid-template-columns: 1fr; }
  .acct-adv-customize { position: static; }
}
</style>
