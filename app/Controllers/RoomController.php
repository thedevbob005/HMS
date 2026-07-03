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

class RoomController
{
    private RoomService $roomService;

    public function __construct(RoomService $roomService)
    {
        $this->roomService = $roomService;
    }

    public function listRooms(Request $request, Response $response): Response
    {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        $queryParams = $request->getQueryParams();
        $filters = [];
        if (!empty($queryParams['status'])) {
            $filters['status'] = $queryParams['status'];
        }
        if (!empty($queryParams['room_type_id'])) {
            $filters['room_type_id'] = $queryParams['room_type_id'];
        }

        $rooms = $this->roomService->listRooms($hotelId, $filters);
        return ApiResponse::success($rooms, 'Rooms retrieved successfully.');
    }

    public function createRoom(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        $data = (array)$request->getParsedBody();

        try {
            RoomValidator::validateRoom($data);
            $room = $this->roomService->createRoom($hotelId, $data, (int)$currentUser['user_id']);
            return ApiResponse::success($room, 'Room created successfully.', 201);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function changeStatus(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;
        $roomId = $route ? (int)$route->getArgument('roomId') : 0;

        $data = (array)$request->getParsedBody();

        try {
            RoomValidator::validateStatusChange($data);
            $room = $this->roomService->updateRoomStatus(
                $hotelId,
                $roomId,
                $data['status'],
                $data['reason'] ?? null,
                (int)$currentUser['user_id']
            );
            return ApiResponse::success($room, 'Room status updated successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }
}
