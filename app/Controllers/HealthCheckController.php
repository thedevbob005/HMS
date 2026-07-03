<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;

class HealthCheckController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $dbStatus = 'connected';
        try {
            // Run a simple query to verify database connection
            $this->pdo->query('SELECT 1');
        } catch (Exception $e) {
            $dbStatus = 'disconnected: ' . $e->getMessage();
        }

        $payload = [
            'success' => true,
            'data' => [
                'status' => 'healthy',
                'database' => $dbStatus,
                'php_version' => PHP_VERSION,
            ],
            'message' => 'System is running correctly.'
        ];

        $response->getBody()->write((string)json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
