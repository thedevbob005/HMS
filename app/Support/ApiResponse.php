<?php

declare(strict_types=1);

namespace App\Support;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Response as SlimResponse;

class ApiResponse
{
    public static function success($data = null, string $message = 'Saved successfully', int $statusCode = 200): Response
    {
        $response = new SlimResponse();
        $payload = [
            'success' => true,
            'data' => $data,
            'message' => $message,
        ];
        
        $response->getBody()->write((string)json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }

    public static function error(string $message, int $statusCode = 400, array $errors = []): Response
    {
        $response = new SlimResponse();
        $payload = [
            'success' => false,
            'message' => $message,
        ];
        
        if (!empty($errors)) {
            $payload['errors'] = $errors;
        }

        $response->getBody()->write((string)json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }
}
