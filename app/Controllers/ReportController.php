<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\ApiResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;
use Slim\Exception\HttpForbiddenException;
use PDO;
use Exception;

class ReportController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
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

    public function getCollectionReport(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'reports.view_financial', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        $queryParams = $request->getQueryParams();
        $startDate = $queryParams['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
        $endDate = $queryParams['end_date'] ?? date('Y-m-d');

        // Fetch daily collections grouped by date and payment method
        $stmt = $this->pdo->prepare('
            SELECT 
                DATE(created_at) as collection_date,
                payment_method,
                SUM(amount) as total_amount,
                COUNT(*) as transaction_count
            FROM payments
            WHERE hotel_id = :hotelId
              AND DATE(created_at) >= :startDate
              AND DATE(created_at) <= :endDate
            GROUP BY DATE(created_at), payment_method
            ORDER BY collection_date DESC, payment_method ASC
        ');
        $stmt->execute([
            ':hotelId' => $hotelId,
            ':startDate' => $startDate,
            ':endDate' => $endDate
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Group rows by date for a cleaner UI layout representation
        $grouped = [];
        foreach ($rows as $row) {
            $date = $row['collection_date'];
            if (!isset($grouped[$date])) {
                $grouped[$date] = [
                    'date' => $date,
                    'methods' => [],
                    'day_total' => 0.00,
                    'day_count' => 0
                ];
            }
            $grouped[$date]['methods'][] = [
                'payment_method' => $row['payment_method'],
                'total_amount' => (float)$row['total_amount'],
                'transaction_count' => (int)$row['transaction_count']
            ];
            $grouped[$date]['day_total'] += (float)$row['total_amount'];
            $grouped[$date]['day_count'] += (int)$row['transaction_count'];
        }

        // Return list of day-by-day collections
        return ApiResponse::success(array_values($grouped), 'Daily collections report retrieved successfully.');
    }
}
