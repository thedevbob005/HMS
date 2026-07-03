<?php

declare(strict_types=1);

use Slim\App;
use App\Controllers\HealthCheckController;

return function (App $app) {
    // Health Check Endpoint
    $app->get('/api/health', HealthCheckController::class);
};
