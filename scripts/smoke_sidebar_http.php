<?php

/**
 * Live DB smoke: hit every workspace sidebar GET link as admin.
 * Usage: php scripts/smoke_sidebar_http.php
 */
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$console = $app->make(Illuminate\Contracts\Console\Kernel::class);
$console->bootstrap();

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

$html = file_get_contents(__DIR__.'/../modules/Accounting/Resources/views/layouts/workspace_sidebar.blade.php');
preg_match_all("/url\\('([^']+)'\\)/", $html, $m);
$paths = array_values(array_unique(array_map(function ($raw) {
    return '/'.ltrim($raw, '/');
}, $m[1])));

$user = User::query()->where('email', 'admin@dermasapparel.com')->first()
    ?? User::query()->orderBy('id')->first();

if (! $user) {
    fwrite(STDERR, "No user found to authenticate.\n");
    exit(1);
}

Auth::login($user);

$fail = [];
$ok = 0;
foreach ($paths as $path) {
    $request = Request::create($path, 'GET');
    $request->setLaravelSession($app['session.store']);
    $app['session']->start();
    Auth::login($user);

    try {
        $response = $kernel->handle($request);
        $status = $response->getStatusCode();
        $content = method_exists($response, 'getContent') ? (string) $response->getContent() : '';
        $kernel->terminate($request, $response);

        $bad = $status >= 400;
        // Catch rendered exception pages that still return 200 in some setups
        if (str_contains($content, 'Whoops') || str_contains($content, 'ErrorException') || str_contains($content, 'QueryException')) {
            $bad = true;
        }
        if ($bad) {
            $msg = '';
            if (preg_match('/class="exception-message"[^>]*>(.*?)<\\/div>/s', $content, $mm)) {
                $msg = trim(strip_tags($mm[1]));
            } elseif (preg_match('/<title>(.*?)<\\/title>/si', $content, $mm)) {
                $msg = trim(strip_tags($mm[1]));
            }
            $fail[] = sprintf('%s → %d %s', $path, $status, $msg);
            echo 'FAIL '.$path.' ['.$status.'] '.$msg.PHP_EOL;
        } else {
            $ok++;
            echo 'OK   '.$path.' ['.$status.']'.PHP_EOL;
        }
    } catch (Throwable $e) {
        $fail[] = $path.' → EXCEPTION '.$e->getMessage();
        echo 'EXC  '.$path.' → '.$e->getMessage().PHP_EOL;
    }
}

echo PHP_EOL.'OK='.$ok.' FAIL='.count($fail).PHP_EOL;
file_put_contents(__DIR__.'/../storage/logs/sidebar-smoke.txt', implode(PHP_EOL, $fail));
exit(count($fail) ? 1 : 0);
