<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class IdentityVerificationRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function createLog(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO identity_verification_logs 
                (hotel_id, guest_id, document_id, action, status, provider, reference_id, details_json, created_by)
            VALUES 
                (:hotel_id, :guest_id, :document_id, :action, :status, :provider, :reference_id, :details_json, :created_by)
        ');
        
        $stmt->execute([
            ':hotel_id' => $data['hotel_id'],
            ':guest_id' => $data['guest_id'],
            ':document_id' => $data['document_id'],
            ':action' => $data['action'],
            ':status' => $data['status'],
            ':provider' => $data['provider'],
            ':reference_id' => $data['reference_id'] ?? null,
            ':details_json' => $data['details_json'] ?? null,
            ':created_by' => $data['created_by']
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function findLogsByDocumentId(int $docId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT ivl.*, u.username as creator_name
            FROM identity_verification_logs ivl
            LEFT JOIN users u ON ivl.created_by = u.id
            WHERE ivl.document_id = :docId
            ORDER BY ivl.created_at DESC
        ');
        $stmt->execute([':docId' => $docId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
