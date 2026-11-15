<?php

namespace App\Services;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

class FixFilesystemPermissions
{
    /**
     * @return array{dirs: int, files: int, errors: list<string>}
     */
    public function run(?string $root = null): array
    {
        $root = $root ?? base_path();
        $stats = ['dirs' => 0, 'files' => 0, 'errors' => []];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $root,
                FilesystemIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $rootNorm = rtrim(str_replace('\\', '/', $root), '/');

        foreach ($iterator as $item) {
            $path = $item->getPathname();
            $relative = ltrim(str_replace('\\', '/', substr($path, strlen($rootNorm))), '/');

            if ($relative === '.git' || str_starts_with($relative, '.git/')) {
                continue;
            }

            $writable = $this->isWritablePath($relative);

            try {
                if ($item->isDir()) {
                    chmod($path, $writable ? 0775 : 0755);
                    $stats['dirs']++;
                } elseif ($item->isFile()) {
                    chmod($path, $writable ? 0664 : 0644);
                    $stats['files']++;
                }
            } catch (Throwable $e) {
                $stats['errors'][] = "{$relative}: {$e->getMessage()}";
            }
        }

        return $stats;
    }

    private function isWritablePath(string $relative): bool
    {
        return $relative === 'storage'
            || str_starts_with($relative, 'storage/')
            || $relative === 'bootstrap/cache'
            || str_starts_with($relative, 'bootstrap/cache/');
    }
}
