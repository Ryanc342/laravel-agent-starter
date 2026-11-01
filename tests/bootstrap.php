<?php

/*
| Tests run on MySQL, like the app, in a database of their own: the app's
| database name from .env plus "_test" (ezdev_template_test, or
| ezdev_template_<worktree>_test in a worktree), so parallel worktrees never
| share one and tests never touch the app's data. The database is created on
| the first run; RefreshDatabase migrates it and wraps each test in a
| transaction.
*/

require __DIR__.'/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createArrayBacked(dirname(__DIR__))->safeLoad();

$read = function (string $key, string $default) use ($dotenv): string {
    $value = $_SERVER[$key] ?? $_ENV[$key] ?? getenv($key);

    return is_string($value) && $value !== '' ? $value : ($dotenv[$key] ?? $default);
};

$database = $read('DB_DATABASE', 'laravel');
$database = str_ends_with($database, '_test') ? $database : $database.'_test';

putenv("DB_DATABASE={$database}");
$_ENV['DB_DATABASE'] = $_SERVER['DB_DATABASE'] = $database;

(new PDO(
    sprintf('mysql:host=%s;port=%s', $read('DB_HOST', '127.0.0.1'), $read('DB_PORT', '3306')),
    $read('DB_USERNAME', 'root'),
    $read('DB_PASSWORD', ''),
))->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
