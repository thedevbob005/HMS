<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

class AuditLogService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function log(
        string $entityType,
        ?int $entityId,
        ?int $hotelId,
        string $action,
        ?array $oldValue,
        ?array $newValue,
        ?int $userId,
        ?string $ipAddress = null
    ): void {
        $stmt = $this->pdo->prepare('
            INSERT INTO audit_logs (
                entity_type, entity_id, hotel_id, action, old_value, new_value, user_id, ip_address
            ) VALUES (
                :entity_type, :entity_id, :hotel_id, :action, :old_value, :new_value, :user_id, :ip_address
            )
        ');

        $stmt->execute([
            ':entity_type' => $entityType,
            ':entity_id' => $entityId,
            ':hotel_id' => $hotelId,
            ':action' => $action,
            ':old_value' => $oldValue ? json_encode($oldValue) : null,
            ':new_value' => $newValue ? json_encode($newValue) : null,
            ':user_id' => $userId,
            ':ip_address' => $ipAddress,
        ]);
    }
}
