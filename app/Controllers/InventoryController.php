<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\InventoryService;
use App\Support\ApiResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;
use Slim\Exception\HttpForbiddenException;
use PDO;
use Exception;

class InventoryController
{
    private InventoryService $inventoryService;
    private PDO $pdo;

    public function __construct(InventoryService $inventoryService, PDO $pdo)
    {
        $this->inventoryService = $inventoryService;
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

    public function listItems(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'inventory.view', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        try {
            $items = $this->inventoryService->listInventoryItems($hotelId);
            return ApiResponse::success($items, 'Inventory items retrieved successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function createItem(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'inventory.manage', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        $body = (array)$request->getParsedBody();

        try {
            $item = $this->inventoryService->createInventoryItem($hotelId, $body, (int)$currentUser['user_id']);
            return ApiResponse::success($item, 'Inventory item created successfully.', 201);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function adjustStock(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'inventory.adjust', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;
        $itemId = $route ? (int)$route->getArgument('itemId') : 0;

        $body = (array)$request->getParsedBody();
        $qty = (float)($body['quantity'] ?? 0.0);
        $cost = (float)($body['unit_cost'] ?? 0.0);
        $reason = trim($body['reason'] ?? '');

        try {
            $res = $this->inventoryService->adjustStock($hotelId, $itemId, $qty, $cost, $reason, (int)$currentUser['user_id']);
            return ApiResponse::success($res, 'Stock adjusted successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function listVendors(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'inventory.view', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        try {
            $vendors = $this->inventoryService->listVendors($hotelId);
            return ApiResponse::success($vendors, 'Vendors retrieved successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function createVendor(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'inventory.manage', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        $body = (array)$request->getParsedBody();

        try {
            $vendor = $this->inventoryService->createVendor($hotelId, $body, (int)$currentUser['user_id']);
            return ApiResponse::success($vendor, 'Vendor registered successfully.', 201);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function getLedger(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'inventory.view', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $itemId = $route ? (int)$route->getArgument('itemId') : 0;

        try {
            $ledger = $this->inventoryService->getLedger($itemId);
            return ApiResponse::success($ledger, 'Stock ledger retrieved successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }
}
