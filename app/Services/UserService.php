<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use App\Services\AuditLogService;
use PDO;
use Exception;

class UserService
{
    private UserRepository $userRepository;
    private AuditLogService $auditLogService;
    private PDO $pdo;

    public function __construct(UserRepository $userRepository, AuditLogService $auditLogService, PDO $pdo)
    {
        $this->userRepository = $userRepository;
        $this->auditLogService = $auditLogService;
        $this->pdo = $pdo;
    }

    public function listUsers(): array
    {
        return $this->userRepository->findAll();
    }

    public function listRoles(): array
    {
        return $this->userRepository->findAllRoles();
    }

    public function getUser(int $id): ?array
    {
        return $this->userRepository->findById($id);
    }

    public function createUser(array $data, int $creatorId): array
    {
        $this->pdo->beginTransaction();
        try {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
            
            $userId = $this->userRepository->create($data);

            // Assign roles
            if (!empty($data['role_ids'])) {
                foreach ($data['role_ids'] as $roleId) {
                    $this->userRepository->assignRole($userId, (int)$roleId);
                }
            }

            // Assign hotel accesses
            if (!empty($data['hotel_ids'])) {
                foreach ($data['hotel_ids'] as $hotelId) {
                    $this->userRepository->grantHotelAccess($userId, (int)$hotelId);
                }
            }

            $this->auditLogService->log(
                'user',
                $userId,
                null,
                'create',
                null,
                [
                    'username' => $data['username'],
                    'email' => $data['email'] ?? null,
                    'role_ids' => $data['role_ids'] ?? [],
                    'hotel_ids' => $data['hotel_ids'] ?? []
                ],
                $creatorId
            );

            $this->pdo->commit();
            return [
                'id' => $userId,
                'username' => $data['username'],
                'email' => $data['email'] ?? null
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function updateUserAccess(int $userId, array $roleIds, array $hotelIds, int $updaterId): void
    {
        $this->pdo->beginTransaction();
        try {
            $user = $this->userRepository->findById($userId);
            if (!$user) {
                throw new Exception('User not found.');
            }

            // Retrieve old access configuration for audit logs
            $stmt = $this->pdo->prepare('SELECT role_id FROM user_roles WHERE user_id = :user_id');
            $stmt->execute([':user_id' => $userId]);
            $oldRoleIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $stmt = $this->pdo->prepare('SELECT hotel_id FROM user_hotel_access WHERE user_id = :user_id');
            $stmt->execute([':user_id' => $userId]);
            $oldHotelIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Re-assign roles
            $this->userRepository->clearRoles($userId);
            foreach ($roleIds as $roleId) {
                $this->userRepository->assignRole($userId, (int)$roleId);
            }

            // Re-assign hotel access
            $this->userRepository->clearHotelAccess($userId);
            foreach ($hotelIds as $hotelId) {
                $this->userRepository->grantHotelAccess($userId, (int)$hotelId);
            }

            $this->auditLogService->log(
                'user_access',
                $userId,
                null,
                'update_access',
                [
                    'role_ids' => $oldRoleIds,
                    'hotel_ids' => $oldHotelIds
                ],
                [
                    'role_ids' => $roleIds,
                    'hotel_ids' => $hotelIds
                ],
                $updaterId
            );

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
