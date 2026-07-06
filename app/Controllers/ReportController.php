<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ReportService;
use App\Support\ApiResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;
use Slim\Exception\HttpForbiddenException;
use PDO;
use Exception;

class ReportController
{
    private ReportService $reportService;
    private PDO $pdo;

    public function __construct(ReportService $reportService, PDO $pdo)
    {
        $this->reportService = $reportService;
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

    private function getCommonParams(Request $request): array
    {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        $queryParams = $request->getQueryParams();
        $startDate = $queryParams['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
        $endDate = $queryParams['end_date'] ?? date('Y-m-d');

        return [$hotelId, $startDate, $endDate];
    }

    public function getCollectionReport(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'reports.view_financial', $request);

        list($hotelId, $startDate, $endDate) = $this->getCommonParams($request);

        try {
            $report = $this->reportService->getCollectionReport($hotelId, $startDate, $endDate);
            return ApiResponse::success($report, 'Daily collections report retrieved successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function getOccupancyReport(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'reports.view_financial', $request);

        list($hotelId, $startDate, $endDate) = $this->getCommonParams($request);

        try {
            $report = $this->reportService->getOccupancyReport($hotelId, $startDate, $endDate);
            return ApiResponse::success($report, 'Occupancy report compiled successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function getGSTReport(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'reports.view_financial', $request);

        list($hotelId, $startDate, $endDate) = $this->getCommonParams($request);

        try {
            $report = $this->reportService->getGSTReport($hotelId, $startDate, $endDate);
            return ApiResponse::success($report, 'GST tax ledger generated successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function getRevenueReport(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'reports.view_financial', $request);

        list($hotelId, $startDate, $endDate) = $this->getCommonParams($request);

        try {
            $report = $this->reportService->getRevenueReport($hotelId, $startDate, $endDate);
            return ApiResponse::success($report, 'Revenue center distribution loaded successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }
}
