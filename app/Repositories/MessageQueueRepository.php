<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class MessageQueueRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function enqueue(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO message_queue (
                hotel_id, channel, recipient, message_type, template_id, variables, status
            ) VALUES (
                :hotel_id, :channel, :recipient, :message_type, :template_id, :variables, :status
            )
        ');

        $stmt->execute([
            ':hotel_id' => $data['hotel_id'],
            ':channel' => $data['channel'],
            ':recipient' => $data['recipient'],
            ':message_type' => $data['message_type'],
            ':template_id' => $data['template_id'] ?? null,
            ':variables' => isset($data['variables']) ? json_encode($data['variables']) : null,
            ':status' => $data['status'] ?? 'queued'
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function fetchPending(int $limit = 10): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * 
            FROM message_queue 
            WHERE status IN (\'queued\', \'failed\') AND retry_count < 3
            ORDER BY created_at ASC
            LIMIT :limit
        ');
        
        // bindParam is needed for LIMIT integer binding in PDO
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus(int $id, string $status, int $retryCount, ?string $error = null, ?string $response = null): void
    {
        $sql = '
            UPDATE message_queue 
            SET status = :status, retry_count = :retry_count, error_message = :error_message, provider_response = :provider_response
        ';
        if ($status === 'sent') {
            $sql .= ', sent_at = CURRENT_TIMESTAMP';
        }
        $sql .= ' WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':status' => $status,
            ':retry_count' => $retryCount,
            ':error_message' => $error,
            ':provider_response' => $response
        ]);
    }

    public function listMessages(int $hotelId, array $filters = []): array
    {
        $sql = '
            SELECT * 
            FROM message_queue 
            WHERE hotel_id = :hotelId
        ';
        $params = [':hotelId' => $hotelId];

        if (!empty($filters['status'])) {
            $sql .= ' AND status = :status';
            $params[':status'] = $filters['status'];
        }

        $sql .= ' ORDER BY created_at DESC LIMIT 50';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
