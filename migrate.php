<?php
/**
 * Run Laravel migrations via browser (no route cache needed).
 *
 * Upload to project root (same folder as artisan, .env), then open:
 *   https://YOUR-DOMAIN/migrate.php?key=YOUR_DEPLOY_KEY
 *
 * If document root is public/ only, use public/migrate.php instead.
 *
 * DELETE this file after use on production.
 */
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$root = __DIR__;
$key  = isset($_GET['key']) ? (string) $_GET['key'] : '';

function envValue(string $root, string $name, string $default = ''): string
{
    $file = $root . DIRECTORY_SEPARATOR . '.env';
    if (!is_readable($file)) {
        return $default;
    }

    foreach (file($file, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        if ($k !== $name) {
            continue;
        }

        return trim($v, " \t\"'");
    }

    return $default;
}

$expected = envValue($root, 'DEPLOY_KEY', 'dermas-2026');

if ($key === '' || !hash_equals($expected, $key)) {
    http_response_code(403);
    exit("Forbidden.\nUse: migrate.php?key=YOUR_DEPLOY_KEY\n(Set DEPLOY_KEY in .env)\n");
}

if (!is_file($root . '/vendor/autoload.php') || !is_file($root . '/artisan')) {
    http_response_code(500);
    exit("Error: vendor/autoload.php or artisan not found.\nMake sure this file is in the Laravel project root.\n");
}

echo "DERMAS — Run migrations\n";
echo "Root: {$root}\n";
echo str_repeat('-', 55) . "\n";

try {
    require $root . '/vendor/autoload.php';
    $app = require_once $root . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->call('migrate', ['--force' => true]);
    echo $kernel->output();
    echo "\nDone.\n";
    echo "DELETE migrate.php from server after this.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "Migration failed:\n" . $e->getMessage() . "\n";
}
