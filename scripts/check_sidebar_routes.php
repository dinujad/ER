<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$html = file_get_contents(__DIR__.'/../modules/Accounting/Resources/views/layouts/workspace_sidebar.blade.php');
preg_match_all("/url\\('([^']+)'\\)/", $html, $m);
$paths = array_values(array_unique($m[1]));

$router = app('router');
$routes = collect($router->getRoutes())->map(function ($r) {
    return '/'.ltrim($r->uri(), '/');
})->all();

$missing = [];
foreach ($paths as $raw) {
    $path = '/'.ltrim(preg_replace('/\\?.*/', '', $raw), '/');
    $found = false;
    foreach ($routes as $uri) {
        // Convert {param} to regex
        $pattern = preg_replace('/\\{[^}]+\\}/', '[^/]+', $uri);
        $pattern = '#^'.str_replace('/', '\\/', $pattern).'$#';
        if (preg_match($pattern, $path)) {
            $found = true;
            break;
        }
    }
    if (! $found) {
        $missing[] = $path;
    }
}

echo "Sidebar links: ".count($paths).PHP_EOL;
echo "Unmatched routes: ".count($missing).PHP_EOL;
foreach ($missing as $p) {
    echo "MISSING $p".PHP_EOL;
}
