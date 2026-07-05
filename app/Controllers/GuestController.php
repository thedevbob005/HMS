<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\GuestService;
use App\Support\ApiResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;
use PDO;
use Exception;

class GuestController
{
    private GuestService $guestService;
    private PDO $pdo;

    public function __construct(GuestService $guestService, PDO $pdo)
    {
        $this->guestService = $guestService;
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

    public function listGuests(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'guests.view', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        $queryParams = $request->getQueryParams();
        $filters = [];
        if (!empty($queryParams['search'])) {
            $filters['search'] = $queryParams['search'];
        }

        $guests = $this->guestService->listGuests($hotelId, $filters);
        return ApiResponse::success($guests, 'Guests retrieved successfully.');
    }

    public function getGuest(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'guests.view', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;
        $guestId = $route ? (int)$route->getArgument('guestId') : 0;

        $guest = $this->guestService->getGuest($hotelId, $guestId);
        if (!$guest) {
            return ApiResponse::error('Guest not found.', 404);
        }

        return ApiResponse::success($guest, 'Guest retrieved successfully.');
    }

    public function createGuest(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'guests.create', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        $data = (array)$request->getParsedBody();

        try {
            $guest = $this->guestService->createGuest($hotelId, $data, (int)$currentUser['user_id']);
            return ApiResponse::success($guest, 'Guest profile created successfully.', 201);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function listDocuments(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'guests.view', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;
        $guestId = $route ? (int)$route->getArgument('guestId') : 0;

        try {
            $docs = $this->guestService->listDocuments($hotelId, $guestId);
            return ApiResponse::success($docs, 'Documents retrieved successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function uploadDocument(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'guests.update', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;
        $guestId = $route ? (int)$route->getArgument('guestId') : 0;

        $data = (array)$request->getParsedBody();
        
        // Grab uploaded file
        $uploadedFiles = $request->getUploadedFiles();
        $uploadedFile = $uploadedFiles['document_file'] ?? null;
        
        $fileInfo = null;
        if ($uploadedFile && $uploadedFile->getError() === UPLOAD_ERR_OK) {
            $fileInfo = [
                'tmp_name' => $uploadedFile->getFilePath(),
                'name' => $uploadedFile->getClientFilename()
            ];
        }

        try {
            $doc = $this->guestService->uploadIdentityDocument($hotelId, $guestId, $data, $fileInfo, (int)$currentUser['user_id']);
            return ApiResponse::success($doc, 'Identity document uploaded successfully.', 201);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function decryptDocumentNumber(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'guests.view_sensitive', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;
        $guestId = $route ? (int)$route->getArgument('guestId') : 0;
        $docId = $route ? (int)$route->getArgument('docId') : 0;

        $ipAddress = $request->getServerParams()['REMOTE_ADDR'] ?? '127.0.0.1';

        try {
            $decrypted = $this->guestService->decryptDocumentNumber($hotelId, $guestId, $docId, (int)$currentUser['user_id'], $ipAddress);
            return ApiResponse::success(['document_number' => $decrypted], 'Document decrypted successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }
}
