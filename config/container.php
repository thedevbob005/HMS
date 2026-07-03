<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

$containerBuilder = new ContainerBuilder();

$containerBuilder->addDefinitions([
    'settings' => function () {
        return require __DIR__ . '/settings.php';
    },

    PDO::class => function (ContainerInterface $container) {
        $settings = $container->get('settings')['db'];
        
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $settings['host'],
            $settings['port'],
            $settings['database'],
            $settings['charset']
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        return new PDO($dsn, $settings['username'], $settings['password'], $options);
    },

    LoggerInterface::class => function (ContainerInterface $container) {
        $settings = $container->get('settings')['logger'];
        $logger = new Logger($settings['name']);
        
        $level = match (strtolower($settings['level'])) {
            'debug' => Level::Debug,
            'info' => Level::Info,
            'notice' => Level::Notice,
            'warning' => Level::Warning,
            'error' => Level::Error,
            'critical' => Level::Critical,
            'alert' => Level::Alert,
            'emergency' => Level::Emergency,
            default => Level::Info,
        };

        // Ensure directory exists
        $dir = dirname($settings['path']);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $logger->pushHandler(new StreamHandler($settings['path'], $level));
        return $logger;
    },
]);

return $containerBuilder->build();
