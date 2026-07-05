<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\HousekeepingRepository;
use App\Repositories\RoomRepository;
use App\Services\AuditLogService;
use PDO;
use Exception;

class HousekeepingService
{
    private HousekeepingRepository $housekeepingRepository;
    private RoomRepository $roomRepository;
    private AuditLogService $auditLogService;
    private PDO $pdo;

    public function __construct(
        HousekeepingRepository $housekeepingRepository,
        RoomRepository $roomRepository,
        AuditLogService $auditLogService,
        PDO $pdo
    ) {
        $this->housekeepingRepository = $housekeepingRepository;
        $this->roomRepository = $roomRepository;
        $this->auditLogService = $auditLogService;
        $this->pdo = $pdo;
    }

    public function createTask(int $hotelId, array $data, int $userId): array
    {
        if (empty($data['task_type'])) {
            throw new Exception('Task type is required.');
        }

        $dbAlreadyInTransaction = $this->pdo->inTransaction();
        if (!$dbAlreadyInTransaction) {
            $this->pdo->beginTransaction();
        }
        try {
            $roomId = isset($data['room_id']) ? (int)$data['room_id'] : null;
            $taskType = $data['task_type'];
            
            $taskData = [
                'hotel_id' => $hotelId,
                'room_id' => $roomId,
                'task_type' => $taskType,
                'status' => $data['status'] ?? 'pending',
                'priority' => $data['priority'] ?? 'medium',
                'assigned_to' => isset($data['assigned_to']) ? (int)$data['assigned_to'] : null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId
            ];

            $taskId = $this->housekeepingRepository->createTask($taskData);

            // Handle room status updates if room is linked
            if ($roomId !== null) {
                $room = $this->roomRepository->findRoomById($roomId);
                if (!$room || (int)$room['hotel_id'] !== $hotelId) {
                    throw new Exception('Room not found or unauthorized.');
                }

                $oldStatus = $room['status'];
                $newStatus = null;

                if ($taskType === 'maintenance') {
                    $newStatus = 'Maintenance';
                } elseif ($taskType === 'cleaning') {
                    $newStatus = 'Cleaning';
                }

                if ($newStatus !== null && $oldStatus !== $newStatus) {
                    $this->roomRepository->updateRoomStatus($roomId, $newStatus);
                    $this->roomRepository->logStatusChange(
                        $roomId,
                        $oldStatus,
                        $newStatus,
                        sprintf('Auto-transitioned by Housekeeping %s task logging', $taskType),
                        $userId
                    );
                }
            }

            $this->auditLogService->log(
                'housekeeping_task',
                $taskId,
                $hotelId,
                'create_task',
                null,
                $taskData,
                $userId
            );

            if (!$dbAlreadyInTransaction) {
                $this->pdo->commit();
            }
            return array_merge(['id' => $taskId], $taskData);
        } catch (Exception $e) {
            if (!$dbAlreadyInTransaction) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function assignTask(int $hotelId, int $taskId, ?int $assigneeId, int $userId): array
    {
        $task = $this->housekeepingRepository->findTaskById($hotelId, $taskId);
        if (!$task) {
            throw new Exception('Housekeeping task not found or unauthorized.');
        }

        $dbAlreadyInTransaction = $this->pdo->inTransaction();
        if (!$dbAlreadyInTransaction) {
            $this->pdo->beginTransaction();
        }
        try {
            $oldAssignee = $task['assigned_to'] ? (int)$task['assigned_to'] : null;
            
            $task['assigned_to'] = $assigneeId;
            $task['updated_by'] = $userId;

            // Auto-transition status to in_progress if assigned and currently pending
            if ($assigneeId !== null && $task['status'] === 'pending') {
                $task['status'] = 'in_progress';
            }

            $this->housekeepingRepository->updateTask($taskId, $task);

            $this->auditLogService->log(
                'housekeeping_task',
                $taskId,
                $hotelId,
                'assign_task',
                ['assigned_to' => $oldAssignee],
                ['assigned_to' => $assigneeId, 'status' => $task['status']],
                $userId
            );

            if (!$dbAlreadyInTransaction) {
                $this->pdo->commit();
            }
            return $task;
        } catch (Exception $e) {
            if (!$dbAlreadyInTransaction) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function updateTaskStatus(int $hotelId, int $taskId, string $status, int $userId, ?string $notes = null): array
    {
        $task = $this->housekeepingRepository->findTaskById($hotelId, $taskId);
        if (!$task) {
            throw new Exception('Housekeeping task not found or unauthorized.');
        }

        $allowedStatuses = ['pending', 'in_progress', 'completed', 'cancelled'];
        if (!in_array($status, $allowedStatuses)) {
            throw new Exception(sprintf('Invalid task status: %s.', $status));
        }

        $dbAlreadyInTransaction = $this->pdo->inTransaction();
        if (!$dbAlreadyInTransaction) {
            $this->pdo->beginTransaction();
        }
        try {
            $oldStatus = $task['status'];
            
            $task['status'] = $status;
            $task['updated_by'] = $userId;
            
            if ($notes !== null) {
                $task['notes'] = $notes;
            }

            if ($status === 'completed') {
                $task['completed_at'] = date('Y-m-d H:i:s');
                
                // If room is linked, set room status back to Available
                if ($task['room_id'] !== null) {
                    $roomId = (int)$task['room_id'];
                    $room = $this->roomRepository->findRoomById($roomId);
                    
                    if ($room && in_array($room['status'], ['Cleaning', 'Maintenance'])) {
                        $this->roomRepository->updateRoomStatus($roomId, 'Available');
                        $this->roomRepository->logStatusChange(
                            $roomId,
                            $room['status'],
                            'Available',
                            sprintf('Auto-transitioned by Housekeeping task completion (#%d)', $taskId),
                            $userId
                        );
                    }
                }
            }

            $this->housekeepingRepository->updateTask($taskId, $task);

            $this->auditLogService->log(
                'housekeeping_task',
                $taskId,
                $hotelId,
                'update_status',
                ['status' => $oldStatus],
                ['status' => $status, 'notes' => $notes],
                $userId
            );

            if (!$dbAlreadyInTransaction) {
                $this->pdo->commit();
            }
            return $task;
        } catch (Exception $e) {
            if (!$dbAlreadyInTransaction) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function listTasks(int $hotelId, array $filters = []): array
    {
        return $this->housekeepingRepository->findAllTasks($hotelId, $filters);
    }

    public function getTask(int $hotelId, int $taskId): ?array
    {
        return $this->housekeepingRepository->findTaskById($hotelId, $taskId);
    }
}
