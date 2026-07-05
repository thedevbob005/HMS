<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class HousekeepingRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function createTask(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO housekeeping_tasks 
                (hotel_id, room_id, task_type, status, priority, assigned_to, notes, created_by)
            VALUES 
                (:hotel_id, :room_id, :task_type, :status, :priority, :assigned_to, :notes, :created_by)
        ');
        
        $stmt->execute([
            ':hotel_id' => $data['hotel_id'],
            ':room_id' => $data['room_id'] ?? null,
            ':task_type' => $data['task_type'],
            ':status' => $data['status'] ?? 'pending',
            ':priority' => $data['priority'] ?? 'medium',
            ':assigned_to' => $data['assigned_to'] ?? null,
            ':notes' => $data['notes'] ?? null,
            ':created_by' => $data['created_by']
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function updateTask(int $taskId, array $data): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE housekeeping_tasks 
            SET status = :status, priority = :priority, assigned_to = :assigned_to, 
                notes = :notes, completed_at = :completed_at, updated_by = :updated_by
            WHERE id = :id
        ');

        $stmt->execute([
            ':id' => $taskId,
            ':status' => $data['status'],
            ':priority' => $data['priority'],
            ':assigned_to' => $data['assigned_to'] ?? null,
            ':notes' => $data['notes'] ?? null,
            ':completed_at' => $data['completed_at'] ?? null,
            ':updated_by' => $data['updated_by']
        ]);
    }

    public function findTaskById(int $hotelId, int $taskId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT ht.*, r.room_number, u.username as assignee_name
            FROM housekeeping_tasks ht
            LEFT JOIN rooms r ON ht.room_id = r.id
            LEFT JOIN users u ON ht.assigned_to = u.id
            WHERE ht.id = :taskId AND ht.hotel_id = :hotelId
            LIMIT 1
        ');
        $stmt->execute([':taskId' => $taskId, ':hotelId' => $hotelId]);
        $task = $stmt->fetch();
        return $task ? $task : null;
    }

    public function findAllTasks(int $hotelId, array $filters = []): array
    {
        $sql = '
            SELECT ht.*, r.room_number, u.username as assignee_name
            FROM housekeeping_tasks ht
            LEFT JOIN rooms r ON ht.room_id = r.id
            LEFT JOIN users u ON ht.assigned_to = u.id
            WHERE ht.hotel_id = :hotel_id
        ';
        $params = [':hotel_id' => $hotelId];

        if (!empty($filters['status'])) {
            $sql .= ' AND ht.status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['task_type'])) {
            $sql .= ' AND ht.task_type = :task_type';
            $params[':task_type'] = $filters['task_type'];
        }

        if (!empty($filters['room_id'])) {
            $sql .= ' AND ht.room_id = :room_id';
            $params[':room_id'] = (int)$filters['room_id'];
        }

        $sql .= ' ORDER BY FIELD(ht.priority, "high", "medium", "low"), ht.created_at DESC';
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
