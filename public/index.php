<?php

declare(strict_types=1);

use Slim\Factory\AppFactory;
use App\Middleware\JsonBodyParserMiddleware;

require dirname(__DIR__) . '/vendor/autoload.php';

// Instantiate PHP-DI Container
$container = require dirname(__DIR__) . '/config/container.php';

// Set Container to AppFactory
AppFactory::setContainer($container);

// Create App
$app = AppFactory::create();

// Add Routing & Body Parsing Middleware
$app->addBodyParsingMiddleware();
$app->add(new JsonBodyParserMiddleware());
$app->addRoutingMiddleware();

// Retrieve configuration settings
$appSettings = $container->get('settings')['app'];

// Add Error Middleware
$errorMiddleware = $app->addErrorMiddleware(
    $appSettings['debug'], // Display error details (should be true for development)
    true,                  // Log errors
    true                   // Log error details
);

// Register Routes
$routes = require dirname(__DIR__) . '/routes/api.php';
$routes($app);

// Run Application
$app->run();
