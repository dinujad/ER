@extends('accounting::layouts.app')
@section('title', 'Charts of Accounts')

@section('css')
<style>
/* ── Page hero ─────────────────────────────────────────── */
.coa-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #0d2137 100%);
    border-radius: 14px;
    padding: 22px 28px;
    margin-bottom: 24px;
    color: #fff;
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;
}
.coa-hero h4  { font-weight: 700; font-size: 1.2rem; margin: 0; }
.coa-hero p   { opacity: .75; font-size: .85rem; margin: 3px 0 0; }
.btn-add-coa {
    background: rgba(255,255,255,.15); color: #fff;
    border: 1.5px solid rgba(255,255,255,.35); border-radius: 9px;
    padding: 9px 20px; font-weight: 600; font-size: .9rem;
    white-space: nowrap; text-decoration: none;
    backdrop-filter: blur(4px); transition: all .18s;
}
.btn-add-coa:hover { background: rgba(255,255,255,.28); color: #fff; transform: translateY(-1px); }

/* ── Stat pills ─────────────────────────────────────────── */
.stat-strip { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
.stat-pill {
    background: #fff; border: 1px solid #e9ecef; border-radius: 10px;
    padding: 12px 18px; display: flex; align-items: center; gap: 10px;
    flex: 1 1 130px; box-shadow: 0 1px 3px rgba(0,0,0,.04);
}
.stat-pill .pill-icon {
    width: 36px; height: 36px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;
}
.stat-pill .pill-num  { font-weight: 700; font-size: 1.15rem; color: #1e293b; line-height: 1; }
.stat-pill .pill-lbl  { font-size: .73rem; font-weight: 500; color: #64748b; text-transform: uppercase; letter-spacing: .03em; }

/* ── Table card ─────────────────────────────────────────── */
.coa-card {
    background: #fff; border: 1px solid #e9ecef; border-radius: 12px;
    box-shadow: 0 1px 4px rgba(0,0,0,.05); overflow: hidden;
}
.coa-card-header {
    background: #f8fafc; border-bottom: 1px solid #e9ecef;
    padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
}
.coa-card-header h6 { font-weight: 700; font-size: .92rem; color: #1e293b; margin: 0; }
#search-box {
    border: 1.5px solid #e2e8f0; border-radius: 8px;
    padding: 7px 12px 7px 36px; font-size: .88rem; width: 220px;
    outline: none; transition: border-color .18s;
}
#search-box:focus { border-color: #3b82f6; }
.search-wrap { position: relative; }
.search-wrap i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: .9rem; }

/* ── Table ──────────────────────────────────────────────── */
#coa-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
#coa-table thead th {
    background: #f8fafc; color: #475569; font-weight: 600;
    font-size: .72rem; text-transform: uppercase; letter-spacing: .04em;
    padding: 11px 14px; border-bottom: 1px solid #e9ecef; white-space: nowrap; user-select: none;
}
#coa-table thead th.sortable { cursor: pointer; }
#coa-table thead th.sortable:hover { background: #f1f5f9; }
#coa-table tbody td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
#coa-table tbody tr:last-child td { border-bottom: 0; }
#coa-table tbody tr:hover { background: #fafbfc; }
.acct-name-link { color: #1e3a5f; text-decoration: none; font-weight: 600; }
.acct-name-link:hover { color: #2563eb; text-decoration: underline; }
.gl-badge {
    background: #f1f5f9; color: #475569; border-radius: 5px;
    padding: 2px 8px; font-size: .75rem; font-weight: 600; font-family: monospace;
}

/* ── Account-type colour chips ──────────────────────────── */
.type-chip { border-radius: 6px; padding: 3px 10px; font-size: .74rem; font-weight: 600; display: inline-block; white-space: nowrap; }
.type-asset     { background: #eff6ff; color: #1d4ed8; }
.type-income    { background: #f0fdf4; color: #15803d; }
.type-expense   { background: #fef2f2; color: #b91c1c; }
.type-liability { background: #fffbeb; color: #b45309; }
.type-equity    { background: #f5f3ff; color: #6d28d9; }

/* ── Yes/No badges ──────────────────────────────────────── */
.badge-yes { background: #dcfce7; color: #166534; border-radius: 20px; padding: 3px 10px; font-size: .74rem; font-weight: 600; }
.badge-no  { background: #fee2e2; color: #991b1b; border-radius: 20px; padding: 3px 10px; font-size: .74rem; font-weight: 600; }

/* ── Balance col ─────────────────────────────────────────── */
.bal-pos { color: #15803d; font-weight: 600; }
.bal-neg { color: #b91c1c; font-weight: 600; }
.bal-zero{ color: #94a3b8; }

/* ── Action dropdown ─────────────────────────────────────── */
.action-dd { position: relative; display: inline-block; }
.action-btn {
    background: #1e3a5f; color: #fff; border: none; border-radius: 7px;
    padding: 5px 13px; font-size: .8rem; cursor: pointer; white-space: nowrap;
    display: flex; align-items: center; gap: 5px; transition: background .15s;
}
.action-btn:hover { background: #2563eb; }
.action-menu {
    display: none; position: absolute; left: 0; top: calc(100% + 4px);
    background: #fff; border: 1px solid #e2e8f0; border-radius: 9px;
    box-shadow: 0 8px 24px rgba(0,0,0,.12); min-width: 160px; z-index: 99;
    padding: 4px 0; overflow: hidden;
}
.action-menu.open { display: block; }
.action-menu a, .action-menu button {
    display: flex; align-items: center; gap: 9px;
    padding: 9px 14px; font-size: .85rem; color: #374151;
    text-decoration: none; background: none; border: none; width: 100%; cursor: pointer;
    transition: background .12s;
}
.action-menu a:hover, .action-menu button:hover { background: #f8fafc; }
.action-menu .danger { color: #dc2626; }
.action-menu .danger:hover { background: #fef2f2; }
.action-menu .divider { height: 1px; background: #f1f5f9; margin: 3px 0; }

/* ── Empty state ─────────────────────────────────────────── */
#empty-state { text-align: center; padding: 56px 20px; color: #94a3b8; }
#empty-state i { font-size: 2.5rem; display: block; margin-bottom: 12px; }
#empty-state p { font-size: .95rem; margin: 0; }

/* ── Pagination ──────────────────────────────────────────── */
.coa-pagination { padding: 14px 20px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
.coa-pagination .info { font-size: .82rem; color: #64748b; }
.page-btn {
    border: 1.5px solid #e2e8f0; border-radius: 7px; background: #fff;
    padding: 5px 12px; font-size: .82rem; color: #374151; cursor: pointer; transition: all .15s;
}
.page-btn:hover { border-color: #3b82f6; color: #2563eb; }
.page-btn.active { background: #1e3a5f; color: #fff; border-color: #1e3a5f; font-weight: 600; }
.page-btn:disabled { opacity: .4; cursor: not-allowed; }
</style>
@endsection

@section('content')
@include('accounting::layouts.nav')

{{-- Hero ─────────────────────────────────────────────────── --}}
<div class="coa-hero">
    <div class="d-flex align-items-center gap-3">
        <div style="background:rgba(255,255,255,.15);border-radius:10px;width:44px;height:44px;display:flex;align-items:center;justify-content:center;font-size:1.35rem;flex-shrink:0;">
            <i class="bi bi-journal-bookmark"></i>
        </div>
        <div>
            <h4>Charts of Accounts</h4>
            <p>Manage your general ledger accounts</p>
        </div>
    </div>
    @can('accounting.chart_of_accounts.create')
    <a href="{{ url('accounting/chart_of_account/create') }}" class="btn-add-coa">
        <i class="bi bi-plus-lg me-1"></i> Add Account
    </a>
    @endcan
</div>

{{-- Stat pills ─────────────────────────────────────────────── --}}
@php
    $allData = $data->getCollection();
    $stats = [
        'asset'     => ['label'=>'Asset',     'icon'=>'bi-bank',           'bg'=>'#eff6ff','clr'=>'#1d4ed8'],
        'income'    => ['label'=>'Income',    'icon'=>'bi-graph-up-arrow', 'bg'=>'#f0fdf4','clr'=>'#15803d'],
        'expense'   => ['label'=>'Expense',   'icon'=>'bi-receipt',        'bg'=>'#fef2f2','clr'=>'#b91c1c'],
        'liability' => ['label'=>'Liability', 'icon'=>'bi-exclamation-circle','bg'=>'#fffbeb','clr'=>'#b45309'],
        'equity'    => ['label'=>'Equity',    'icon'=>'bi-pie-chart',      'bg'=>'#f5f3ff','clr'=>'#6d28d9'],
    ];
@endphp
<div class="stat-strip">
    @foreach($stats as $type => $s)
    <div class="stat-pill">
        <div class="pill-icon" style="background:{{ $s['bg'] }};color:{{ $s['clr'] }}">
            <i class="bi {{ $s['icon'] }}"></i>
        </div>
        <div>
            <div class="pill-num">{{ $allData->where('account_type', $type)->count() }}</div>
            <div class="pill-lbl">{{ $s['label'] }}</div>
        </div>
    </div>
    @endforeach
    <div class="stat-pill">
        <div class="pill-icon" style="background:#f8fafc;color:#475569;">
            <i class="bi bi-list-ul"></i>
        </div>
        <div>
            <div class="pill-num">{{ $data->total() }}</div>
            <div class="pill-lbl">Total</div>
        </div>
    </div>
</div>

{{-- Table card ──────────────────────────────────────────────── --}}
<div class="coa-card">
    <div class="coa-card-header">
        <h6><i class="bi bi-table me-2 text-muted"></i>Account Ledger</h6>
        <div class="d-flex align-items-center gap-2">
            <div class="search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" id="search-box" placeholder="Search accounts…" value="{{ request('s') }}">
            </div>
            <select id="per-page" class="form-select form-select-sm" style="width:auto;border-radius:8px;">
                @foreach([10,25,50,100] as $pp)
                <option value="{{ $pp }}" {{ request('per_page', 20) == $pp ? 'selected' : '' }}>{{ $pp }} / page</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table id="coa-table">
            <thead>
                <tr>
                    <th>Action</th>
                    <th>Account Name</th>
                    <th>GL Code</th>
                    <th>Parent Account</th>
                    <th>Account Type</th>
                    <th>Account Subtype</th>
                    <th>Account Detail Type</th>
                    <th>Active</th>
                    <th>Manual Entries</th>
                    <th>Currency</th>
                    <th style="text-align:right">Opening Balance</th>
                    <th style="text-align:right">Current Balance</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $key)
                @php
                    $typeColors = [
                        'asset'     => 'type-asset',
                        'income'    => 'type-income',
                        'expense'   => 'type-expense',
                        'liability' => 'type-liability',
                        'equity'    => 'type-equity',
                    ];
                    $typeClass = $typeColors[$key->account_type] ?? '';
                    $curBal = (float) $key->current_balance;
                    $balClass = $curBal > 0 ? 'bal-pos' : ($curBal < 0 ? 'bal-neg' : 'bal-zero');
                @endphp
                <tr>
                    {{-- Action --}}
                    <td>
                        <div class="action-dd">
                            <button class="action-btn" onclick="toggleMenu(this)">
                                Action <i class="bi bi-chevron-down" style="font-size:.7rem;"></i>
                            </button>
                            <div class="action-menu">
                                <a href="{{ url('accounting/chart_of_account/'.$key->id.'/show') }}">
                                    <i class="bi bi-eye text-muted"></i> View
                                </a>
                                @can('accounting.chart_of_accounts.edit')
                                <a href="{{ url('accounting/chart_of_account/'.$key->id.'/edit') }}">
                                    <i class="bi bi-pencil text-muted"></i> Edit
                                </a>
                                @endcan
                                @can('accounting.chart_of_accounts.destroy')
                                <div class="divider"></div>
                                <a href="{{ url('accounting/chart_of_account/'.$key->id.'/destroy') }}"
                                   class="danger"
                                   onclick="return confirm('Delete this account?')">
                                    <i class="bi bi-trash"></i> Delete
                                </a>
                                @endcan
                            </div>
                        </div>
                    </td>

                    {{-- Name --}}
                    <td>
                        <a href="{{ url('accounting/chart_of_account/'.$key->id.'/show') }}" class="acct-name-link">
                            {{ $key->name }}
                        </a>
                    </td>

                    {{-- GL Code --}}
                    <td><span class="gl-badge">{{ $key->gl_code }}</span></td>

                    {{-- Parent --}}
                    <td style="color:#64748b;">{{ $key->parent->name }}</td>

                    {{-- Account Type --}}
                    <td><span class="type-chip {{ $typeClass }}">{{ ucfirst($key->account_type) }}</span></td>

                    {{-- Subtype --}}
                    <td style="color:#475569;">{{ $key->account_subtype->name ?? '—' }}</td>

                    {{-- Detail Type --}}
                    <td style="color:#475569;">{{ $key->account_detail_type->name ?? '—' }}</td>

                    {{-- Active --}}
                    <td>
                        @if($key->active)
                            <span class="badge-yes"><i class="bi bi-check-circle-fill me-1"></i>Yes</span>
                        @else
                            <span class="badge-no"><i class="bi bi-x-circle-fill me-1"></i>No</span>
                        @endif
                    </td>

                    {{-- Manual Entries --}}
                    <td>
                        @if($key->allow_manual)
                            <span class="badge-yes">Yes</span>
                        @else
                            <span class="badge-no">No</span>
                        @endif
                    </td>

                    {{-- Currency --}}
                    <td>
                        <span style="background:#f1f5f9;color:#475569;border-radius:5px;padding:2px 8px;font-size:.75rem;font-weight:600;">
                            {{ $key->currency->code ?? '—' }}
                        </span>
                    </td>

                    {{-- Opening Balance --}}
                    <td style="text-align:right;">
                        <span style="color:#374151;">{{ number_format((float)$key->opening_balance, 2) }}</span>
                    </td>

                    {{-- Current Balance --}}
                    <td style="text-align:right;">
                        <span class="{{ $balClass }}">{{ number_format($curBal, 2) }}</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="12">
                        <div id="empty-state">
                            <i class="bi bi-inbox"></i>
                            <p>No accounts found. <a href="{{ url('accounting/chart_of_account/create') }}">Add your first account</a>.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination ────────────────────────────────────────── --}}
    @if($data->hasPages() || $data->count() > 0)
    <div class="coa-pagination">
        <div class="info">
            Showing {{ $data->firstItem() }}–{{ $data->lastItem() }} of {{ $data->total() }} accounts
        </div>
        <div class="d-flex gap-1 flex-wrap">
            {{-- Prev --}}
            @if($data->onFirstPage())
            <button class="page-btn" disabled><i class="bi bi-chevron-left"></i></button>
            @else
            <a href="{{ $data->previousPageUrl() }}" class="page-btn"><i class="bi bi-chevron-left"></i></a>
            @endif

            @foreach($data->getUrlRange(max(1,$data->currentPage()-2), min($data->lastPage(),$data->currentPage()+2)) as $page => $url)
            <a href="{{ $url }}" class="page-btn {{ $page == $data->currentPage() ? 'active' : '' }}">{{ $page }}</a>
            @endforeach

            {{-- Next --}}
            @if($data->hasMorePages())
            <a href="{{ $data->nextPageUrl() }}" class="page-btn"><i class="bi bi-chevron-right"></i></a>
            @else
            <button class="page-btn" disabled><i class="bi bi-chevron-right"></i></button>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection

@section('javascript')
<script>
/* ── Dropdown toggle ─────────────────────────────── */
function toggleMenu(btn) {
    var menu = btn.nextElementSibling;
    document.querySelectorAll('.action-menu.open').forEach(function(m) {
        if (m !== menu) m.classList.remove('open');
    });
    menu.classList.toggle('open');
    event.stopPropagation();
}
document.addEventListener('click', function () {
    document.querySelectorAll('.action-menu.open').forEach(function (m) { m.classList.remove('open'); });
});

/* ── Live search ─────────────────────────────────── */
var searchTimer;
document.getElementById('search-box').addEventListener('input', function () {
    clearTimeout(searchTimer);
    var q = this.value;
    searchTimer = setTimeout(function () {
        var url = new URL(window.location.href);
        url.searchParams.set('s', q);
        url.searchParams.delete('page');
        window.location.href = url.toString();
    }, 450);
});

/* ── Per-page change ─────────────────────────────── */
document.getElementById('per-page').addEventListener('change', function () {
    var url = new URL(window.location.href);
    url.searchParams.set('per_page', this.value);
    url.searchParams.delete('page');
    window.location.href = url.toString();
});
</script>
@endsection
