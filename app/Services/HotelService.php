<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\HotelRepository;
use App\Services\AuditLogService;
use PDO;
use Exception;

class HotelService
{
    private HotelRepository $hotelRepository;
    private AuditLogService $auditLogService;
    private PDO $pdo;

    public function __construct(HotelRepository $hotelRepository, AuditLogService $auditLogService, PDO $pdo)
    {
        $this->hotelRepository = $hotelRepository;
        $this->auditLogService = $auditLogService;
        $this->pdo = $pdo;
    }

    public function getHotel(int $id): ?array
    {
        return $this->hotelRepository->findById($id);
    }

    public function listAccessibleHotels(int $userId): array
    {
        return $this->hotelRepository->findAccessibleHotels($userId);
    }

    public function listAllHotels(): array
    {
        return $this->hotelRepository->findAll();
    }

    public function listAllGroups(): array
    {
        return $this->hotelRepository->findAllGroups();
    }

    public function createHotelGroup(array $data, int $userId): array
    {
        $this->pdo->beginTransaction();
        try {
            $groupId = $this->hotelRepository->createGroup($data);
            
            $this->auditLogService->log(
                'hotel_group',
                $groupId,
                null,
                'create',
                null,
                ['name' => $data['name']],
                $userId
            );

            $this->pdo->commit();
            return ['id' => $groupId, 'name' => $data['name']];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function createHotel(array $data, int $userId): array
    {
        $this->pdo->beginTransaction();
        try {
            $hotelId = $this->hotelRepository->create($data);
            
            $this->auditLogService->log(
                'hotel',
                $hotelId,
                $hotelId,
                'create',
                null,
                [
                    'name' => $data['name'],
                    'hotel_group_id' => $data['hotel_group_id'] ?? null,
                    'address' => $data['address'] ?? null
                ],
                $userId
            );

            // Grant the creator access to the hotel by default
            $stmt = $this->pdo->prepare('INSERT INTO user_hotel_access (user_id, hotel_id) VALUES (:user_id, :hotel_id)');
            $stmt->execute([':user_id' => $userId, ':hotel_id' => $hotelId]);

            $this->pdo->commit();
            return array_merge(['id' => $hotelId], $data);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
