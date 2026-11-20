<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') — @endif Accounting · {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;500;600;700&family=IBM+Plex+Sans:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    @include('accounting::layouts._acct_theme')
    <style>
        :root {
            --acct-sidebar: #0f2744;
            --acct-sidebar-2: #163556;
            --acct-sidebar-width: 280px;
            --acct-accent: #2f6fed;
            --acct-bg: #eef2f6;
            --acct-ink: #1e293b;
        }
        body.acct-workspace {
            margin: 0;
            background: var(--acct-bg);
            color: var(--acct-ink);
            font-family: 'Source Sans 3', system-ui, sans-serif;
            overflow-x: hidden;
        }
        .acct-ws-shell {
            display: grid;
            grid-template-columns: var(--acct-sidebar-width) minmax(0, 1fr);
            min-height: 100vh;
        }
        .acct-ws-sidebar {
            width: var(--acct-sidebar-width);
            background: linear-gradient(180deg, var(--acct-sidebar) 0%, var(--acct-sidebar-2) 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
            z-index: 1040;
        }
        .acct-ws-brand {
            display: flex; gap: 12px; align-items: center;
            flex-shrink: 0; padding: 18px 16px; border-bottom: 1px solid rgba(255,255,255,.12);
        }
        .acct-ws-brand-mark {
            width: 40px; height: 40px; border-radius: 10px;
            background: rgba(255,255,255,.12); display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
        }
        .acct-ws-brand-title { font-family: 'IBM Plex Sans', sans-serif; font-weight: 700; font-size: 1.05rem; }
        .acct-ws-brand-sub { font-size: .75rem; opacity: .7; }
        .acct-ws-nav {
            padding: 4px 12px 18px;
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #58718c transparent;
        }
        .acct-ws-section {
            margin: 18px 4px 8px; font-size: .7rem; letter-spacing: .06em;
            text-transform: uppercase; color: #b6c9df; font-weight: 700;
        }
        .acct-ws-link {
            display: flex; align-items: center; gap: 10px;
            color: #edf4fc; text-decoration: none;
            min-height: 42px; padding: 5px 9px; margin-bottom: 5px;
            background: rgba(255,255,255,.045);
            border: 1px solid rgba(255,255,255,.10); border-radius: 9px;
            font-size: .875rem; font-weight: 600;
        }
        .acct-ws-link:hover {
            background: rgba(255,255,255,.13); border-color: rgba(255,255,255,.24); color: #fff;
        }
        .acct-ws-link.active {
            background: #f0f6ff; border-color: #c6dbf8; color: #163556;
            box-shadow: inset 3px 0 #4385e6;
        }
        .acct-ws-link i {
            display: grid; place-items: center;
            flex: 0 0 28px; height: 28px; border-radius: 6px;
            background: rgba(255,255,255,.09); color: #c4dcfa;
        }
        .acct-ws-link.active i { background: #dceaff; color: #244e80; }
        .acct-ws-sidebar-foot { flex-shrink: 0; padding: 10px 12px; border-top: 1px solid rgba(255,255,255,.12); }
        .acct-ws-back {
            display: flex; align-items: center; gap: 8px; color: #fff; text-decoration: none;
            padding: 10px 12px; border: 1px solid rgba(255,255,255,.18);
            border-radius: 9px; background: rgba(255,255,255,.1); font-weight: 600; font-size: .9rem;
        }
        .acct-ws-back:hover { background: rgba(255,255,255,.18); color: #fff; }
        .acct-ws-main { min-width: 0; display: flex; flex-direction: column; }
        .acct-ws-header {
            background: #fff; border-bottom: 1px solid #e2e8f0;
            padding: 12px 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
            position: sticky; top: 0; z-index: 1030;
        }
        .acct-ws-header-left { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .acct-ws-search { flex: 1 1 300px; max-width: 400px; margin-inline-start: auto; }
        .acct-ws-menu-btn {
            display: none; border: 1px solid #e2e8f0; background: #fff; border-radius: 8px;
            width: 40px; height: 40px; align-items: center; justify-content: center;
        }
        .acct-ws-title { font-family: 'IBM Plex Sans', sans-serif; font-weight: 700; font-size: 1.05rem; margin: 0; }
        .acct-ws-meta { display: flex; gap: 8px; flex-wrap: wrap; }
        .acct-ws-chip {
            font-size: .75rem; font-weight: 600; background: #f1f5f9; color: #475569;
            border-radius: 999px; padding: 4px 10px;
        }
        .acct-ws-content { padding: 20px 22px 40px; max-width: 1400px; width: 100%; margin: 0 auto; }
        .acct-ws-backdrop { display: none; }
        @media (max-width: 991.98px) {
            .acct-ws-shell { grid-template-columns: minmax(0, 1fr); }
            .acct-ws-sidebar {
                width: min(var(--acct-sidebar-width), 100vw);
                position: fixed; left: 0; top: 0; transform: translateX(-105%);
                transition: transform .2s ease; box-shadow: 8px 0 24px rgba(0,0,0,.2);
            }
            body.acct-ws-open .acct-ws-sidebar { transform: translateX(0); }
            .acct-ws-menu-btn { display: inline-flex; }
            .acct-ws-search { order: 1; flex-basis: 100%; max-width: none; }
            .acct-ws-backdrop {
                display: none; position: fixed; inset: 0; background: rgba(15,23,42,.45); z-index: 1035;
            }
            body.acct-ws-open .acct-ws-backdrop { display: block; }
        }
        @media print {
            .acct-ws-shell { display: block; }
            .acct-ws-sidebar, .acct-ws-header, .acct-ws-backdrop { display: none !important; }
            .acct-ws-content { max-width: none; padding: 0; }
        }
    </style>
    @yield('css')
</head>
<body class="acct-workspace">
@php
    $businessName = session('business.name') ?? config('app.name');
    $user = auth()->user();
    $userName = $user->name ?? 'User';
    $canSearch = $user && ($user->isSystemAdmin() || $user->can_access('accounting.view'));
    $fy = function_exists('get_financial_year') ? get_financial_year() : date('Y');
@endphp
<div class="acct-ws-shell">
    @include('accounting::layouts.workspace_sidebar')

    <div class="acct-ws-main">
        <header class="acct-ws-header">
            <div class="acct-ws-header-left">
                <button type="button" class="acct-ws-menu-btn" id="acctWsMenuBtn" aria-label="Open menu">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h1 class="acct-ws-title">@yield('title', 'Accounting Workspace')</h1>
                    <div class="acct-ws-meta mt-1">
                        <span class="acct-ws-chip">{{ $businessName }}</span>
                        <span class="acct-ws-chip">FY {{ $fy }}</span>
                        <span class="acct-ws-chip">LKR</span>
                    </div>
                </div>
            </div>
            @if($canSearch)
                <form method="GET" action="{{ url('accounting/phase6/search') }}" class="acct-ws-search" role="search" aria-label="Accounting search">
                    <div class="input-group input-group-sm">
                        <input type="search" name="q" class="form-control" aria-label="Search invoice, bill, customer or amount" placeholder="Invoice, bill, customer or amount" value="{{ request()->is('accounting/phase6/search') ? request('q') : '' }}">
                        <button type="submit" class="btn btn-outline-secondary" aria-label="Search"><i class="bi bi-search" aria-hidden="true"></i></button>
                    </div>
                </form>
            @endif
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="text-muted small d-none d-md-inline">{{ $userName }}</span>
                <a href="{{ url('/erp/dashboard') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-box-arrow-left me-1"></i> Back to ERP
                </a>
            </div>
        </header>

        <main class="acct-ws-content">
            @if(session('status'))
                <div class="alert alert-info border-0 shadow-sm">{{ session('status') }}</div>
            @endif
            @if(session('success'))
                <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
(function () {
    var btn = document.getElementById('acctWsMenuBtn');
    var backdrop = document.getElementById('acctWorkspaceBackdrop');
    function closeNav() { document.body.classList.remove('acct-ws-open'); }
    function toggleNav() { document.body.classList.toggle('acct-ws-open'); }
    if (btn) btn.addEventListener('click', toggleNav);
    if (backdrop) backdrop.addEventListener('click', closeNav);
    document.querySelectorAll('#acctWorkspaceSidebar a').forEach(function (a) {
        a.addEventListener('click', closeNav);
    });
})();
</script>
@yield('javascript')
@yield('scripts')
@stack('scripts')
</body>
</html>
