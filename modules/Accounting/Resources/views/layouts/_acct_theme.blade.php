{{-- Shared accounting theme CSS – included by all accounting pages --}}
<style>
/* ── Hero ──────────────────────────────────────────────── */
.acct-hero{background:linear-gradient(135deg,#1e3a5f 0%,#0d2137 100%);border-radius:14px;padding:22px 28px;margin-bottom:22px;color:#fff;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px}
.acct-hero h4{font-weight:700;font-size:1.2rem;margin:0}
.acct-hero p{opacity:.72;font-size:.85rem;margin:3px 0 0}
.acct-hero-icon{background:rgba(255,255,255,.15);border-radius:10px;width:44px;height:44px;display:flex;align-items:center;justify-content:center;font-size:1.35rem;flex-shrink:0}
.btn-hero-action{background:rgba(255,255,255,.15);color:#fff;border:1.5px solid rgba(255,255,255,.35);border-radius:9px;padding:9px 20px;font-weight:600;font-size:.9rem;text-decoration:none;transition:all .18s;white-space:nowrap}
.btn-hero-action:hover{background:rgba(255,255,255,.28);color:#fff;transform:translateY(-1px)}
/* ── Cards ─────────────────────────────────────────────── */
.acct-card{background:#fff;border:1px solid #e9ecef;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.05);overflow:hidden;margin-bottom:20px}
.acct-card-head{background:#f8fafc;border-bottom:1px solid #e9ecef;padding:14px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px}
.acct-card-head h6{font-weight:700;font-size:.92rem;color:#1e293b;margin:0}
.acct-card-body{padding:20px}
/* ── Form controls ──────────────────────────────────────── */
.acct-label{font-size:.8rem;font-weight:600;color:#475569;margin-bottom:5px;text-transform:uppercase;letter-spacing:.03em;display:block}
.acct-control{border-radius:8px;border:1.5px solid #e2e8f0;padding:9px 12px;font-size:.92rem;color:#1e293b;width:100%;transition:border-color .18s,box-shadow .18s;background:#fff}
.acct-control:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.12);outline:none}
.acct-ig .input-group-text{border-radius:8px 0 0 8px;border:1.5px solid #e2e8f0;border-right:0;background:#f8fafc;color:#64748b}
.acct-ig .acct-control{border-radius:0 8px 8px 0}
/* ── Table ──────────────────────────────────────────────── */
.acct-table{width:100%;border-collapse:collapse;font-size:.875rem}
.acct-table thead th{background:#f8fafc;color:#475569;font-weight:600;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;padding:11px 14px;border-bottom:1px solid #e9ecef;white-space:nowrap}
.acct-table tbody td{padding:11px 14px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
.acct-table tbody tr:last-child td{border-bottom:0}
.acct-table tbody tr:hover{background:#fafbfc}
/* ── Type chips ─────────────────────────────────────────── */
.chip{border-radius:6px;padding:3px 10px;font-size:.73rem;font-weight:600;display:inline-block;white-space:nowrap}
.chip-asset{background:#eff6ff;color:#1d4ed8}
.chip-income{background:#f0fdf4;color:#15803d}
.chip-expense{background:#fef2f2;color:#b91c1c}
.chip-liability{background:#fffbeb;color:#b45309}
.chip-equity{background:#f5f3ff;color:#6d28d9}
.chip-blue{background:#eff6ff;color:#1d4ed8}
.chip-green{background:#f0fdf4;color:#15803d}
.chip-red{background:#fef2f2;color:#b91c1c}
.chip-amber{background:#fffbeb;color:#b45309}
.chip-purple{background:#f5f3ff;color:#6d28d9}
.chip-gray{background:#f1f5f9;color:#475569}
/* ── Badges ─────────────────────────────────────────────── */
.badge-yes{background:#dcfce7;color:#166534;border-radius:20px;padding:3px 10px;font-size:.74rem;font-weight:600}
.badge-no{background:#fee2e2;color:#991b1b;border-radius:20px;padding:3px 10px;font-size:.74rem;font-weight:600}
/* ── Action dropdown ────────────────────────────────────── */
.acct-dd{position:relative;display:inline-block}
.acct-dd-btn{background:#1e3a5f;color:#fff;border:none;border-radius:7px;padding:5px 13px;font-size:.8rem;cursor:pointer;white-space:nowrap;display:flex;align-items:center;gap:5px;transition:background .15s}
.acct-dd-btn:hover{background:#2563eb}
.acct-dd-menu{display:none;position:absolute;left:0;top:calc(100% + 4px);background:#fff;border:1px solid #e2e8f0;border-radius:9px;box-shadow:0 8px 24px rgba(0,0,0,.12);min-width:160px;z-index:200;padding:4px 0}
.acct-dd-menu.open{display:block}
.acct-dd-menu a,.acct-dd-menu button{display:flex;align-items:center;gap:9px;padding:9px 14px;font-size:.85rem;color:#374151;text-decoration:none;background:none;border:none;width:100%;cursor:pointer;transition:background .12s}
.acct-dd-menu a:hover,.acct-dd-menu button:hover{background:#f8fafc}
.acct-dd-menu .danger{color:#dc2626}
.acct-dd-menu .danger:hover{background:#fef2f2}
.acct-dd-menu .divider{height:1px;background:#f1f5f9;margin:3px 0}
/* ── GL badge ───────────────────────────────────────────── */
.gl-badge{background:#f1f5f9;color:#475569;border-radius:5px;padding:2px 8px;font-size:.74rem;font-weight:600;font-family:monospace}
/* ── Debit / Credit colours ─────────────────────────────── */
.num-debit{color:#b91c1c;font-weight:600}
.num-credit{color:#15803d;font-weight:600}
.num-zero{color:#94a3b8}
/* ── Save btn ───────────────────────────────────────────── */
.btn-acct-save{background:linear-gradient(135deg,#1e3a5f,#0d2137);color:#fff;border:none;border-radius:9px;padding:10px 28px;font-weight:600;font-size:.95rem;transition:all .18s;cursor:pointer}
.btn-acct-save:hover{background:linear-gradient(135deg,#2563eb,#1e3a5f);color:#fff;transform:translateY(-1px)}
/* ── Select2 integration ────────────────────────────────── */
.select2-container--default .select2-selection--single{height:42px;border-radius:8px;border:1.5px solid #e2e8f0;display:flex;align-items:center}
.select2-container--default .select2-selection--single .select2-selection__rendered{line-height:42px;color:#1e293b;padding-left:12px;font-size:.92rem}
.select2-container--default .select2-selection--single .select2-selection__arrow{height:40px}
.select2-container--default.select2-container--focus .select2-selection--single{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.12)}
.select2-container{width:100%!important}
/* ── Filter bar ─────────────────────────────────────────── */
.filter-bar{background:#fff;border:1px solid #e9ecef;border-radius:12px;padding:16px 20px;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,.04)}
/* Bootstrap modals + Select2 above ERP chrome (sidebar mobile, etc.) */
.modal{z-index:1060!important}
.modal-backdrop{z-index:1055!important}
body.modal-open .select2-container--open{z-index:1075!important}
</style>
<script>
function acctToggleMenu(btn){
    var menu=btn.nextElementSibling;
    document.querySelectorAll('.acct-dd-menu.open').forEach(function(m){if(m!==menu)m.classList.remove('open')});
    menu.classList.toggle('open');
    event.stopPropagation();
}
document.addEventListener('click',function(){document.querySelectorAll('.acct-dd-menu.open').forEach(function(m){m.classList.remove('open')})});
</script>
