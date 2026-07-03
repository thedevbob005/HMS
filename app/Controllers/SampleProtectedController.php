<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use App\Services\AuditLogService;
use App\Support\ApiResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Routing\RouteContext;

class SampleProtectedController
{
    private PDO $pdo;
    private AuditLogService $auditLogService;

    public function __construct(PDO $pdo, AuditLogService $auditLogService)
    {
        $this->pdo = $pdo;
        $this->auditLogService = $auditLogService;
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        if (!$user) {
            throw new HttpUnauthorizedException($request, 'Authentication required.');
        }

        $userId = (int)$user['user_id'];

        // 1. RBAC Permission Check (verify sample.view permission)
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
            ':permission' => 'sample.view'
        ]);
        
        $hasPermission = $stmt->fetchColumn();

        if (!$hasPermission) {
            throw new HttpForbiddenException(
                $request, 
                'You do not have the required permission (sample.view) to perform this action.'
            );
        }

        // Retrieve hotelId from route
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : null;

        // 2. Audit Logging
        $ipAddress = $request->getServerParams()['REMOTE_ADDR'] ?? '127.0.0.1';
        $this->auditLogService->log(
            'sample_endpoint',
            null, // entityId
            $hotelId,
            'view_protected_sample',
            null, // old_value
            ['access' => 'granted', 'username' => $user['username']], // new_value
            $userId,
            $ipAddress
        );

        // 3. Return Standard Envelope
        return ApiResponse::success(
            [
                'status' => 'access_granted',
                'user_id' => $userId,
                'username' => $user['username'],
                'hotel_id' => $hotelId
            ],
            'Successfully accessed protected sample endpoint.'
        );
    }
}
