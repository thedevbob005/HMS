<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\UserService;
use App\Support\ApiResponse;
use App\Validators\UserValidator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;
use Exception;

class UserController
{
    private UserService $userService;
    private \PDO $pdo;

    public function __construct(UserService $userService, \PDO $pdo)
    {
        $this->userService = $userService;
        $this->pdo = $pdo;
    }

    public function login(Request $request, Response $response): Response
    {
        $data = (array)$request->getParsedBody();
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';

        if ($username === '' || $password === '') {
            return ApiResponse::error('Username and password are required.', 400);
        }

        // Query user
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = :username AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ApiResponse::error('Invalid username or password.', 401);
        }

        if (!(bool)$user['is_active']) {
            return ApiResponse::error('This account is inactive.', 403);
        }

        // Generate token
        $token = bin2hex(random_bytes(16));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 day'));

        $stmt = $this->pdo->prepare('
            INSERT INTO user_tokens (user_id, token, expires_at) 
            VALUES (:user_id, :token, :expires_at)
        ');
        $stmt->execute([
            ':user_id' => $user['id'],
            ':token' => $token,
            ':expires_at' => $expiresAt
        ]);

        // Get accessible hotels
        $stmt = $this->pdo->prepare('
            SELECT h.id, h.name 
            FROM hotels h
            JOIN user_hotel_access uha ON h.id = uha.hotel_id
            WHERE uha.user_id = :user_id AND h.deleted_at IS NULL
        ');
        $stmt->execute([':user_id' => $user['id']]);
        $hotels = $stmt->fetchAll();

        return ApiResponse::success([
            'token' => $token,
            'user' => [
                'id' => (int)$user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'phone' => $user['phone']
            ],
            'hotels' => $hotels
        ], 'Login successful.');
    }

    public function listUsers(Request $request, Response $response): Response
    {
        $users = $this->userService->listUsers();
        return ApiResponse::success($users, 'Staff list retrieved successfully.');
    }

    public function listRoles(Request $request, Response $response): Response
    {
        $roles = $this->userService->listRoles();
        return ApiResponse::success($roles, 'Roles retrieved successfully.');
    }

    public function createUser(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $data = (array)$request->getParsedBody();

        try {
            UserValidator::validateCreate($data);
            $user = $this->userService->createUser($data, (int)$currentUser['user_id']);
            return ApiResponse::success($user, 'User created successfully.', 201);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function updateAccess(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $userId = $route ? (int)$route->getArgument('userId') : 0;
        
        $data = (array)$request->getParsedBody();

        try {
            UserValidator::validateAccessUpdate($data);
            $this->userService->updateUserAccess(
                $userId,
                $data['role_ids'],
                $data['hotel_ids'],
                (int)$currentUser['user_id']
            );
            return ApiResponse::success(null, 'User access overrides updated successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }
}
