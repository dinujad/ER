<?php
/**
 * Clear purchases, inventory stock history, expenses + related journals (cPanel, no SSH).
 *
 * Project root (same folder as app/, .env):
 *   https://YOUR-DOMAIN/reset-operations.php?key=YOUR_DEPLOY_KEY
 *
 * If document root is public/:
 *   https://YOUR-DOMAIN/reset-operations.php?key=YOUR_DEPLOY_KEY
 *
 * Alternative Laravel route:
 *   https://YOUR-DOMAIN/erp/deploy/YOUR_DEPLOY_KEY/reset-operations
 */
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$root = __DIR__;
if (! is_file($root . DIRECTORY_SEPARATOR . 'artisan')) {
    $root = dirname(__DIR__);
}

require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$key = isset($_GET['key']) ? (string) $_GET['key'] : '';
$expected = trim((string) env('DEPLOY_KEY', 'dermas-2026'));

if ($key === '' || ! hash_equals($expected, $key)) {
    http_response_code(403);
    exit("Forbidden.\nUse: reset-operations.php?key=YOUR_DEPLOY_KEY\n(Set DEPLOY_KEY in .env)\n");
}

try {
    $result = (new App\Services\OperationsDataReset())->reset();
    echo "Purchases + inventory + expenses reset complete.\n";
    echo "Products, orders, manufacturing, users, suppliers, expense categories were kept.\n\n";
    echo 'Tables truncated: ' . implode(', ', $result['truncated'] ?: ['(none)']) . "\n";
    echo 'Tables missing (skipped): ' . implode(', ', $result['skipped'] ?: ['(none)']) . "\n";
    echo 'Material stock qty reset to 0: ' . $result['materials_reset'] . " row(s)\n";
    echo 'Journal entries removed: ' . $result['journals_removed'] . "\n";
    echo 'Accounting postings removed: ' . $result['postings_removed'] . "\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "RESET OPERATIONS FAILED\n";
    echo 'Exception: ' . get_class($e) . "\n";
    echo 'Message: ' . $e->getMessage() . "\n";
    echo 'Location: ' . $e->getFile() . ':' . $e->getLine() . "\n";
}
