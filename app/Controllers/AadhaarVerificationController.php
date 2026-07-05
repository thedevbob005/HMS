<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AadhaarVerificationService;
use App\Support\ApiResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;
use Slim\Exception\HttpForbiddenException;
use PDO;
use Exception;

class AadhaarVerificationController
{
    private AadhaarVerificationService $verificationService;
    private PDO $pdo;

    public function __construct(AadhaarVerificationService $verificationService, PDO $pdo)
    {
        $this->verificationService = $verificationService;
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

    public function requestOtp(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'guests.verify_identity', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;
        $guestId = $route ? (int)$route->getArgument('guestId') : 0;
        $docId = $route ? (int)$route->getArgument('docId') : 0;

        try {
            $res = $this->verificationService->requestOtp($hotelId, $guestId, $docId, (int)$currentUser['user_id']);
            return ApiResponse::success($res, 'Verification OTP enqueued successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function verifyOtp(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'guests.verify_identity', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;
        $guestId = $route ? (int)$route->getArgument('guestId') : 0;
        $docId = $route ? (int)$route->getArgument('docId') : 0;

        $body = (array)$request->getParsedBody();
        $otp = trim($body['otp'] ?? '');
        $clientId = trim($body['client_id'] ?? '');

        if ($otp === '' || $clientId === '') {
            return ApiResponse::error('OTP code and client_id are required.', 400);
        }

        try {
            $res = $this->verificationService->verifyOtp($hotelId, $guestId, $docId, $otp, $clientId, (int)$currentUser['user_id']);
            return ApiResponse::success($res, 'OTP verified and demographic details loaded.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function applyManualFallback(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'guests.verify_identity', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;
        $guestId = $route ? (int)$route->getArgument('guestId') : 0;
        $docId = $route ? (int)$route->getArgument('docId') : 0;

        $body = (array)$request->getParsedBody();
        $reason = trim($body['reason'] ?? '');

        if ($reason === '') {
            return ApiResponse::error('Override reason is required.', 400);
        }

        try {
            $res = $this->verificationService->applyManualFallback($hotelId, $guestId, $docId, $reason, (int)$currentUser['user_id']);
            return ApiResponse::success($res, 'Manual override successfully applied.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function listLogs(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'guests.verify_identity', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $docId = $route ? (int)$route->getArgument('docId') : 0;

        try {
            $logs = $this->verificationService->listLogs($docId);
            return ApiResponse::success($logs, 'Verification logs retrieved successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }
}
