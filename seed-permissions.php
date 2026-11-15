<?php
/**
 * Seed roles + Admin permission keys (cPanel, no SSH).
 *
 * Project root (same folder as app/, .env):
 *   https://YOUR-DOMAIN/seed-permissions.php?key=YOUR_DEPLOY_KEY
 *
 * If document root is public/:
 *   https://YOUR-DOMAIN/seed-permissions.php?key=YOUR_DEPLOY_KEY
 *
 * Alternative Laravel route:
 *   https://YOUR-DOMAIN/erp/deploy/YOUR_DEPLOY_KEY/seed-permissions
 */
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$root = __DIR__;
if (!is_file($root . DIRECTORY_SEPARATOR . 'artisan')) {
    $root = dirname(__DIR__);
}

require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$key = isset($_GET['key']) ? (string) $_GET['key'] : '';
$expected = trim((string) env('DEPLOY_KEY', 'dermas-2026'));

if ($key === '' || !hash_equals($expected, $key)) {
    http_response_code(403);
    exit("Forbidden.\nUse: seed-permissions.php?key=YOUR_DEPLOY_KEY\n(Set DEPLOY_KEY in .env)\n");
}

try {
    Illuminate\Support\Facades\Artisan::call('db:seed', [
        '--class' => Database\Seeders\RoleSeeder::class,
        '--force' => true,
    ]);

    $roles = App\Models\Role::query()->orderBy('label')->get(['name', 'label', 'permissions']);
    echo "Roles & permissions seeded.\n";
    echo "Admin role now has all keys from config/permissions.php.\n";
    echo "Other roles were not overwritten.\n\n";
    echo Illuminate\Support\Facades\Artisan::output();
    echo "Roles:\n";
    foreach ($roles as $role) {
        $count = is_array($role->permissions) ? count($role->permissions) : 0;
        echo '- ' . $role->label . ' (' . $role->name . ') — ' . $count . " permission(s)\n";
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo "SEED PERMISSIONS FAILED\n";
    echo 'Exception: ' . get_class($e) . "\n";
    echo 'Message: ' . $e->getMessage() . "\n";
    echo 'Location: ' . $e->getFile() . ':' . $e->getLine() . "\n";
}
