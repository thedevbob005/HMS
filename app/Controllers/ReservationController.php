<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ReservationService;
use App\Support\ApiResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;
use Slim\Exception\HttpForbiddenException;
use PDO;
use Exception;

class ReservationController
{
    private ReservationService $reservationService;
    private PDO $pdo;

    public function __construct(ReservationService $reservationService, PDO $pdo)
    {
        $this->reservationService = $reservationService;
        $this->pdo = $pdo;
    }

    private function checkPermission(int $userId, string $permission, Request $request): void
    {
        $stmt = $this->pdo->prepare('
            SELECT 1 
            FROM user_roles ur
            JOIN role_permissions rp ON ur.role_id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.id
            WHERE ur.user_id = :user_id AND p.name = :permission
            LIMIT 1
        ');
        $stmt->execute([
            ':user_id' => $userId,
            ':permission' => $permission
        ]);
        if (!$stmt->fetchColumn()) {
            throw new HttpForbiddenException($request, sprintf('You do not have the required permission (%s).', $permission));
        }
    }

    public function listReservations(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'reservations.view', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        $queryParams = $request->getQueryParams();
        $filters = [];
        if (!empty($queryParams['status'])) {
            $filters['status'] = $queryParams['status'];
        }
        if (!empty($queryParams['start_date'])) {
            $filters['start_date'] = $queryParams['start_date'];
        }
        if (!empty($queryParams['end_date'])) {
            $filters['end_date'] = $queryParams['end_date'];
        }

        $res = $this->reservationService->listReservations($hotelId, $filters);
        return ApiResponse::success($res, 'Reservations retrieved successfully.');
    }

    public function getReservation(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'reservations.view', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;
        $resId = $route ? (int)$route->getArgument('resId') : 0;

        $res = $this->reservationService->getReservation($hotelId, $resId);
        if (!$res) {
            return ApiResponse::error('Reservation not found.', 404);
        }

        return ApiResponse::success($res, 'Reservation retrieved successfully.');
    }

    public function createReservation(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'reservations.create', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        $data = (array)$request->getParsedBody();

        try {
            $res = $this->reservationService->createReservation($hotelId, $data, (int)$currentUser['user_id']);
            return ApiResponse::success($res, 'Reservation created successfully.', 201);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function cancelReservation(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'reservations.cancel', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;
        $resId = $route ? (int)$route->getArgument('resId') : 0;

        try {
            $res = $this->reservationService->cancelReservation($hotelId, $resId, (int)$currentUser['user_id']);
            return ApiResponse::success($res, 'Reservation cancelled successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function checkAvailability(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'reservations.view', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;
        $roomId = $route ? (int)$route->getArgument('roomId') : 0;

        $queryParams = $request->getQueryParams();
        $checkin = $queryParams['checkin_date'] ?? null;
        $checkout = $queryParams['checkout_date'] ?? null;

        if (!$checkin || !$checkout) {
            return ApiResponse::error('Both checkin_date and checkout_date parameters are required.', 400);
        }

        try {
            $available = $this->reservationService->checkRoomAvailability($hotelId, $roomId, $checkin, $checkout);
            return ApiResponse::success(['available' => $available], 'Availability checked successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }
}
