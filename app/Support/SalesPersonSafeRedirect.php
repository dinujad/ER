<?php

namespace App\Support;

use Illuminate\Http\Request;

class SalesPersonSafeRedirect
{
    /**
     * Allow only same-app /sales-person paths (relative or absolute).
     */
    public static function path(?string $to, ?Request $request = null): ?string
    {
        if (! is_string($to)) {
            return null;
        }

        $to = trim($to);
        if ($to === '' || str_starts_with($to, '//')) {
            return null;
        }

        $isAbsolute = (bool) preg_match('#^https?://#i', $to);
        if (! $isAbsolute && ! str_starts_with($to, '/')) {
            return null;
        }

        $parts = parse_url($isAbsolute ? $to : 'http://placeholder.local'.$to);
        if ($parts === false) {
            return null;
        }

        $path = $parts['path'] ?? '';
        if ($path !== '/sales-person' && ! str_starts_with($path, '/sales-person/')) {
            return null;
        }

        if ($isAbsolute) {
            $host = strtolower((string) ($parts['host'] ?? ''));
            $allowed = [];
            if ($request) {
                $allowed[] = strtolower($request->getHost());
            }
            $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
            if (is_string($appHost) && $appHost !== '') {
                $allowed[] = strtolower($appHost);
            }
            if ($host === '' || ! in_array($host, $allowed, true)) {
                return null;
            }
        }

        $redirect = $path;
        if (! empty($parts['query'])) {
            $redirect .= '?'.$parts['query'];
        }
        if (! empty($parts['fragment'])) {
            $redirect .= '#'.$parts['fragment'];
        }

        return $redirect;
    }
}
