<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@hasSection('title')@yield('title') — @endif{{ config('app.name') }}</title>
    <!-- Bootstrap 5 CDN -->
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
        body { background-color: #f1f5f9; font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; color: #334155; margin: 0; overflow-x: hidden; }
        .erp-wrapper { min-height: 100vh; width: 100%; display: flex; flex-direction: row; flex-wrap: nowrap; align-items: flex-start; }
        .sidebar { 
            flex-shrink: 0;
            min-width: 300px;
            width: 300px;
            height: 100vh;
            min-height: 100vh;
            max-height: 100vh;
            background: var(--dermas-sidebar); 
            color: white; 
            box-shadow: 2px 0 12px rgba(0,0,0,0.08);
            overflow-y: auto;
            overflow-x: hidden;
        }
        .content { flex: 1 1 auto; min-width: 0; }
        .sidebar a { color: rgba(255,255,255,0.85); text-decoration: none; padding: 10px 14px; display: block; border-radius: 8px; transition: all 0.2s; word-wrap: break-word; }
        .sidebar a:hover { background-color: rgba(255,255,255,0.1); color: white; }
        .sidebar .nav-link.active { background-color: rgba(255,255,255,0.95); color: #1e3a5f !important; font-weight: 600; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .sidebar .collapse .nav-link { padding-left: 12px; font-size: 0.9rem; white-space: normal; }
        .sidebar .text-muted { color: rgba(255,255,255,0.6) !important; }
        .sidebar .nav-link { white-space: normal; }
        .sidebar .text-uppercase.text-xs { white-space: normal; }
        .content { padding: 24px 28px; max-width: 1600px; margin: 0 auto; width: 100%; }

        /* Cards - cleaner look */
        .content .card {
            border: 1px solid var(--dermas-border);
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .content .card-header {
            background: var(--dermas-head);
            border-bottom: 1px solid var(--dermas-border);
            font-weight: 600;
            padding: 14px 18px;
            border-radius: 10px 10px 0 0;
        }

        /* Tables - system-wide clean style */
        .content .table {
            margin-bottom: 0;
            font-size: 0.9rem;
        }
        .content .table thead th {
            background: var(--dermas-head);
            color: #475569;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            padding: 12px 14px;
            border-bottom: 1px solid var(--dermas-border);
            white-space: nowrap;
        }
        .content .table tbody td {
            padding: 12px 14px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }
        .content .table tbody tr:hover {
            background-color: #f8fafc;
        }
        .content .table tbody tr:last-child td {
            border-bottom-color: var(--dermas-border);
        }
        .content .table-bordered { border-radius: 8px; overflow: hidden; }
        .content .table-bordered thead th { border-color: var(--dermas-border); }
        .content .table-bordered tbody td { border-color: #f1f5f9; }

        /* Alerts in content */
        .content .alert { border-radius: 8px; border: none; }

        /* One form theme for every screen */
        .content .card {
            border-color: #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }
        .content .card-header,
        .content .card-header h6,
        .content .card-header .text-primary {
            background: #fff;
            color: #1e3a5f !important;
            font-weight: 600;
            letter-spacing: 0;
        }
        .content form .form-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 0.35rem;
        }
        .content .form-control,
        .content .form-select,
        .content textarea.form-control {
            border: 1px solid #d5deea;
            border-radius: 8px;
            min-height: 40px;
            padding: 0.5rem 0.75rem;
            font-size: 0.92rem;
            color: #1e293b;
            background: #fff;
            box-shadow: none;
        }
        .content textarea.form-control { min-height: 84px; }
        .content .form-control:focus,
        .content .form-select:focus {
            border-color: #1e3a5f;
            box-shadow: 0 0 0 3px rgba(30, 58, 95, 0.12);
        }
        .content form h6,
        .content form h6.text-primary {
            color: #1e3a5f !important;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 0.45rem;
            margin: 1.35rem 0 1rem;
        }
        .content form small,
        .content form .form-text {
            color: #94a3b8 !important;
            font-size: 0.75rem;
        }
        .content .btn {
            border-radius: 8px;
            font-weight: 600;
        }
        .content .btn-primary {
            background: #1e3a5f;
            border-color: #1e3a5f;
        }
        .content .btn-primary:hover,
        .content .btn-primary:focus {
            background: #16304f;
            border-color: #16304f;
        }
        .content .btn-outline-primary {
            color: #1e3a5f;
            border-color: #1e3a5f;
        }
        .content .btn-outline-primary:hover {
            background: #1e3a5f;
            border-color: #1e3a5f;
            color: #fff;
        }
        .content .form-check-input:checked {
            background-color: #1e3a5f;
            border-color: #1e3a5f;
        }

        /* Scrollbar for sidebar */
        .sidebar::-webkit-scrollbar { width: 6px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 3px; }
        .sidebar::-webkit-scrollbar-track { background: transparent; }

        /* Logo full white in sidebar and mobile header only */
        .sidebar .erp-brand-logo,
        .erp-mobile-header .brand img {
            filter: brightness(0) invert(1);
        }

        /* Mobile: top bar + sidebar as overlay */
        @media (max-width: 991.98px) {
            .erp-mobile-header {
                display: flex !important;
                align-items: center;
                justify-content: space-between;
                padding: 0.75rem 1rem;
                background: var(--dermas-sidebar);
                color: #fff;
                box-shadow: 0 2px 8px rgba(0,0,0,0.15);
                position: sticky;
                top: 0;
                z-index: 1040;
            }
            .erp-mobile-header .brand { font-weight: 700; font-size: 1.1rem; color: #fff; }
            .erp-mobile-header .btn-menu { color: #fff; font-size: 1.5rem; padding: 0.25rem 0.5rem; }
            .erp-sidebar {
                position: fixed !important;
                top: 0;
                left: 0;
                z-index: 1050;
                width: 300px !important;
                max-width: 85vw;
                height: 100vh;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                box-shadow: 4px 0 20px rgba(0,0,0,0.2);
            }
            body.erp-sidebar-open .erp-sidebar { transform: translateX(0); }
            .erp-sidebar-backdrop {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.4);
                z-index: 1045;
                transition: opacity 0.2s;
            }
            body.erp-sidebar-open .erp-sidebar-backdrop { display: block; }
            .content { margin-left: 0 !important; padding: 1rem !important; }
        }
        @media (min-width: 992px) {
            .erp-mobile-header { display: none !important; }
            .erp-wrapper {
                display: grid !important;
                grid-template-columns: 300px 1fr !important;
                grid-template-rows: 1fr !important;
                min-height: 100vh !important;
                width: 100% !important;
                overflow: visible !important;
            }
            .erp-sidebar,
            #erpSidebar {
                display: block !important;
                visibility: visible !important;
                transform: none !important;
                width: 100% !important;
                min-width: 0 !important;
                max-width: 300px !important;
                grid-column: 1 !important;
                grid-row: 1 !important;
                position: sticky !important;
                top: 0 !important;
                left: 0 !important;
                height: 100vh !important;
                z-index: 30 !important;
            }
            .erp-wrapper .content {
                grid-column: 2 !important;
                grid-row: 1 !important;
                min-width: 0 !important;
                position: relative !important;
                /* Do not set z-index here: it traps Bootstrap modals (backdrop is on body at 1050)
                   below the overlay so the dialog cannot be clicked. */
            }
        }
    </style>
    @yield('css')
    @stack('styles')
</head>
<body>
    @php
        $isSupervisor = Auth::check() && optional(Auth::user()->employee)->role === 'line_supervisor';
    @endphp
    <div class="erp-mobile-header d-none">
        <a href="{{ $isSupervisor ? route('supervisor.dashboard') : route('erp.dashboard') }}" class="brand text-decoration-none d-flex align-items-center gap-2 text-white">
            <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" style="height: 32px; width: auto; object-fit: contain;">
            <span class="fw-bold">{{ config('app.name') }}</span>
        </a>
        <button type="button" class="btn btn-link btn-menu text-decoration-none" id="erpMenuBtn" aria-label="Menu">
            <i class="bi bi-list"></i>
        </button>
    </div>
    <div class="erp-sidebar-backdrop" id="erpSidebarBackdrop"></div>
    <div class="d-flex flex-row erp-wrapper" style="min-height: 100vh;">
        <!-- Sidebar: always visible on desktop (min-width 992px) -->
        <aside class="sidebar erp-sidebar p-3" id="erpSidebar" role="navigation">
            <a href="{{ $isSupervisor ? route('supervisor.dashboard') : route('erp.dashboard') }}" class="text-white text-decoration-none d-block mb-4 d-flex align-items-center gap-2">
                <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" class="erp-brand-logo" style="height: 42px; width: auto; object-fit: contain;">
                <span class="fw-bold fs-5">{{ config('app.name') }}</span>
            </a>
            
            <ul class="navbar-nav">
                
                @if($isSupervisor)
                    {{-- SUPERVISOR MENU (Restricted) --}}
                    <li class="nav-item">
                        <a href="{{ route('supervisor.dashboard') }}" class="nav-link {{ request()->routeIs('supervisor.dashboard') ? 'active text-white bg-primary rounded' : '' }}">
                            <i class="bi bi-speedometer2 me-2"></i>
                            <span class="nav-link-text">My Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('supervisor.jobs') }}" class="nav-link {{ request()->routeIs('supervisor.jobs') ? 'active text-white bg-primary rounded' : '' }}">
                            <i class="bi bi-journal-text me-2"></i>
                            <span class="nav-link-text">Jobs</span>
                        </a>
                    </li>
                     <li class="nav-item mt-3">
                        <h6 class="ps-4 ms-2 text-uppercase text-xs text-white font-weight-bolder opacity-8">Account</h6>
                    </li>
                    <li class="nav-item">
                       <form action="{{ route('logout') }}" method="POST" id="logout-sidebar-form">
                           @csrf
                           <a class="nav-link text-white" href="#" onclick="event.preventDefault(); document.getElementById('logout-sidebar-form').submit();">
                            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                              <i class="bi bi-box-arrow-right"></i>
                            </div>
                            <span class="nav-link-text ms-1">Logout</span>
                          </a>
                       </form>
                    </li>

                @else
                    {{-- ADMIN / RESTRICTED MENU --}}
                    @php $u = Auth::user(); @endphp
                    @if($u->can_access('dashboard.view'))
                    <li class="nav-item mb-2">
                        <a href="{{ route('erp.dashboard') }}" class="nav-link {{ request()->routeIs('erp.dashboard') ? 'active' : '' }}">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                    </li>
                    @endif
                    @if($u->can_access('pos.view'))
                    <li class="nav-item mb-2">
                        <a href="{{ route('pos.index') }}" class="nav-link {{ request()->routeIs('pos.*') ? 'active' : '' }}">
                            <i class="bi bi-cash-register me-2"></i> POS (Retail)
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link d-flex justify-content-between align-items-center {{ request()->routeIs('retail-quotations.*') || request()->routeIs('retail-sale-returns.*') ? 'active' : '' }}"
                           data-bs-toggle="collapse" href="#retailSalesMenu" role="button"
                           aria-expanded="{{ (request()->routeIs('retail-quotations.*') || request()->routeIs('retail-sale-returns.*')) ? 'true' : 'false' }}">
                            <span><i class="bi bi-shop-window me-2"></i> Retail Sales</span>
                            <i class="bi bi-chevron-down small"></i>
                        </a>
                        <div class="collapse {{ (request()->routeIs('retail-quotations.*') || request()->routeIs('retail-sale-returns.*')) ? 'show' : '' }}" id="retailSalesMenu">
                            <ul class="nav flex-column ms-3 mt-1 ps-2 border-start border-white-50">
                                <li class="nav-item">
                                    <a href="{{ route('retail-quotations.index') }}" class="nav-link py-1 {{ request()->routeIs('retail-quotations.*') ? 'active' : '' }}">
                                        Quotations
                                    </a>
                                </li>
                                @if($u->can_access('orders.view'))
                                <li class="nav-item">
                                    <a href="{{ route('retail-sale-returns.index') }}" class="nav-link py-1 {{ request()->routeIs('retail-sale-returns.*') ? 'active' : '' }}">
                                        Sales Returns
                                    </a>
                                </li>
                                @endif
                            </ul>
                        </div>
                    </li>
                    @endif
                    @if($u->can_access('orders.view'))
                    <li class="nav-item mb-2">
                        <a class="nav-link d-flex justify-content-between align-items-center {{ request()->routeIs('orders.*') && !in_array(request('workflow'), ['ac', 'issued'], true) ? 'active' : '' }}"
                           data-bs-toggle="collapse" href="#ordersMenu" role="button"
                           aria-expanded="{{ request()->routeIs('orders.*') && !in_array(request('workflow'), ['ac', 'issued'], true) ? 'true' : 'false' }}">
                            <span><i class="bi bi-cart-check me-2"></i> Orders</span>
                            <i class="bi bi-chevron-down small"></i>
                        </a>
                        <div class="collapse {{ request()->routeIs('orders.*') && !in_array(request('workflow'), ['ac', 'issued'], true) ? 'show' : '' }}" id="ordersMenu">
                            <ul class="nav flex-column ms-3 mt-1 ps-2 border-start border-white-50">
                                <li class="nav-item">
                                    <a href="{{ route('orders.index') }}" class="nav-link py-1 {{ request()->routeIs('orders.index') && !request()->boolean('urgent') && !in_array(request('workflow'), ['ac', 'issued'], true) ? 'active' : '' }}">
                                        All Orders
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('orders.index', ['urgent' => 1]) }}" class="nav-link py-1 {{ request()->routeIs('orders.index') && request()->boolean('urgent') ? 'active' : '' }}">
                                        Urgent Orders
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('orders.history', ['date' => now()->toDateString()]) }}" class="nav-link py-1 {{ request()->routeIs('orders.history') ? 'active' : '' }}">
                                        Order History
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link d-flex justify-content-between align-items-center {{ (request()->routeIs('orders.*') && in_array(request('workflow'), ['ac', 'issued'])) ? 'active' : '' }}"
                           data-bs-toggle="collapse" href="#orderAcMenu" role="button"
                           aria-expanded="{{ (request()->routeIs('orders.*') && in_array(request('workflow'), ['ac', 'issued'])) ? 'true' : 'false' }}">
                            <span><i class="bi bi-clipboard2-check me-2"></i> Order AC</span>
                            <i class="bi bi-chevron-down small"></i>
                        </a>
                        <div class="collapse {{ (request()->routeIs('orders.*') && in_array(request('workflow'), ['ac', 'issued'])) ? 'show' : '' }}" id="orderAcMenu">
                            <ul class="nav flex-column ms-3 mt-1 ps-2 border-start border-white-50">
                                <li class="nav-item">
                                    <a href="{{ route('orders.index', ['workflow' => 'ac']) }}" class="nav-link py-1 {{ request('workflow') === 'ac' ? 'active' : '' }}">
                                        Order AC
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('orders.index', ['workflow' => 'issued']) }}" class="nav-link py-1 {{ request('workflow') === 'issued' ? 'active' : '' }}">
                                        Issued Invoices
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link d-flex justify-content-between align-items-center {{ request()->routeIs('payment-management.*') || request()->routeIs('sale-returns.*') || request()->routeIs('retail-sale-returns.*') ? 'active' : '' }}"
                           data-bs-toggle="collapse" href="#paymentManagementMenu" role="button"
                           aria-expanded="{{ (request()->routeIs('payment-management.*') || request()->routeIs('sale-returns.*') || request()->routeIs('retail-sale-returns.*')) ? 'true' : 'false' }}">
                            <span><i class="bi bi-wallet2 me-2"></i> Payment Management</span>
                            <i class="bi bi-chevron-down small"></i>
                        </a>
                        <div class="collapse {{ (request()->routeIs('payment-management.*') || request()->routeIs('sale-returns.*') || request()->routeIs('retail-sale-returns.*')) ? 'show' : '' }}" id="paymentManagementMenu">
                            <ul class="nav flex-column ms-3 mt-1 ps-2 border-start border-white-50">
                                <li class="nav-item">
                                    <a href="{{ route('payment-management.index') }}" class="nav-link py-1 {{ request()->routeIs('payment-management.index') ? 'active' : '' }}">
                                        Order payments
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('payment-management.cheques') }}" class="nav-link py-1 {{ request()->routeIs('payment-management.cheques') ? 'active' : '' }}">
                                        Cheque Payments
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('sale-returns.index') }}" class="nav-link py-1 {{ request()->routeIs('sale-returns.*') ? 'active' : '' }}">
                                        Sale Returns
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('retail-sale-returns.index') }}" class="nav-link py-1 {{ request()->routeIs('retail-sale-returns.*') ? 'active' : '' }}">
                                        Retail Sales Returns
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    @endif
                    @if($u->can_access('reports.sales'))
                    <li class="nav-item mb-2">
                        <a href="{{ route('reports.show', 'sale') }}" class="nav-link {{ (request()->routeIs('reports.show') && request()->route() && request()->route()->parameter('report') === 'sale') ? 'active' : '' }}">
                            <i class="bi bi-graph-up me-2"></i> Sale Report
                        </a>
                    </li>
                    @endif
                    @if($u->can_access('inventory.view'))
                    <li class="nav-item mb-2">
                        <a href="{{ route('inventory.index') }}" class="nav-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}">
                            <i class="bi bi-box-seam me-2"></i> Inventory
                        </a>
                    </li>
                    @endif
                    @if($u->can_access('purchases.view'))
                    <li class="nav-item mt-2">
                        <a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#purchaseMenu" role="button" aria-expanded="false">
                            <span><i class="bi bi-cart-plus me-2"></i> Purchase</span>
                            <i class="bi bi-chevron-down small"></i>
                        </a>
                        <div class="collapse {{ request()->routeIs('purchases.*', 'suppliers.*', 'purchase-payment-management.*') || (request()->routeIs('reports.show') && request()->route() && request()->route()->parameter('report') === 'purchase') ? 'show' : '' }}" id="purchaseMenu">
                            <ul class="nav flex-column ms-3 mt-1 ps-2 border-start border-white-50">
                                <li class="nav-item"><a href="{{ route('suppliers.index') }}" class="nav-link py-1">Suppliers</a></li>
                                <li class="nav-item"><a href="{{ route('purchases.index') }}" class="nav-link py-1">Purchase List</a></li>
                                @if($u->can_access('purchases.create'))
                                <li class="nav-item"><a href="{{ route('purchases.create') }}" class="nav-link py-1 fw-bold">Add Purchase</a></li>
                                @endif
                                <li class="nav-item"><a href="{{ route('purchase-payment-management.index') }}" class="nav-link py-1 {{ request()->routeIs('purchase-payment-management.*') ? 'active' : '' }}">Payment Management</a></li>
                                <li class="nav-item"><a href="{{ route('reports.show', 'purchase') }}" class="nav-link py-1">Purchase Report</a></li>
                            </ul>
                        </div>
                    </li>
                    @endif

                    <li class="nav-item mb-2">
                        <a href="{{ route('essentials.todo.index') }}" class="nav-link {{ request()->routeIs('essentials.*') ? 'active' : '' }}">
                            <i class="bi bi-check2-square me-2"></i> To Do / Essentials
                        </a>
                    </li>

                    <!-- Manufacturing Mega Menu -->
                    @if($u->can_access('manufacturing.view'))
                    <li class="nav-item mt-2">
                        <a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#manufacturingMenu" role="button" aria-expanded="false">
                            <span><i class="bi bi-gear-wide-connected me-2"></i> Manufacturing</span>
                            <i class="bi bi-chevron-down small"></i>
                        </a>
                        <div class="collapse {{ request()->is('erp/jobs*') || request()->is('erp/manufacturing*') ? 'show' : '' }}" id="manufacturingMenu">
                            <ul class="nav flex-column ms-3 mt-1 ps-2 border-start border-white-50">
                                <li class="nav-item"><a href="{{ route('manufacturing.overview') }}" class="nav-link py-1 fw-bold text-white">Manufacturing Overview</a></li>
                                <li class="nav-item"><a href="{{ route('jobs.index') }}" class="nav-link py-1">All Jobs Listing</a></li>
                                <li class="nav-item mt-2"><span class="text-xs text-uppercase text-white-50 fw-bold small">Production Stages</span></li>
                                <li class="nav-item"><a href="{{ route('jobs.create') }}" class="nav-link py-1">Fabric Issue (New Job)</a></li>
                                <li class="nav-item"><a href="{{ route('manufacturing.cutting') }}" class="nav-link py-1">Cutting Section</a></li>
                                <li class="nav-item"><a href="{{ route('hourly-production.create') }}" class="nav-link py-1">Line Production (Hourly)</a></li>
                                <li class="nav-item"><a href="{{ route('manufacturing.washing-stock.index') }}" class="nav-link py-1">Washing Stock</a></li>
                                <li class="nav-item"><a href="{{ route('manufacturing.washing') }}" class="nav-link py-1">Washing / Gate Pass</a></li>
                                <li class="nav-item"><a href="{{ route('manufacturing.washing.report') }}" class="nav-link py-1">Washing Report</a></li>
                                <li class="nav-item"><a href="{{ route('manufacturing.qc') }}" class="nav-link py-1">After Wash / Packing (QC)</a></li>
                                <li class="nav-item"><a href="{{ route('manufacturing.final.index') }}" class="nav-link py-1 fw-bold text-warning">Final Checking / Receiving</a></li>
                                
                                
                                <!-- Management -->
                                <li class="nav-item mt-2"><span class="text-xs text-uppercase text-white-50 fw-bold small">Management</span></li>
                                <li class="nav-item"><a href="{{ route('sections.index') }}" class="nav-link py-1">Production Sections</a></li>
                                <li class="nav-item"><a href="{{ route('product-recipes.index') }}" class="nav-link py-1">Product Recipes (BOM)</a></li>
                                <li class="nav-item"><a href="{{ route('supervisor.dashboard') }}" class="nav-link py-1">Supervisor Dashboard</a></li>
                                <li class="nav-item"><a href="{{ route('reports.show', 'manufacturing') }}" class="nav-link py-1">Manufacturing Report</a></li>
                                <li class="nav-item"><a href="{{ route('reports.index') }}" class="nav-link py-1">All Reports</a></li>
                            </ul>
                        </div>
                    </li>

                    @endif {{-- manufacturing.view --}}

                    <!-- Products Mega Menu -->
                    @if($u->can_access('products.view'))
                    <li class="nav-item mt-2">
                        <a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#productsMenu" role="button" aria-expanded="false">
                            <span><i class="bi bi-box-seam me-2"></i> Products</span>
                            <i class="bi bi-chevron-down small"></i>
                        </a>
                        <div class="collapse {{ request()->is('erp/products*') || request()->is('erp/categories*') || request()->is('erp/brands*') ? 'show' : '' }}" id="productsMenu">
                            <ul class="nav flex-column ms-3 mt-1 ps-2 border-start border-white-50">
                                <li class="nav-item"><a href="{{ route('products.index') }}" class="nav-link py-1 fw-bold text-white">All Products</a></li>
                                <li class="nav-item"><a href="{{ route('products.create') }}" class="nav-link py-1">Add New Product</a></li>
                                
                                <li class="nav-item mt-2"><span class="text-xs text-uppercase text-white-50 fw-bold small">Inventory</span></li>
                                <li class="nav-item">@canRoute('stock-adjustment.create')
<a href="{{ route('stock-adjustment.create') }}" class="nav-link py-1">Stock Adjustment</a>
@endcanRoute
</li>
                                
                                <li class="nav-item mt-2"><span class="text-xs text-uppercase text-white-50 fw-bold small">Management</span></li>
                                <li class="nav-item"><a href="{{ route('categories.index') }}" class="nav-link py-1">Categories</a></li>
                                <li class="nav-item"><a href="{{ route('brands.index') }}" class="nav-link py-1">Brands</a></li>
                                <li class="nav-item"><a href="{{ route('units.index') }}" class="nav-link py-1">Units</a></li>
                                <li class="nav-item"><a href="{{ route('barcode.print') }}" class="nav-link py-1 fw-bold text-warning">Print Labels</a></li>
                            </ul>
                        </div>
                    </li>

                    @endif {{-- products.view --}}

                    {{-- Customers Management --}}
                    @if($u->can_access('customers.view'))
                    <li class="nav-item mb-2">
                        <a href="{{ route('erp.customers.index') }}" class="nav-link {{ request()->routeIs('erp.customers.*') ? 'active' : '' }}">
                            <i class="bi bi-people me-2"></i> Customers
                        </a>
                    </li>
                    @endif

                    {{-- Sales Routes --}}
                    @if($u->can_access('sales_persons.view'))
                    <li class="nav-item mt-2">
                        <a class="nav-link d-flex justify-content-between align-items-center {{ request()->routeIs('sales-routes.*') ? 'active' : '' }}" data-bs-toggle="collapse" href="#salesRoutesMenu" role="button" aria-expanded="{{ request()->routeIs('sales-routes.*') ? 'true' : 'false' }}">
                            <span><i class="bi bi-signpost-2 me-2"></i> Routes</span>
                            <i class="bi bi-chevron-down small"></i>
                        </a>
                        <div class="collapse {{ request()->routeIs('sales-routes.*') ? 'show' : '' }}" id="salesRoutesMenu">
                            <ul class="nav flex-column ms-3 mt-1 ps-2 border-start border-white-50">
                                <li class="nav-item">
                                    <a href="{{ route('sales-routes.index') }}" class="nav-link py-1 {{ request()->routeIs('sales-routes.index') || request()->routeIs('sales-routes.create') || request()->routeIs('sales-routes.edit') ? 'active-sub' : '' }}">
                                        Manage routes
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('sales-routes.live') }}" class="nav-link py-1 {{ request()->routeIs('sales-routes.live') ? 'active-sub' : '' }}">
                                        Started routes
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('sales-routes.runs') }}" class="nav-link py-1 {{ request()->routeIs('sales-routes.runs') || request()->routeIs('sales-routes.runs.show') ? 'active-sub' : '' }}">
                                        All routes
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    @endif

                    <!-- Employees Mega Menu -->
                    @if($u->can_access('employees.view'))
                    <li class="nav-item mt-2">
                        <a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#employeesMenu" role="button" aria-expanded="false">
                            <span><i class="bi bi-people me-2"></i> Employees</span>
                            <i class="bi bi-chevron-down small"></i>
                        </a>
                        <div class="collapse {{ request()->is('erp/employees*', 'erp/supervisors*') ? 'show' : '' }}" id="employeesMenu">
                            <ul class="nav flex-column ms-3 mt-1 ps-2 border-start border-white-50">
                                <li class="nav-item"><a href="{{ route('roles.index') }}" class="nav-link py-1">Manage Roles</a></li>
                                <li class="nav-item"><a href="{{ route('employees.create') }}" class="nav-link py-1">Add Employee</a></li>
                                <li class="nav-item"><a href="{{ route('employees.index') }}" class="nav-link py-1">Employee List</a></li>
                                @if($u->can_access('employees.create'))
                                <li class="nav-item"><a href="{{ route('employees.hik-connect-import.create') }}" class="nav-link py-1">Import Hik-Connect Employees</a></li>
                                @endif
                                <li class="nav-item"><a href="{{ route('supervisors.index') }}" class="nav-link py-1">Supervisors</a></li>
                                <li class="nav-item"><a href="{{ route('agents.index') }}" class="nav-link py-1">Sales Commission Agents</a></li>
                            </ul>
                        </div>
                    </li>

                    @endif {{-- employees.view --}}

                    {{-- Sales Persons Management --}}
                    @if($u->can_access('sales_persons.view'))
                    <li class="nav-item mt-2">
                        <a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#salesPersonMenu" role="button" aria-expanded="false">
                            <span><i class="bi bi-person-badge me-2"></i> Sales Persons</span>
                            <i class="bi bi-chevron-down small"></i>
                        </a>
                        <div class="collapse {{ request()->routeIs('sales-persons.*') || request()->routeIs('commissions.*') ? 'show' : '' }}" id="salesPersonMenu">
                            <ul class="nav flex-column ms-3 mt-1 ps-2 border-start border-white-50">
                                <li class="nav-item">
                                    <a href="{{ route('sales-persons.create') }}" class="nav-link py-1 {{ request()->routeIs('sales-persons.create') ? 'active-sub' : '' }}">
                                        Add Sales Person
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('sales-persons.index') }}" class="nav-link py-1 {{ request()->routeIs('sales-persons.index') ? 'active-sub' : '' }}">
                                        Sales Person List
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('commissions.index') }}" class="nav-link py-1 {{ request()->routeIs('commissions.index') ? 'active-sub' : '' }}">
                                        Sales Commissions
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('commissions.history') }}" class="nav-link py-1 {{ request()->routeIs('commissions.history') ? 'active-sub' : '' }}">
                                        Paid Sales Commission
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    @endif {{-- sales_persons.view --}}

                    {{-- HRM: single link; sub-pages use horizontal tabs inside HRM area --}}
                    @if($u->can_access('hrm.view'))
                    <li class="nav-item mb-2">
                        <a href="{{ route('hrm.index') }}" class="nav-link {{ request()->routeIs('hrm.*', 'hr.*', 'ot-requests.*', 'attendance.*', 'leave-types.*', 'leave-requests.*', 'departments.*', 'designations.*', 'shifts.*', 'roster.*', 'announcements.*', 'salary-components.*', 'employee-salaries.*', 'payroll-runs.*', 'employees.*') ? 'active' : '' }}">
                            <i class="bi bi-people-fill me-2"></i> HRM
                        </a>
                    </li>
                    @endif

                    <!-- Expenses (Ultimate POS style) -->
                    @if($u->can_access('expenses.view'))
                    <li class="nav-item mt-2">
                        <a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#expenseMenu" role="button" aria-expanded="false">
                            <span><i class="bi bi-wallet2 me-2"></i> Expenses</span>
                            <i class="bi bi-chevron-down small"></i>
                        </a>
                        <div class="collapse {{ request()->routeIs('expenses.*') || request()->routeIs('expense-categories.*') || request()->routeIs('field-expenses.sales-person.*') ? 'show' : '' }}" id="expenseMenu">
                            <ul class="nav flex-column ms-3 mt-1 ps-2 border-start border-white-50">
                                <li class="nav-item"><a href="{{ route('expenses.index') }}" class="nav-link py-1">Expense List</a></li>
                                <li class="nav-item"><a href="{{ route('expense-categories.index') }}" class="nav-link py-1">Expense Categories</a></li>
                                <li class="nav-item"><a href="{{ route('field-expenses.sales-person.index') }}" class="nav-link py-1 {{ request()->routeIs('field-expenses.sales-person.*') ? 'active-sub' : '' }}">Sales Person Expenses</a></li>
                            </ul>
                        </div>
                    </li>

                    @endif {{-- expenses.view --}}

                    @if($u->can_access('vehicles.view'))
                    <li class="nav-item mt-2">
                        <a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#vehicleMenu" role="button" aria-expanded="false">
                            <span><i class="bi bi-truck me-2"></i> Vehicle Management</span>
                            <i class="bi bi-chevron-down small"></i>
                        </a>
                        <div class="collapse {{ request()->routeIs('vehicles.*', 'drivers.*', 'vehicle-runs.*', 'field-expenses.drivers.*') ? 'show' : '' }}" id="vehicleMenu">
                            <ul class="nav flex-column ms-3 mt-1 ps-2 border-start border-white-50">
                                <li class="nav-item"><a href="{{ route('vehicles.index') }}" class="nav-link py-1">Vehicles</a></li>
                                <li class="nav-item"><a href="{{ route('drivers.index') }}" class="nav-link py-1">Drivers</a></li>
                                <li class="nav-item"><a href="{{ route('vehicle-runs.index') }}" class="nav-link py-1">Run Summary</a></li>
                                <li class="nav-item"><a href="{{ route('field-expenses.drivers.index') }}" class="nav-link py-1 {{ request()->routeIs('field-expenses.drivers.*') ? 'active-sub' : '' }}">Driver Expenses & Receipts</a></li>
                            </ul>
                        </div>
                    </li>
                    @endif {{-- vehicles.view --}}

                    @if($u->can_access('cheque.settings'))
                    <li class="nav-item mt-2">
                        @canRoute('erp.settings.cheque-notifications.edit')
<a href="{{ route('erp.settings.cheque-notifications.edit') }}" class="nav-link {{ request()->routeIs('erp.settings.cheque-notifications.*') ? 'active' : '' }}">
                            <i class="bi bi-whatsapp me-2"></i> Cheque / WhatsApp
                        </a>
@endcanRoute
                    </li>
                    @endif

                    @if($u->can_access('manufacturing.costs'))
                    <li class="nav-item mt-2">
                        <a href="{{ route('cost-analyst.index') }}" class="nav-link {{ request()->routeIs('cost-analyst.*') ? 'active' : '' }}">
                            <i class="bi bi-robot me-2"></i> Cost Analyst
                        </a>
                    </li>
                    @endif

                    @if($u->can_access('reports.view'))
                    <li class="nav-item mt-2">
                        <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                            <i class="bi bi-graph-up me-2"></i> Reports (Main)
                        </a>
                    </li>
                    @endif

                    {{-- Accounting --}}
                    @if($u->can_access('accounting.view'))
                    <li class="nav-item mt-2">
                        <a href="{{ url('accounting/dashboard') }}" class="nav-link {{ request()->is('accounting*', 'report/accounting*') ? 'active' : '' }}">
                            <i class="bi bi-journal-bookmark-fill me-2"></i> Accounting
                        </a>
                    </li>
                    @endif

                    <!-- User Management -->
                    @if($u->can_access('users.view') || $u->isSystemAdmin())
                    <li class="nav-item mt-2">
                        <a class="nav-link d-flex justify-content-between align-items-center {{ request()->is('erp/users*', 'erp/roles*') ? 'active' : '' }}"
                           data-bs-toggle="collapse" href="#usersMenu" role="button"
                           aria-expanded="{{ request()->is('erp/users*', 'erp/roles*') ? 'true' : 'false' }}">
                            <span><i class="bi bi-person-lock me-2"></i> Users</span>
                            <i class="bi bi-chevron-down small"></i>
                        </a>
                        <div class="collapse {{ request()->is('erp/users*', 'erp/roles*') ? 'show' : '' }}" id="usersMenu">
                            <ul class="nav flex-column ms-3 mt-1 ps-2 border-start border-white-50">
                                <li class="nav-item"><a href="{{ route('erp.users.index') }}" class="nav-link py-1 {{ request()->routeIs('erp.users.index') ? 'active' : '' }}">All Users</a></li>
                                <li class="nav-item"><a href="{{ route('erp.users.create') }}" class="nav-link py-1 {{ request()->routeIs('erp.users.create') ? 'active' : '' }}">Add User</a></li>
                                <li class="nav-item"><a href="{{ route('roles.index') }}" class="nav-link py-1 {{ request()->routeIs('roles.*') ? 'active' : '' }}">Roles & Permissions</a></li>
                            </ul>
                        </div>
                    </li>
                    @endif {{-- users.view --}}

                @endif {{-- end full admin menu --}}

                <!-- Logged-in user card + Logout (always visible) -->
                <div class="mt-auto pt-3 pb-2 px-1" style="border-top:1px solid rgba(255,255,255,0.15); margin-top:16px;">
                    <div class="d-flex align-items-center gap-2 px-2 mb-2">
                        <div class="rounded-circle bg-white bg-opacity-20 d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:36px;height:36px;">
                            <i class="bi bi-person-fill text-white" style="font-size:1.1rem;"></i>
                        </div>
                        <div style="min-width:0;">
                            <div class="text-white fw-semibold text-truncate" style="font-size:.85rem;max-width:180px;">
                                {{ Auth::user()->name }}
                            </div>
                            <div class="text-truncate" style="font-size:.72rem;color:rgba(255,255,255,.6);max-width:180px;">
                                {{ Auth::user()->email }}
                            </div>
                            @if(Auth::user()->roleModel)
                                <span class="badge bg-white bg-opacity-20 text-white" style="font-size:.65rem;">
                                    {{ Auth::user()->roleModel->label }}
                                </span>
                            @elseif(Auth::user()->role === 'admin')
                                <span class="badge bg-danger bg-opacity-80 text-white" style="font-size:.65rem;">Admin</span>
                            @endif
                        </div>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" id="sidebar-logout-form">
                        @csrf
                        <button type="submit" class="btn btn-sm w-100 text-start px-2 py-1"
                                style="background:rgba(255,255,255,0.08);color:rgba(255,255,255,0.85);border:1px solid rgba(255,255,255,0.15);border-radius:6px;">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </button>
                    </form>
                </div>
            </ul>
        </aside>

        <!-- Content -->
        <div class="content flex-grow-1">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if(request()->routeIs('hrm.*', 'hr.*', 'ot-requests.*', 'attendance.*', 'leave-types.*', 'leave-requests.*', 'departments.*', 'designations.*', 'shifts.*', 'roster.*', 'announcements.*', 'salary-components.*', 'employee-salaries.*', 'payroll-runs.*', 'employees.*'))
                @include('erp.hrm.nav')
            @endif

            @yield('content')
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        (function() {
            var btn = document.getElementById('erpMenuBtn');
            var backdrop = document.getElementById('erpSidebarBackdrop');
            var sidebar = document.getElementById('erpSidebar');
            if (btn) btn.addEventListener('click', function() { document.body.classList.toggle('erp-sidebar-open'); });
            if (backdrop) backdrop.addEventListener('click', function() { document.body.classList.remove('erp-sidebar-open'); });
            if (sidebar) sidebar.querySelectorAll('a').forEach(function(a) {
                a.addEventListener('click', function() { document.body.classList.remove('erp-sidebar-open'); });
            });
        })();
    </script>
    @yield('scripts')
    @stack('scripts')
</body>
</html>
