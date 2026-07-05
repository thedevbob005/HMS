<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\PurchaseService;
use App\Support\ApiResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;
use Slim\Exception\HttpForbiddenException;
use PDO;
use Exception;

class PurchaseController
{
    private PurchaseService $purchaseService;
    private PDO $pdo;

    public function __construct(PurchaseService $purchaseService, PDO $pdo)
    {
        $this->purchaseService = $purchaseService;
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

    public function listOrders(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'inventory.view', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        try {
            $orders = $this->purchaseService->listPurchaseOrders($hotelId);
            return ApiResponse::success($orders, 'Purchase orders retrieved successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function createOrder(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'inventory.manage', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        $body = (array)$request->getParsedBody();

        try {
            $order = $this->purchaseService->createPurchaseOrder($hotelId, $body, (int)$currentUser['user_id']);
            return ApiResponse::success($order, 'Purchase order generated successfully.', 201);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function getOrder(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'inventory.view', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;
        $poId = $route ? (int)$route->getArgument('poId') : 0;

        try {
            $order = $this->purchaseService->getPurchaseOrder($hotelId, $poId);
            if (!$order) {
                return ApiResponse::error('Purchase order not found.', 404);
            }
            return ApiResponse::success($order, 'Purchase order details loaded.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function approveOrder(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'purchases.approve', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;
        $poId = $route ? (int)$route->getArgument('poId') : 0;

        $body = (array)$request->getParsedBody();
        $status = trim($body['status'] ?? '');

        try {
            $order = $this->purchaseService->approvePurchaseOrder($hotelId, $poId, $status, (int)$currentUser['user_id']);
            return ApiResponse::success($order, sprintf('Purchase order status updated to %s.', $status));
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function receiveOrder(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'inventory.manage', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;
        $poId = $route ? (int)$route->getArgument('poId') : 0;

        $body = (array)$request->getParsedBody();

        try {
            $grn = $this->purchaseService->logGoodsReceipt($hotelId, $poId, $body, (int)$currentUser['user_id']);
            return ApiResponse::success($grn, 'Goods receipt note logged and stock balances updated.', 201);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function listReceipts(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'inventory.view', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        try {
            $receipts = $this->purchaseService->listGoodsReceipts($hotelId);
            return ApiResponse::success($receipts, 'Goods receipts retrieved successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function getReceipt(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'inventory.view', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;
        $grnId = $route ? (int)$route->getArgument('grnId') : 0;

        try {
            $receipt = $this->purchaseService->getGoodsReceipt($hotelId, $grnId);
            if (!$receipt) {
                return ApiResponse::error('Goods receipt not found.', 404);
            }
            return ApiResponse::success($receipt, 'Goods receipt details loaded.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }
}
