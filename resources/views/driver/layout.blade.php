<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>@yield('title', config('app.name') . ' - Driver')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --dermas-sidebar: linear-gradient(180deg, #1e3a5f 0%, #0d2137 100%); }
        body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; margin: 0; }
        .driver-wrap { display: flex; min-height: 100vh; }
        .driver-sidebar { width: 220px; min-width: 220px; background: var(--dermas-sidebar); color: #fff; padding: 1rem 0; }
        .driver-sidebar a { color: rgba(255,255,255,0.9); text-decoration: none; padding: 10px 16px; display: block; border-radius: 8px; margin: 2px 8px; transition: all 0.2s; }
        .driver-sidebar a:hover { background: rgba(255,255,255,0.1); color: #fff; }
        .driver-sidebar a.active { background: rgba(255,255,255,0.95); color: #1e3a5f; font-weight: 600; }
        .driver-sidebar .brand { padding: 8px 16px; font-weight: 700; font-size: 1.1rem; }
        .driver-content { flex: 1; padding: 1rem 1.25rem; max-width: 100%; }
        .driver-content .card { border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        @media (max-width: 767px) {
            .driver-sidebar { width: 100%; min-width: 0; }
            .driver-wrap { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="driver-wrap">
        <nav class="driver-sidebar">
            <div class="brand">{{ config('app.name') }} Driver</div>
            <a href="{{ route('driver.dashboard') }}" class="{{ request()->routeIs('driver.dashboard') && !request()->has('expenses') ? 'active' : '' }}"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
            <a href="{{ route('driver.dashboard') }}#expenses" class="{{ request()->routeIs('driver.dashboard') ? '' : '' }}"><i class="bi bi-receipt me-2"></i> Add expense</a>
            <a href="{{ route('driver.jobs.index') }}" class="{{ request()->routeIs('driver.jobs.*') ? 'active' : '' }}"><i class="bi bi-list-ul me-2"></i> My Jobs</a>
            <hr class="border-white-50 my-2 mx-2">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-link text-white text-decoration-none w-100 text-start px-3 py-2" style="border-radius:8px;"><i class="bi bi-box-arrow-right me-2"></i> Logout</button>
            </form>
        </nav>
        <main class="driver-content">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @yield('content')
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
