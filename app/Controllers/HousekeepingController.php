<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\HousekeepingService;
use App\Support\ApiResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;
use Slim\Exception\HttpForbiddenException;
use PDO;
use Exception;

class HousekeepingController
{
    private HousekeepingService $housekeepingService;
    private PDO $pdo;

    public function __construct(HousekeepingService $housekeepingService, PDO $pdo)
    {
        $this->housekeepingService = $housekeepingService;
        $this->pdo = $pdo;
    }

    private function checkPermission(int $userId, array $permissions, Request $request): void
    {
        $placeholders = implode(',', array_fill(0, count($permissions), '?'));
        $stmt = $this->pdo->prepare("
            SELECT 1 
            FROM user_roles ur
            JOIN role_permissions rp ON ur.role_id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.id
            WHERE ur.user_id = ? AND p.name IN ($placeholders)
            LIMIT 1
        ");
        
        $params = array_merge([$userId], $permissions);
        $stmt->execute($params);
        if (!$stmt->fetchColumn()) {
            throw new HttpForbiddenException($request, sprintf('You do not have the required permissions (%s).', implode(' or ', $permissions)));
        }
    }

    public function listTasks(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], ['housekeeping.view', 'rooms.view'], $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        $queryParams = $request->getQueryParams();
        $filters = [
            'status' => $queryParams['status'] ?? null,
            'task_type' => $queryParams['task_type'] ?? null,
            'room_id' => $queryParams['room_id'] ?? null
        ];

        try {
            $tasks = $this->housekeepingService->listTasks($hotelId, $filters);
            return ApiResponse::success($tasks, 'Tasks retrieved successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function createTask(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], ['housekeeping.manage'], $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        $body = (array)$request->getParsedBody();

        try {
            $task = $this->housekeepingService->createTask($hotelId, $body, (int)$currentUser['user_id']);
            return ApiResponse::success($task, 'Housekeeping task logged successfully.', 201);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function assignTask(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], ['housekeeping.manage'], $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;
        $taskId = $route ? (int)$route->getArgument('taskId') : 0;

        $body = (array)$request->getParsedBody();
        $assigneeId = isset($body['assigned_to']) && $body['assigned_to'] !== '' ? (int)$body['assigned_to'] : null;

        try {
            $task = $this->housekeepingService->assignTask($hotelId, $taskId, $assigneeId, (int)$currentUser['user_id']);
            return ApiResponse::success($task, 'Staff assigned to task successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function updateStatus(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], ['housekeeping.manage', 'housekeeping.perform'], $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;
        $taskId = $route ? (int)$route->getArgument('taskId') : 0;

        $body = (array)$request->getParsedBody();
        $status = trim($body['status'] ?? '');
        $notes = isset($body['notes']) ? trim($body['notes']) : null;

        if ($status === '') {
            return ApiResponse::error('Task status is required.', 400);
        }

        try {
            $task = $this->housekeepingService->updateTaskStatus($hotelId, $taskId, $status, (int)$currentUser['user_id'], $notes);
            return ApiResponse::success($task, 'Task status updated successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }
}
