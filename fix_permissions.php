<?php
/**
 * ONE-TIME permission fixer for Laravel on shared hosting / cPanel.
 * Upload to your Laravel root, visit it ONCE in the browser, then DELETE it immediately.
 *
 * Directories → 755   |   Files → 644
 * storage/**  → 775   |   bootstrap/cache → 775
 */

// Basic protection: ?token= must match ONE of these (edit this list before upload if you want).
$allowedTokens = [
    'CHANGE_THIS_SECRET_TOKEN_123',
    'mama_dana_pass_word_ekak',
];
$token = $_GET['token'] ?? '';
if ($token === '' || !in_array($token, $allowedTokens, true)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    die(
        "403 Forbidden.\n\n"
        ."The ?token= value must match EXACTLY one line inside fix_permissions.php (the \$allowedTokens array).\n\n"
        ."Try one of these URLs (use the one that matches the file on your server):\n"
        ."  ?token=CHANGE_THIS_SECRET_TOKEN_123\n"
        ."  ?token=mama_dana_pass_word_ekak\n\n"
        ."If neither works: open fix_permissions.php in cPanel File Manager → Edit → check the token list.\n"
    );
}

set_time_limit(300);
$base    = __DIR__;
$log     = [];
$errors  = [];

/**
 * Recursively chmod directories and files under $path.
 */
function fixPerms(string $path, int $dirMode, int $fileMode, array &$log, array &$errors): void
{
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    // chmod the root directory itself first
    if (!chmod($path, $dirMode)) {
        $errors[] = "chmod($path) failed";
    }

    foreach ($items as $item) {
        $target = $item->isDir() ? $dirMode : $fileMode;
        if (!chmod($item->getPathname(), $target)) {
            $errors[] = "chmod({$item->getPathname()}) failed";
        } else {
            $log[] = sprintf('%s  %o  %s', $item->isDir() ? 'd' : 'f', $target, $item->getPathname());
        }
    }
}

// 1. Fix everything to 755 (dirs) / 644 (files) first
fixPerms($base, 0755, 0644, $log, $errors);

// 2. Give storage and bootstrap/cache write permission (775)
foreach (['storage', 'bootstrap/cache'] as $writable) {
    $dir = $base . DIRECTORY_SEPARATOR . $writable;
    if (is_dir($dir)) {
        fixPerms($dir, 0775, 0664, $log, $errors);
    }
}

// 3. Make artisan executable
$artisan = $base . '/artisan';
if (file_exists($artisan)) {
    chmod($artisan, 0755);
    $log[] = "artisan → 755";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Permission Fixer</title>
<style>
  body { font-family: monospace; background: #1a1a2e; color: #e0e0e0; padding: 24px; }
  h1 { color: #00d4ff; }
  .ok  { color: #4cff91; }
  .err { color: #ff4c4c; }
  .box { background: #16213e; border: 1px solid #0f3460; border-radius: 6px; padding: 16px; max-height: 400px; overflow-y: auto; font-size: 12px; }
  .warn { background: #ff4c4c; color: #fff; padding: 10px 16px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; }
</style>
</head>
<body>
<h1>🔧 Laravel Permission Fixer</h1>

<?php if ($errors): ?>
<div class="warn">⚠️ <?= count($errors) ?> error(s) occurred. You may need SSH / root access to fix those.</div>
<?php endif; ?>

<p class="ok">✅ Fixed: <?= count($log) ?> items</p>

<?php if ($errors): ?>
<h3 class="err">Errors (<?= count($errors) ?>):</h3>
<div class="box">
<?php foreach ($errors as $e): ?>
  <div class="err"><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<h3>Summary of changes:</h3>
<div class="box">
<?php foreach (array_slice($log, 0, 200) as $l): ?>
  <div><?= htmlspecialchars($l) ?></div>
<?php endforeach; ?>
<?php if (count($log) > 200): ?>
  <div>... and <?= count($log) - 200 ?> more items</div>
<?php endif; ?>
</div>

<br>
<div class="warn">🚨 DELETE this file (fix_permissions.php) from your server NOW! Do not leave it accessible.</div>
</body>
</html>
