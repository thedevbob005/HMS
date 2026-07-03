<?php

declare(strict_types=1);

namespace App\Middleware;

use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Routing\RouteContext;

class HotelScopingMiddleware implements MiddlewareInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $user = $request->getAttribute('user');
        if (!$user) {
            throw new HttpUnauthorizedException($request, 'Authentication required for hotel scoping check.');
        }

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();

        if ($route) {
            $hotelIdValue = $route->getArgument('hotelId') ?? $route->getArgument('hotel_id');
            if ($hotelIdValue !== null) {
                $hotelId = (int)$hotelIdValue;
                $userId = (int)$user['user_id'];

                // Query database to check if user has access to the hotel
                $stmt = $this->pdo->prepare('
                    SELECT 1 
                    FROM user_hotel_access 
                    WHERE user_id = :user_id AND hotel_id = :hotel_id 
                    LIMIT 1
                ');
                $stmt->execute([
                    ':user_id' => $userId,
                    ':hotel_id' => $hotelId
                ]);
                
                $hasAccess = $stmt->fetchColumn();

                if (!$hasAccess) {
                    throw new HttpForbiddenException($request, 'You do not have access to this hotel.');
                }
            }
        }

        return $handler->handle($request);
    }
}
