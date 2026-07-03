<?php

declare(strict_types=1);

use Slim\App;
use App\Controllers\HealthCheckController;
use App\Controllers\SampleProtectedController;
use App\Controllers\HotelController;
use App\Controllers\UserController;
use App\Controllers\RoomTypeController;
use App\Controllers\RoomController;
use App\Middleware\AuthenticationMiddleware;
use App\Middleware\HotelScopingMiddleware;

return function (App $app) {
    // Health Check Endpoint
    $app->get('/api/health', HealthCheckController::class);

    // Public authentication endpoint
    $app->post('/api/login', UserController::class . ':login');

    // Public / Protected Global routes (without Hotel Scoping)
    $app->group('/api', function ($group) {
        // Hotel Groups
        $group->get('/hotel-groups', HotelController::class . ':listGroups');
        $group->post('/hotel-groups', HotelController::class . ':createGroup');

        // Hotels
        $group->get('/hotels', HotelController::class . ':listHotels');
        $group->post('/hotels', HotelController::class . ':createHotel');

        // Users & Roles
        $group->get('/users', UserController::class . ':listUsers');
        $group->post('/users', UserController::class . ':createUser');
        $group->post('/users/{userId}/access', UserController::class . ':updateAccess');
        $group->get('/roles', UserController::class . ':listRoles');
    })->add(AuthenticationMiddleware::class);

    // Hotel Scoped Routes (require HotelScopingMiddleware)
    $app->group('/api/hotels/{hotelId}', function ($group) {
        // Sample
        $group->get('/sample', SampleProtectedController::class);

        // Room Types
        $group->get('/room-types', RoomTypeController::class . ':listRoomTypes');
        $group->post('/room-types', RoomTypeController::class . ':createRoomType');
        $group->post('/room-types/{roomTypeId}/rates', RoomTypeController::class . ':configureRates');
        $group->get('/room-types/{roomTypeId}/calculate-rate', RoomTypeController::class . ':calculateRate');

        // Rooms
        $group->get('/rooms', RoomController::class . ':listRooms');
        $group->post('/rooms', RoomController::class . ':createRoom');
        $group->post('/rooms/{roomId}/status', RoomController::class . ':changeStatus');
    })
    ->add(HotelScopingMiddleware::class)
    ->add(AuthenticationMiddleware::class);
};
