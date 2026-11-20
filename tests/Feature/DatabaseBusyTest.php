<?php

namespace Tests\Feature;

use App\Database\RetryingMySqlConnector;
use Tests\TestCase;

class DatabaseBusyTest extends TestCase
{
    public function test_detects_mysql_server_full_errors(): void
    {
        $this->assertTrue(RetryingMySqlConnector::isServerFull(new \PDOException('SQLSTATE[HY000] [1040] Too many connections')));
        $this->assertTrue(RetryingMySqlConnector::isServerFull(new \PDOException("SQLSTATE[HY000] [1203] User dermasap has already more than 'max_user_connections' active connections")));
        $this->assertFalse(RetryingMySqlConnector::isServerFull(new \PDOException('SQLSTATE[HY000] [2002] Connection refused')));
    }

    public function test_server_full_shows_friendly_busy_page_not_a_crash(): void
    {
        $this->app['router']->get('/__busy-test', function () {
            throw new \Illuminate\Database\QueryException('mysql', 'select * from sessions', [], new \PDOException('SQLSTATE[HY000] [1040] Too many connections'));
        });

        $res = $this->get('/__busy-test');
        $res->assertStatus(503)->assertHeader('Retry-After', '10')->assertSee('The system is busy right now')->assertDontSee('sessions');
        $this->getJson('/__busy-test')->assertStatus(503)->assertJson(['message' => 'The system is busy. Please try again in a few seconds.']);
    }

    public function test_mysql_connections_use_the_retrying_connector(): void
    {
        $this->assertInstanceOf(RetryingMySqlConnector::class, $this->app->make('db.connector.mysql'));
    }
}
