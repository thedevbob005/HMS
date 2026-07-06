<?php

declare(strict_types=1);

use Slim\Factory\AppFactory;
use App\Middleware\JsonBodyParserMiddleware;
use App\Middleware\SecurityHeadersMiddleware;

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
$app->add(new SecurityHeadersMiddleware());
$app->addRoutingMiddleware();

// Retrieve configuration settings
$appSettings = $container->get('settings')['app'];

// Add Error Middleware
$errorMiddleware = $app->addErrorMiddleware(
    $appSettings['debug'], // Display error details (should be true for development)
    true,                  // Log errors
    true                   // Log error details
);

// Centralized JSON Error Handler
$errorMiddleware->setDefaultErrorHandler(function (
    \Psr\Http\Message\ServerRequestInterface $request,
    \Throwable $exception,
    bool $displayErrorDetails,
    bool $logErrors,
    bool $logErrorDetails
) use ($app) {
    $statusCode = 500;
    $message = 'Something went wrong. Please try again.';

    if ($exception instanceof \Slim\Exception\HttpException) {
        $statusCode = $exception->getCode();
        $message = $exception->getMessage();
    } elseif ($exception instanceof \PDOException) {
        // Safe message for client
        $message = 'Database operation failed.';
    }

    $payload = [
        'success' => false,
        'message' => $message,
    ];

    if ($displayErrorDetails) {
        $payload['detail'] = $exception->getMessage();
        $payload['trace'] = explode("\n", $exception->getTraceAsString());
    }

    $response = $app->getResponseFactory()->createResponse();
    $response->getBody()->write((string)json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($statusCode);
});

// Register Routes
$routes = require dirname(__DIR__) . '/routes/api.php';
$routes($app);

// Run Application
$app->run();
