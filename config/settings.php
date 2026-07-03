<?php

declare(strict_types=1);

use Dotenv\Dotenv;

// Load environment variables
if (file_exists(dirname(__DIR__) . '/.env')) {
    $dotenv = Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->safeLoad();
}

return [
    'app' => [
        'name' => 'HMS Core API',
        'env' => $_ENV['APP_ENV'] ?? 'production',
        'debug' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
        'url' => $_ENV['APP_URL'] ?? 'http://localhost:8000',
    ],
    'db' => [
        'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'port' => $_ENV['DB_PORT'] ?? '3306',
        'database' => $_ENV['DB_DATABASE'] ?? 'hms',
        'username' => $_ENV['DB_USERNAME'] ?? 'root',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],
    'logger' => [
        'name' => 'hms_app',
        'path' => dirname(__DIR__) . '/storage/logs/app.log',
        'level' => $_ENV['LOG_LEVEL'] ?? 'info',
    ],
];
