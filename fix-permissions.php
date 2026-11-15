<?php
/**
 * Laravel / cPanel permission fix (standalone — no Laravel bootstrap needed).
 *
 * Upload to project root (same folder as app/, storage/, .env), then open:
 *   https://YOUR-DOMAIN/fix-permissions.php?key=YOUR_DEPLOY_KEY
 *
 * If document root is public/ only:
 *   https://YOUR-DOMAIN/fix-permissions.php?key=...  (uses public/fix-permissions.php)
 *
 * Target: folders 755, files 644; storage + bootstrap/cache 775 / 664.
 * DELETE this file after use on production.
 */
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$root = __DIR__;
$key = isset($_GET['key']) ? (string) $_GET['key'] : '';
$useStorage777 = isset($_GET['storage777']) && $_GET['storage777'] === '1';

function envValue(string $root, string $name, string $default = ''): string
{
    $file = $root . DIRECTORY_SEPARATOR . '.env';
    if (!is_readable($file)) {
        return $default;
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return $default;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        if (!str_starts_with($line, $name . '=')) {
            continue;
        }

        return trim(trim(substr($line, strlen($name) + 1)), " \t\"'");
    }

    return $default;
}

$expected = envValue($root, 'DEPLOY_KEY', 'dermas-2026');

if ($key === '' || !hash_equals($expected, $key)) {
    http_response_code(403);
    exit("Forbidden.\nUse: fix-permissions.php?key=YOUR_DEPLOY_KEY\n(Set DEPLOY_KEY in .env — default in fresh installs: dermas-2026)\n");
}

function permString(string $path): string
{
    if (!file_exists($path)) {
        return 'n/a';
    }
    $p = @fileperms($path);
    if ($p === false) {
        return '?';
    }

    return substr(sprintf('%o', $p & 0777), -3);
}

function isWritablePath(string $relative): bool
{
    $r = str_replace('\\', '/', $relative);

    return $r === 'storage'
        || str_starts_with($r, 'storage/')
        || $r === 'bootstrap/cache'
        || str_starts_with($r, 'bootstrap/cache/');
}

/**
 * @return array{dirs: int, files: int, failed: list<string>}
 */
function fixWithPhp(string $root, bool $storage777): array
{
    $stats = ['dirs' => 0, 'files' => 0, 'failed' => []];
    $rootNorm = rtrim(str_replace('\\', '/', $root), '/');

    @chmod($root, 0755);

    $priority = [
        $root . '/storage',
        $root . '/bootstrap/cache',
    ];
    foreach ($priority as $dir) {
        if (!is_dir($dir)) {
            continue;
        }
        @chmod($dir, $storage777 ? 0777 : 0775);
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $path = $item->getPathname();
        $relative = ltrim(str_replace('\\', '/', substr($path, strlen($rootNorm))), '/');

        if ($relative === '.git' || str_starts_with($relative, '.git/')) {
            continue;
        }

        if (basename($path) === 'fix-permissions.php' || basename($path) === 'set-permissions.php') {
            continue;
        }

        $writable = isWritablePath($relative);
        $mode = $item->isDir()
            ? ($writable ? ($storage777 ? 0777 : 0775) : 0755)
            : ($writable ? ($storage777 ? 0666 : 0664) : 0644);

        if (@chmod($path, $mode)) {
            if ($item->isDir()) {
                $stats['dirs']++;
            } else {
                $stats['files']++;
            }
        } else {
            $stats['failed'][] = $relative . ' (was ' . permString($path) . ')';
        }
    }

    return $stats;
}

function fixWithShell(string $root, bool $storage777): bool
{
    if (!function_exists('exec')) {
        return false;
    }

    $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
    if (in_array('exec', $disabled, true)) {
        return false;
    }

    $q = escapeshellarg($root);
    $storageMode = $storage777 ? '777' : '775';
    $cmds = [
        "find {$q} -type d ! -path '*/.git/*' -exec chmod 755 {} + 2>/dev/null",
        "find {$q} -type f ! -path '*/.git/*' -exec chmod 644 {} + 2>/dev/null",
        "chmod -R {$storageMode} {$q}/storage {$q}/bootstrap/cache 2>/dev/null",
    ];

    foreach ($cmds as $cmd) {
        exec($cmd);
    }

    return true;
}

function storageWritableTest(string $root): bool
{
    $test = $root . '/storage/framework/cache/perm_test_' . uniqid('', true) . '.txt';
    $dir = dirname($test);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    if (@file_put_contents($test, 'ok') === false) {
        return false;
    }
    @unlink($test);

    return true;
}

echo "DERMAS — Fix file permissions\n";
echo "Root: {$root}\n";
echo 'Before storage/: ' . permString($root . '/storage') . " | bootstrap/cache: " . permString($root . '/bootstrap/cache') . "\n";
echo str_repeat('-', 55) . "\n";

$stats = fixWithPhp($root, $useStorage777);

if ($stats['failed'] !== [] && PHP_OS_FAMILY !== 'Windows') {
    echo 'Some paths failed via PHP; trying shell find/chmod...' . "\n";
    fixWithShell($root, $useStorage777);
    $stats = fixWithPhp($root, $useStorage777);
}

echo "Directories updated: {$stats['dirs']} (755, storage 775)\n";
echo "Files updated: {$stats['files']} (644, storage 664)\n";
echo 'After storage/: ' . permString($root . '/storage') . " | bootstrap/cache: " . permString($root . '/bootstrap/cache') . "\n";

if (storageWritableTest($root)) {
    echo "\nOK — storage is writable (Laravel can cache & log).\n";
} else {
    echo "\nWARNING — storage still not writable.\n";
    echo "Try: fix-permissions.php?key=...&storage777=1\n";
    echo "Or cPanel → select storage + bootstrap/cache → Permissions → 775 → Recurse.\n";
}

if ($stats['failed'] !== []) {
    echo "\nFailed (" . count($stats['failed']) . ") — fix manually in File Manager:\n";
    foreach (array_slice($stats['failed'], 0, 25) as $path) {
        echo "  - {$path}\n";
    }
    if (count($stats['failed']) > 25) {
        echo '  ... +' . (count($stats['failed']) - 25) . " more\n";
    }
} else {
    echo "\nDone. Normal folders should be 755 (not 555).\n";
    echo "DELETE fix-permissions.php from server after this.\n";
}
