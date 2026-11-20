<?php

namespace App\Database;

use Illuminate\Database\Connectors\MySqlConnector;
use PDOException;

/**
 * MySQL connector that waits briefly and retries when the server is full
 * ("1040 Too many connections" / "1203 max_user_connections"), instead of failing the request at once.
 * Short traffic spikes on shared hosting then pass unnoticed; a real outage still fails after ~1.5 s.
 */
class RetryingMySqlConnector extends MySqlConnector
{
    private const ATTEMPTS = 4;

    private const WAIT_MS = [200, 400, 800];

    public function createConnection($dsn, array $config, array $options)
    {
        $attempt = 0;
        while (true) {
            try {
                return parent::createConnection($dsn, $config, $options);
            } catch (PDOException $e) {
                $attempt++;
                if (!self::isServerFull($e) || $attempt >= self::ATTEMPTS) {
                    throw $e;
                }
                usleep(self::WAIT_MS[$attempt - 1] * 1000);
            }
        }
    }

    public static function isServerFull(\Throwable $e): bool
    {
        $msg = $e->getMessage();

        return str_contains($msg, '[1040]') || str_contains($msg, '[1203]')
            || stripos($msg, 'Too many connections') !== false
            || stripos($msg, 'max_user_connections') !== false;
    }
}
