<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\RoomService;
use App\Support\ApiResponse;
use App\Validators\RoomValidator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;
use Exception;

class RoomTypeController
{
    private RoomService $roomService;

    public function __construct(RoomService $roomService)
    {
        $this->roomService = $roomService;
    }

    public function listRoomTypes(Request $request, Response $response): Response
    {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        $types = $this->roomService->listRoomTypes($hotelId);
        return ApiResponse::success($types, 'Room types retrieved successfully.');
    }

    public function createRoomType(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        $data = (array)$request->getParsedBody();

        try {
            RoomValidator::validateRoomType($data);
            $type = $this->roomService->createRoomType($hotelId, $data, (int)$currentUser['user_id']);
            return ApiResponse::success($type, 'Room type created successfully.', 201);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function configureRates(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;
        $roomTypeId = $route ? (int)$route->getArgument('roomTypeId') : 0;

        $data = (array)$request->getParsedBody();

        try {
            RoomValidator::validateRateRules($data);
            $this->roomService->configureRates($hotelId, $roomTypeId, $data, (int)$currentUser['user_id']);
            return ApiResponse::success(null, 'Room type rates updated successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function calculateRate(Request $request, Response $response): Response
    {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $roomTypeId = $route ? (int)$route->getArgument('roomTypeId') : 0;

        $queryParams = $request->getQueryParams();
        $date = $queryParams['date'] ?? date('Y-m-d');

        try {
            $rate = $this->roomService->calculateRoomRate($roomTypeId, $date);
            return ApiResponse::success([
                'room_type_id' => $roomTypeId,
                'date' => $date,
                'calculated_rate' => $rate
            ], 'Calculated rate retrieved successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }
}
