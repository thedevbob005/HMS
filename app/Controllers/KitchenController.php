<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\KitchenService;
use App\Support\ApiResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;
use Slim\Exception\HttpForbiddenException;
use PDO;
use Exception;

class KitchenController
{
    private KitchenService $kitchenService;
    private PDO $pdo;

    public function __construct(KitchenService $kitchenService, PDO $pdo)
    {
        $this->kitchenService = $kitchenService;
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

    public function listMenuItems(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'kitchen.view', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        try {
            $items = $this->kitchenService->listKitchenItems($hotelId);
            return ApiResponse::success($items, 'Kitchen menu items retrieved.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function createMenuItem(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'kitchen.manage_items', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        $body = (array)$request->getParsedBody();

        try {
            $item = $this->kitchenService->createKitchenItem($hotelId, $body, (int)$currentUser['user_id']);
            return ApiResponse::success($item, 'Kitchen menu item created successfully.', 201);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function getRecipe(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'kitchen.view', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;
        $itemId = $route ? (int)$route->getArgument('itemId') : 0;

        try {
            $recipe = $this->kitchenService->getRecipe($hotelId, $itemId);
            return ApiResponse::success($recipe, 'Recipe details retrieved.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function saveRecipe(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'kitchen.manage_recipes', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;
        $itemId = $route ? (int)$route->getArgument('itemId') : 0;

        $body = (array)$request->getParsedBody();

        try {
            $recipe = $this->kitchenService->saveRecipe($hotelId, $itemId, $body, (int)$currentUser['user_id']);
            return ApiResponse::success($recipe, 'Recipe configured successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function getCostingSheet(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'kitchen.view_costs', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        try {
            $sheet = $this->kitchenService->getRecipeCostingSheet($hotelId);
            return ApiResponse::success($sheet, 'Recipe costing sheet values compiled.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function listOrders(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'kitchen.view', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        $queryParams = $request->getQueryParams();
        $filters = [];
        if (!empty($queryParams['status'])) {
            $filters['status'] = $queryParams['status'];
        }
        if (!empty($queryParams['room_number'])) {
            $filters['room_number'] = $queryParams['room_number'];
        }

        try {
            $orders = $this->kitchenService->listOrders($hotelId, $filters);
            return ApiResponse::success($orders, 'Kitchen room service orders loaded.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function createOrder(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'kitchen.manage_orders', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        $body = (array)$request->getParsedBody();

        try {
            $order = $this->kitchenService->createOrder($hotelId, $body, (int)$currentUser['user_id']);
            return ApiResponse::success($order, 'Room service kitchen order created successfully.', 201);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function updateStatus(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'kitchen.manage_orders', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;
        $orderId = $route ? (int)$route->getArgument('orderId') : 0;

        $body = (array)$request->getParsedBody();
        $status = trim($body['status'] ?? '');

        try {
            $order = $this->kitchenService->updateOrderStatus($hotelId, $orderId, $status, (int)$currentUser['user_id']);
            return ApiResponse::success($order, sprintf('Kitchen order status transitioned to %s.', $status));
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }
}
