<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\InvoiceService;
use App\Services\MessagingService;
use App\Support\ApiResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;
use Slim\Exception\HttpForbiddenException;
use PDO;
use Exception;

class InvoiceController
{
    private InvoiceService $invoiceService;
    private MessagingService $messagingService;
    private PDO $pdo;

    public function __construct(InvoiceService $invoiceService, MessagingService $messagingService, PDO $pdo)
    {
        $this->invoiceService = $invoiceService;
        $this->messagingService = $messagingService;
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

    public function listInvoices(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'invoices.view', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        $queryParams = $request->getQueryParams();
        $invoices = $this->invoiceService->listInvoices($hotelId, $queryParams);

        return ApiResponse::success($invoices, 'Invoices retrieved successfully.');
    }

    public function getInvoice(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'invoices.view', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;
        $invoiceId = $route ? (int)$route->getArgument('invoiceId') : 0;

        $invoice = $this->invoiceService->getInvoice($hotelId, $invoiceId);
        if (!$invoice) {
            return ApiResponse::error('Invoice not found.', 404);
        }

        // Get stay details for printing (folio items details, checkin/checkout date)
        $stmtStay = $this->pdo->prepare('
            SELECT s.*, g.first_name, g.last_name, g.phone, g.email
            FROM stays s
            JOIN guests g ON s.guest_id = g.id
            WHERE s.id = :stayId AND s.hotel_id = :hotelId
        ');
        $stmtStay->execute([':stayId' => $invoice['stay_id'], ':hotelId' => $hotelId]);
        $stay = $stmtStay->fetch();
        if ($stay) {
            // Load stay rooms
            $stmtRooms = $this->pdo->prepare('
                SELECT sr.*, r.room_number, rt.name as room_type_name
                FROM stay_rooms sr
                JOIN rooms r ON sr.room_id = r.id
                JOIN room_types rt ON sr.room_type_id = rt.id
                WHERE sr.stay_id = :stayId
            ');
            $stmtRooms->execute([':stayId' => $invoice['stay_id']]);
            $stay['rooms'] = $stmtRooms->fetchAll();

            // Load folio items
            $stmtFolio = $this->pdo->prepare('
                SELECT * FROM folio_items 
                WHERE stay_id = :stayId 
                ORDER BY created_at ASC
            ');
            $stmtFolio->execute([':stayId' => $invoice['stay_id']]);
            $stay['folio'] = $stmtFolio->fetchAll();

            $invoice['stay_details'] = $stay;
        }

        return ApiResponse::success($invoice, 'Invoice details retrieved successfully.');
    }

    public function getStayInvoice(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'invoices.view', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;
        $stayId = $route ? (int)$route->getArgument('stayId') : 0;

        $invoice = $this->invoiceService->getInvoiceByStayId($hotelId, $stayId);
        if (!$invoice) {
            return ApiResponse::error('Invoice not found for this stay.', 404);
        }

        return ApiResponse::success($invoice, 'Invoice retrieved successfully.');
    }

    public function listMessages(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        // Let users view notification status if they have stays.view permission
        $this->checkPermission((int)$currentUser['user_id'], 'stays.view', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        $queryParams = $request->getQueryParams();
        $messages = $this->messagingService->listLogs($hotelId, $queryParams);

        return ApiResponse::success($messages, 'Notification logs retrieved successfully.');
    }
}
