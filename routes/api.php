<?php

declare(strict_types=1);

use Slim\App;
use App\Controllers\HealthCheckController;
use App\Controllers\SampleProtectedController;
use App\Middleware\AuthenticationMiddleware;
use App\Middleware\HotelScopingMiddleware;

return function (App $app) {
    // Health Check Endpoint
    $app->get('/api/health', HealthCheckController::class);

    // Protected Sample Scoped Endpoint
    $app->get('/api/hotels/{hotelId}/sample', SampleProtectedController::class)
        ->add(HotelScopingMiddleware::class)
        ->add(AuthenticationMiddleware::class);
};
