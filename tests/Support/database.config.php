<?php

declare(strict_types=1);

use Tempest\Database\Config\MysqlConfig;
use Tempest\Database\Config\PostgresConfig;
use Tempest\Database\Config\SQLiteConfig;

use function Tempest\env;
use function Tempest\internal_storage_path;

/*
 * The suite runs on SQLite unless `DB_DRIVER` says otherwise, which is what the
 * MySQL and PostgreSQL jobs in the test workflow set. Everything else is read
 * from the environment so the same file serves a service container and a local
 * server alike.
 */
$string = static function (string $key, string $default): string {
    $value = env($key, $default);

    return is_scalar($value) ? (string) $value : $default;
};

return match (env('DB_DRIVER')) {
    'mysql' => new MysqlConfig(
        host: $string('DB_HOST', '127.0.0.1'),
        port: $string('DB_PORT', '3306'),
        username: $string('DB_USERNAME', 'root'),
        password: $string('DB_PASSWORD', ''),
        database: $string('DB_DATABASE', 'tempest_query_builder'),
    ),
    'pgsql' => new PostgresConfig(
        host: $string('DB_HOST', '127.0.0.1'),
        port: $string('DB_PORT', '5432'),
        username: $string('DB_USERNAME', 'postgres'),
        password: $string('DB_PASSWORD', 'postgres'),
        database: $string('DB_DATABASE', 'tempest_query_builder'),
    ),
    default => new SQLiteConfig(
        path: internal_storage_path('database.sqlite'),
    ),
};
