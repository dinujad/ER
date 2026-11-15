<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS — {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        html, body { height: 100%; margin: 0; padding: 0; overflow: hidden; font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; color: #334155; }
        body { background: #f1f5f9; }
        #pos-app { height: 100vh; width: 100vw; max-width: 100%; display: flex; flex-direction: column; overflow: hidden; box-sizing: border-box; }
        #pos-app > * { flex-shrink: 0; }
        #pos-app > .pos-wrapper { flex: 1; min-height: 0; }
        * { box-sizing: border-box; }
    </style>
    @stack('styles')
</head>
<body>
    <div id="pos-app">
        @yield('content')
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
