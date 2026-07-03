<?php

declare(strict_types=1);

namespace App\Middleware;

use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Exception\HttpUnauthorizedException;

class AuthenticationMiddleware implements MiddlewareInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            throw new HttpUnauthorizedException($request, 'Missing or invalid Authorization header.');
        }

        $token = substr($authHeader, 7);

        // Query database to find active token
        $stmt = $this->pdo->prepare('
            SELECT ut.user_id, u.username, u.email, u.phone, u.is_active 
            FROM user_tokens ut
            JOIN users u ON ut.user_id = u.id
            WHERE ut.token = :token AND (ut.expires_at IS NULL OR ut.expires_at > NOW())
            LIMIT 1
        ');
        $stmt->execute([':token' => $token]);
        $user = $stmt->fetch();

        if (!$user) {
            throw new HttpUnauthorizedException($request, 'Invalid or expired authentication token.');
        }

        if (!(bool)$user['is_active']) {
            throw new HttpUnauthorizedException($request, 'This user account is inactive.');
        }

        // Attach user to the request context
        $request = $request->withAttribute('user', $user);

        return $handler->handle($request);
    }
}
