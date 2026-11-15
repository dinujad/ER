<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#1e3a5f">
    <title>@yield('title', config('app.name') . ' - Sales')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --dermas-sidebar: linear-gradient(180deg, #1e3a5f 0%, #0d2137 100%);
            --dermas-card: #ffffff;
            --dermas-border: #e9ecef;
            --dermas-head: #f8fafc;
        }
        body { background-color: #f1f5f9; font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; color: #334155; padding-bottom: env(safe-area-inset-bottom, 0); }
        .sidebar {
            min-height: 100vh;
            background: var(--dermas-sidebar);
            color: white;
            box-shadow: 2px 0 12px rgba(0,0,0,0.08);
        }
        .sidebar a { color: rgba(255,255,255,0.85); text-decoration: none; padding: 10px 14px; display: block; border-radius: 8px; transition: all 0.2s; }
        .sidebar a:hover { background-color: rgba(255,255,255,0.1); color: white; }
        .sidebar .nav-link.active { background-color: rgba(255,255,255,0.95); color: #1e3a5f !important; font-weight: 600; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .sidebar .collapse .nav-link { padding-left: 12px; font-size: 0.9rem; }
        .content { padding: 1rem 1.25rem; max-width: 1400px; margin: 0 auto; }
        .content .card { border: 1px solid var(--dermas-border); border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .content .card-header { background: var(--dermas-head); border-bottom: 1px solid var(--dermas-border); font-weight: 600; padding: 14px 18px; border-radius: 10px 10px 0 0; }
        .content .table { margin-bottom: 0; font-size: 0.9rem; }
        .content .table thead th { background: var(--dermas-head); color: #475569; font-weight: 600; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.03em; padding: 12px 14px; }
        .content .table tbody td { padding: 12px 14px; vertical-align: middle; }
        .content .table tbody tr:hover { background-color: #f8fafc; }
        .content .alert { border-radius: 8px; border: none; }

        /* Desktop: fixed sidebar */
        @media (min-width: 992px) {
            .sidebar-wrap { width: 280px; min-width: 280px; overflow-y: auto; }
            .mobile-navbar { display: none !important; }
        }
        /* Mobile: hide sidebar, show offcanvas trigger */
        @media (max-width: 991.98px) {
            .sidebar-wrap { display: none; }
            .mobile-navbar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 0.75rem 1rem;
                background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
                color: #fff;
                box-shadow: 0 2px 8px rgba(30,58,95,0.2);
                position: sticky;
                top: 0;
                z-index: 1030;
            }
            .mobile-navbar .brand { font-weight: 700; font-size: 1.2rem; }
            .content { padding: 0.75rem 1rem; }
        }
        /* Offcanvas sidebar (mobile menu) */
        .offcanvas-sidebar .offcanvas-header { background: var(--dermas-sidebar); color: #fff; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .offcanvas-sidebar .offcanvas-body { padding: 0; background: var(--dermas-sidebar); }
        .offcanvas-sidebar { z-index: 1055; }
        .offcanvas-sidebar .offcanvas-body .nav-link {
            color: rgba(255,255,255,0.92);
            padding: 14px 16px;
            display: block;
            border-radius: 8px;
            min-height: 48px;
            line-height: 1.3;
            cursor: pointer;
            pointer-events: auto;
            touch-action: manipulation;
            -webkit-tap-highlight-color: rgba(255,255,255,0.15);
            position: relative;
            z-index: 1;
        }
        .offcanvas-sidebar .offcanvas-body .nav-link:hover,
        .offcanvas-sidebar .offcanvas-body .nav-link:focus {
            background-color: rgba(255,255,255,0.12);
            color: #fff;
        }
        .offcanvas-sidebar .offcanvas-body .nav-link.active { background: rgba(255,255,255,0.95); color: #1e3a5f !important; }
        .offcanvas-sidebar .offcanvas-body button.nav-link {
            min-height: 48px;
            pointer-events: auto;
            touch-action: manipulation;
        }
        .offcanvas-sidebar .btn-close { filter: invert(1); pointer-events: auto; }

        .sidebar::-webkit-scrollbar { width: 6px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 3px; }
    </style>
</head>
<body>
    {{-- Mobile top bar + menu button --}}
    <div class="mobile-navbar d-lg-none">
        <span class="brand">{{ config('app.name') }} Sales</span>
        <button class="btn btn-link text-white p-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#salesSidebar" aria-controls="salesSidebar">
            <i class="bi bi-list fs-2"></i>
        </button>
    </div>

    <div class="d-flex">
        {{-- Desktop sidebar --}}
        <div class="sidebar-wrap sidebar p-3 d-none d-lg-block">
            <a href="{{ route('sales-person.dashboard') }}" class="text-white text-decoration-none d-block mb-4">
                <h4 class="mb-0 fw-bold">{{ config('app.name') }}</h4>
                <small class="text-white-50">Sales Portal</small>
            </a>
            <ul class="navbar-nav">
                <li class="nav-item mb-2">
                    <a href="{{ route('sales-person.dashboard') }}" class="nav-link {{ request()->routeIs('sales-person.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ route('sales-person.route.show') }}" class="nav-link {{ request()->routeIs('sales-person.route.*') ? 'active' : '' }}">
                        <i class="bi bi-signpost-2 me-2"></i> My Route
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link d-flex justify-content-between align-items-center {{ request()->routeIs('sales-person.orders.*') ? 'active' : '' }}"
                       data-bs-toggle="collapse" href="#spOrdersMenu" role="button"
                       aria-expanded="{{ request()->routeIs('sales-person.orders.*') ? 'true' : 'false' }}">
                        <span><i class="bi bi-cart3 me-2"></i> Orders</span>
                        <i class="bi bi-chevron-down small"></i>
                    </a>
                    <div class="collapse {{ request()->routeIs('sales-person.orders.*') ? 'show' : '' }}" id="spOrdersMenu">
                        <ul class="nav flex-column ms-3 mt-1 ps-2 border-start border-white-50">
                            <li class="nav-item">
                                <a href="{{ route('sales-person.orders.index') }}" class="nav-link py-1 {{ request()->routeIs('sales-person.orders.index') && request('status') !== 'draft' ? 'active' : '' }}">All Orders</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('sales-person.orders.index', ['status' => 'draft']) }}" class="nav-link py-1 {{ request('status') === 'draft' ? 'active' : '' }}">Draft</a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ route('sales-person.sale-returns.index') }}" class="nav-link {{ request()->routeIs('sales-person.sale-returns.*') ? 'active' : '' }}">
                        <i class="bi bi-arrow-counterclockwise me-2"></i> Sale Returns
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ route('sales-person.multi-cheques.index') }}" class="nav-link {{ request()->routeIs('sales-person.multi-cheques.*') ? 'active' : '' }}">
                        <i class="bi bi-card-checklist me-2"></i> Multi Cheque
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ route('sales-person.expenses.index') }}" class="nav-link {{ request()->routeIs('sales-person.expenses.*') ? 'active' : '' }}">
                        <i class="bi bi-receipt me-2"></i> My Expenses
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ route('sales-person.sales.index') }}" class="nav-link {{ request()->routeIs('sales-person.sales.*') || request()->routeIs('sales-person.commission.*') ? 'active' : '' }}">
                        <i class="bi bi-graph-up-arrow me-2"></i> Sales & Commission
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ route('sales-person.credit-report.index') }}" class="nav-link {{ request()->routeIs('sales-person.credit-report.*') ? 'active' : '' }}">
                        <i class="bi bi-journal-text me-2"></i> Credit Report
                    </a>
                </li>
                <li class="nav-item mt-2">
                    <a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#customerMenu" role="button" aria-expanded="false">
                        <span><i class="bi bi-people me-2"></i> Customer</span>
                        <i class="bi bi-chevron-down small"></i>
                    </a>
                    <div class="collapse {{ request()->is('sales-person/customers*') ? 'show' : '' }}" id="customerMenu">
                        <ul class="nav flex-column ms-3 mt-1 ps-2 border-start border-white-50">
                            <li class="nav-item"><a href="{{ route('sales-person.customers.create') }}" class="nav-link py-1">Add Customer</a></li>
                            <li class="nav-item"><a href="{{ route('sales-person.customers.index') }}" class="nav-link py-1">Customer List</a></li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item mt-3 border-top pt-3">
                    <form action="{{ route('sales-person.logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="nav-link bg-transparent border-0 text-white w-100 text-start">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>

        {{-- Main content --}}
        <div class="content flex-grow-1">
            @if(session('success') && !session('download_order_form_id'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @yield('content')
        </div>
    </div>

    {{-- Mobile offcanvas menu --}}
    <div class="offcanvas offcanvas-start offcanvas-sidebar" tabindex="-1" id="salesSidebar" aria-labelledby="salesSidebarLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="salesSidebarLabel">{{ config('app.name') }} Sales</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="navbar-nav px-2 pb-3">
                <li class="nav-item mb-1">
                    <a href="{{ route('sales-person.dashboard') }}" class="nav-link sp-mobile-nav {{ request()->routeIs('sales-person.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a href="{{ route('sales-person.route.show') }}" class="nav-link sp-mobile-nav {{ request()->routeIs('sales-person.route.*') ? 'active' : '' }}">
                        <i class="bi bi-signpost-2 me-2"></i> My Route
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a href="{{ route('sales-person.orders.index') }}" class="nav-link sp-mobile-nav {{ request()->routeIs('sales-person.orders.index') && request('status') !== 'draft' ? 'active' : '' }}">
                        <i class="bi bi-cart3 me-2"></i> All Orders
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a href="{{ route('sales-person.orders.index', ['status' => 'draft']) }}" class="nav-link sp-mobile-nav {{ request('status') === 'draft' ? 'active' : '' }}">
                        <i class="bi bi-pencil-square me-2"></i> Draft Orders
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a href="{{ route('sales-person.sale-returns.index') }}" class="nav-link sp-mobile-nav {{ request()->routeIs('sales-person.sale-returns.*') ? 'active' : '' }}">
                        <i class="bi bi-arrow-counterclockwise me-2"></i> Sale Returns
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a href="{{ route('sales-person.multi-cheques.index') }}" class="nav-link sp-mobile-nav {{ request()->routeIs('sales-person.multi-cheques.*') ? 'active' : '' }}">
                        <i class="bi bi-card-checklist me-2"></i> Multi Cheque
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a href="{{ route('sales-person.expenses.index') }}" class="nav-link sp-mobile-nav {{ request()->routeIs('sales-person.expenses.*') ? 'active' : '' }}">
                        <i class="bi bi-receipt me-2"></i> My Expenses
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a href="{{ route('sales-person.sales.index') }}" class="nav-link sp-mobile-nav {{ request()->routeIs('sales-person.sales.*') || request()->routeIs('sales-person.commission.*') ? 'active' : '' }}">
                        <i class="bi bi-graph-up-arrow me-2"></i> Sales & Commission
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a href="{{ route('sales-person.credit-report.index') }}" class="nav-link sp-mobile-nav {{ request()->routeIs('sales-person.credit-report.*') ? 'active' : '' }}">
                        <i class="bi bi-journal-text me-2"></i> Credit Report
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a href="{{ route('sales-person.customers.create') }}" class="nav-link sp-mobile-nav {{ request()->routeIs('sales-person.customers.create') ? 'active' : '' }}">
                        <i class="bi bi-person-plus me-2"></i> Add Customer
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a href="{{ route('sales-person.customers.index') }}" class="nav-link sp-mobile-nav {{ request()->routeIs('sales-person.customers.index') || request()->routeIs('sales-person.customers.show') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i> Customer List
                    </a>
                </li>
                <li class="nav-item mt-3 border-top border-white border-opacity-25 pt-3">
                    <form action="{{ route('sales-person.logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="nav-link bg-transparent border-0 text-white w-100 text-start">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            var sidebarEl = document.getElementById('salesSidebar');
            if (!sidebarEl) return;

            function closeSalesSidebar() {
                if (typeof bootstrap === 'undefined') return;
                var instance = bootstrap.Offcanvas.getInstance(sidebarEl);
                if (instance) {
                    instance.hide();
                }
            }

            sidebarEl.querySelectorAll('a.sp-mobile-nav[href]').forEach(function (link) {
                link.addEventListener('click', function () {
                    closeSalesSidebar();
                }, { passive: true });
            });
        })();
    </script>
    @yield('scripts')
    @stack('scripts')
    <script>
        function orderFormFileName(filename) {
            return filename || 'order-form.pdf';
        }
        function rememberOrderFormFile(target, pdfUrl, filename) {
            if (!pdfUrl || target._file || target._loading) return;
            target._loading = true;
            fetch(pdfUrl, { credentials: 'same-origin' })
                .then(function (response) { return response.ok ? response.blob() : null; })
                .then(function (blob) {
                    if (!blob) return;
                    target._file = new File([blob], orderFormFileName(filename), { type: 'application/pdf' });
                });
        }
        window.sharePreparedOrderForm = function (file) {
            if (!file || !navigator.share || !navigator.canShare || !navigator.canShare({ files: [file] })) {
                return false;
            }
            navigator.share({ files: [file], title: file.name }).catch(function (error) {
                if (error && error.name === 'AbortError') return;
            });
            return true;
        };
        window.shareOrderFormPdf = async function (pdfUrl, filename) {
            const response = await fetch(pdfUrl, { credentials: 'same-origin' });
            if (!response.ok) return;
            const file = new File([await response.blob()], orderFormFileName(filename), { type: 'application/pdf' });
            if (window.sharePreparedOrderForm(file)) return;
            const objectUrl = URL.createObjectURL(file);
            const link = document.createElement('a');
            link.href = objectUrl;
            link.download = file.name;
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(objectUrl);
        };
        document.addEventListener('pointerdown', function (event) {
            const button = event.target.closest('.js-share-pdf');
            if (!button) return;
            rememberOrderFormFile(button, button.dataset.pdf, button.dataset.filename);
        }, { passive: true });
        document.addEventListener('click', function (event) {
            const button = event.target.closest('.js-share-pdf');
            if (!button || !button.dataset.pdf) return;
            event.preventDefault();
            if (button._file && window.sharePreparedOrderForm(button._file)) return;
            window.shareOrderFormPdf(button.dataset.pdf, button.dataset.filename);
        });
    </script>
</body>
</html>
