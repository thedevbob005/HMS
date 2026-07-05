<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\StayService;
use App\Support\ApiResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;
use Slim\Exception\HttpForbiddenException;
use PDO;
use Exception;

class StayController
{
    private StayService $stayService;
    private PDO $pdo;

    public function __construct(StayService $stayService, PDO $pdo)
    {
        $this->stayService = $stayService;
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

    private function hasPermission(int $userId, string $permission): bool
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
        return (bool)$stmt->fetchColumn();
    }

    public function listStays(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'stays.view', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        $queryParams = $request->getQueryParams();
        $filters = [];
        if (!empty($queryParams['status'])) {
            $filters['status'] = $queryParams['status'];
        }

        $stays = $this->stayService->listStays($hotelId, $filters);
        return ApiResponse::success($stays, 'Stays retrieved successfully.');
    }

    public function getStay(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'stays.view', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;
        $stayId = $route ? (int)$route->getArgument('stayId') : 0;

        $stay = $this->stayService->getStay($hotelId, $stayId);
        if (!$stay) {
            return ApiResponse::error('Stay not found.', 404);
        }

        return ApiResponse::success($stay, 'Stay retrieved successfully.');
    }

    public function checkin(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'checkin.perform', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        $data = (array)$request->getParsedBody();

        try {
            $stay = $this->stayService->performCheckin($hotelId, $data, (int)$currentUser['user_id']);
            return ApiResponse::success($stay, 'Check-in completed successfully.', 201);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function roomShift(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'stays.room_shift', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;
        $stayId = $route ? (int)$route->getArgument('stayId') : 0;

        $data = (array)$request->getParsedBody();

        try {
            $this->stayService->performRoomShift($hotelId, $stayId, $data, (int)$currentUser['user_id']);
            return ApiResponse::success(null, 'Room shift completed successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function postFolioItem(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'stays.update', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;
        $stayId = $route ? (int)$route->getArgument('stayId') : 0;

        $data = (array)$request->getParsedBody();

        try {
            $item = $this->stayService->postFolioItem($hotelId, $stayId, $data, (int)$currentUser['user_id']);
            return ApiResponse::success($item, 'Folio item posted successfully.', 201);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function collectPayment(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'payments.collect', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;
        $stayId = $route ? (int)$route->getArgument('stayId') : 0;

        $data = (array)$request->getParsedBody();

        try {
            $res = $this->stayService->collectPayment($hotelId, $stayId, $data, (int)$currentUser['user_id']);
            return ApiResponse::success($res, 'Payment recorded successfully.', 201);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function checkout(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $userId = (int)$currentUser['user_id'];
        $this->checkPermission($userId, 'checkout.perform', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;
        $stayId = $route ? (int)$route->getArgument('stayId') : 0;

        $data = (array)$request->getParsedBody();
        
        $hasOverridePermission = $this->hasPermission($userId, 'stays.override_charges');

        try {
            $stay = $this->stayService->performCheckout($hotelId, $stayId, $data, $userId, $hasOverridePermission);
            return ApiResponse::success($stay, 'Checkout completed successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }
}
