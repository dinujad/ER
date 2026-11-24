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
    @include('accounting::layouts._acct_design_system')
    @include('accounting::layouts._acct_harmonize')
    @yield('css')
</head>
<body class="acct-workspace acct-ws-collapsed">
@php
    $businessName = session('business.name') ?? config('app.name');
    $userName = auth()->user()->name ?? 'User';
    $fy = function_exists('get_financial_year') ? get_financial_year() : date('Y');
@endphp
<script>
(function () {
  try {
    if (localStorage.getItem('acct_ws_collapsed') === '0') {
      document.body.classList.remove('acct-ws-collapsed');
    }
  } catch (e) {}
})();
</script>
<div class="acct-ws-shell">
    @include('accounting::layouts.workspace_sidebar')

    <div class="acct-ws-main">
        <header class="acct-ws-header">
            <div class="acct-ws-header-left d-flex align-items-center gap-2 flex-wrap">
                <button type="button" class="acct-ws-menu-btn" id="acctWsMenuBtn" aria-label="Open menu" aria-controls="acctWorkspaceSidebar">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h1 class="acct-ws-title">@yield('title', 'Accounting Workspace')</h1>
                    <div class="acct-ws-meta mt-1 d-flex gap-2 flex-wrap">
                        <span class="acct-ws-chip">{{ $businessName }}</span>
                        <span class="acct-ws-chip">FY {{ $fy }}</span>
                        <span class="acct-ws-chip accent">LKR</span>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <form method="GET" action="{{ url('accounting/phase6/search') }}" class="d-none d-lg-flex" role="search">
                    <div class="input-group input-group-sm" style="width:300px">
                        <input type="search" name="q" class="form-control" placeholder="Invoice, bill, customer or amount" value="{{ request()->is('accounting/phase6/search') ? request('q') : '' }}" aria-label="Search">
                        <button class="btn btn-outline-secondary" type="submit" aria-label="Search"><i class="bi bi-search"></i></button>
                    </div>
                </form>
                <span class="text-muted small d-none d-md-inline">{{ $userName }}</span>
                <a href="{{ url('/erp/dashboard') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-box-arrow-left me-1"></i> Back to ERP
                </a>
            </div>
        </header>

        <main class="acct-ws-content" id="acctMainContent">
            @php
                // FlashService::onSave/onUpdate/onDelete store status as ['success' => 1, 'msg' => '…'] — printing the array crashed the page.
                $flashStatus = session('status');
                $flashStatusOk = is_array($flashStatus) ? (bool) ($flashStatus['success'] ?? true) : true;
                $flashStatus = is_array($flashStatus) ? ($flashStatus['msg'] ?? null) : $flashStatus;
            @endphp
            @if(filled($flashStatus) && is_string($flashStatus))
                <div class="alert {{ $flashStatusOk ? 'alert-success' : 'alert-danger' }} border-0 shadow-sm" role="status">{{ $flashStatus }}</div>
            @endif
            @if(session('success') && is_string(session('success')))
                <div class="alert alert-success border-0 shadow-sm" role="status">{{ session('success') }}</div>
            @endif
            @if(session('warning') && is_string(session('warning')))
                <div class="alert alert-warning border-0 shadow-sm" role="alert">{{ session('warning') }}</div>
            @endif
            @if(session('error') && is_string(session('error')))
                <div class="alert alert-danger border-0 shadow-sm" role="alert">{{ session('error') }}</div>
            @endif
            @if(isset($errors) && $errors->any())
                <div class="alert alert-danger border-0 shadow-sm" role="alert">
                    <strong>Please fix the following:</strong>
                    <ul class="mb-0 mt-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
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
    var collapseBtn = document.getElementById('acctWsCollapseBtn');
    var search = document.getElementById('acctNavSearch');

    function closeNav() { document.body.classList.remove('acct-ws-open'); }
    function toggleNav() { document.body.classList.toggle('acct-ws-open'); }
    if (btn) btn.addEventListener('click', toggleNav);
    if (backdrop) backdrop.addEventListener('click', closeNav);

    function syncCollapseUi() {
        var collapsed = document.body.classList.contains('acct-ws-collapsed');
        if (collapseBtn) {
            collapseBtn.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
            collapseBtn.setAttribute('title', collapsed ? 'Expand' : 'Compact');
            var label = collapseBtn.querySelector('.d-acct-collapse-label');
            if (label) label.textContent = collapsed ? 'Expand' : 'Compact';
        }
        if (!collapsed) {
            document.querySelectorAll('.acct-ws-group.open-collapsed').forEach(function (g) {
                g.classList.remove('open-collapsed');
            });
        }
    }
    syncCollapseUi();

    if (collapseBtn) {
        collapseBtn.addEventListener('click', function () {
            document.body.classList.toggle('acct-ws-collapsed');
            try {
                localStorage.setItem('acct_ws_collapsed', document.body.classList.contains('acct-ws-collapsed') ? '1' : '0');
            } catch (e) {}
            syncCollapseUi();
        });
    }

    document.querySelectorAll('.acct-ws-group[data-group]').forEach(function (group) {
        var toggle = group.querySelector('.acct-ws-group-btn');
        if (!toggle) return;
        var hideTimer = null;
        var flyout = group.querySelector('.acct-ws-group-body');
        function placeFlyout() {
            if (!flyout) return;
            var rect = toggle.getBoundingClientRect();
            var top = rect.top;
            var maxH = Math.min(window.innerHeight * 0.7, 420);
            if (top + maxH > window.innerHeight - 12) {
                top = Math.max(12, window.innerHeight - maxH - 12);
            }
            flyout.style.top = top + 'px';
        }
        function openFlyout() {
            if (hideTimer) { clearTimeout(hideTimer); hideTimer = null; }
            document.querySelectorAll('.acct-ws-group.open-collapsed').forEach(function (g) {
                if (g !== group) g.classList.remove('open-collapsed');
            });
            group.classList.add('open-collapsed');
            placeFlyout();
        }
        function scheduleCloseFlyout() {
            if (hideTimer) clearTimeout(hideTimer);
            hideTimer = setTimeout(function () {
                group.classList.remove('open-collapsed');
                hideTimer = null;
            }, 220);
        }
        toggle.addEventListener('click', function () {
            if (document.body.classList.contains('acct-ws-collapsed')) {
                if (group.classList.contains('open-collapsed')) {
                    group.classList.remove('open-collapsed');
                } else {
                    openFlyout();
                }
                return;
            }
            var willOpen = !group.classList.contains('open');
            group.classList.toggle('open', willOpen);
            toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        });
        group.addEventListener('mouseenter', function () {
            if (!document.body.classList.contains('acct-ws-collapsed')) return;
            openFlyout();
        });
        group.addEventListener('mouseleave', function () {
            if (!document.body.classList.contains('acct-ws-collapsed')) return;
            scheduleCloseFlyout();
        });
        if (flyout) {
            flyout.addEventListener('mouseenter', function () {
                if (!document.body.classList.contains('acct-ws-collapsed')) return;
                if (hideTimer) { clearTimeout(hideTimer); hideTimer = null; }
                group.classList.add('open-collapsed');
            });
            flyout.addEventListener('mouseleave', function () {
                if (!document.body.classList.contains('acct-ws-collapsed')) return;
                scheduleCloseFlyout();
            });
        }
    });

    if (search) {
        search.addEventListener('input', function () {
            var q = (search.value || '').toLowerCase().trim();
            document.querySelectorAll('#acctWsNav .acct-ws-link').forEach(function (link) {
                var label = (link.getAttribute('data-label') || link.textContent || '').toLowerCase();
                var show = !q || label.indexOf(q) !== -1;
                link.style.display = show ? '' : 'none';
                if (show && q) {
                    var g = link.closest('.acct-ws-group');
                    if (g) g.classList.add('open');
                }
            });
        });
    }

    document.querySelectorAll('#acctWorkspaceSidebar a.acct-ws-link').forEach(function (a) {
        a.addEventListener('click', function () {
            if (window.matchMedia('(max-width: 991.98px)').matches) closeNav();
        });
    });
})();
</script>
@yield('javascript')
@yield('scripts')
@stack('scripts')
</body>
</html>
